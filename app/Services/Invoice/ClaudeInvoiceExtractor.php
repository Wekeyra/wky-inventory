<?php

namespace App\Services\Invoice;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Messages\Base64ImageSource;
use Anthropic\Messages\Base64PDFSource;
use Anthropic\Messages\DocumentBlockParam;
use Anthropic\Messages\ImageBlockParam;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Membaca invois menggunakan keupayaan penglihatan Claude.
 *
 * Skema output berstruktur memaksa balasan menjadi JSON yang sah, jadi tiada
 * penghuraian teks bebas diperlukan dan kegagalan format mustahil berlaku
 * secara senyap.
 */
class ClaudeInvoiceExtractor implements InvoiceExtractor
{
    private const JENIS_IMEJ = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const ARAHAN_SISTEM = <<<'TEKS'
        You extract line items from supplier invoices and delivery orders for an
        inventory system. The document may be a photo taken at an angle, a scan,
        or a PDF, and may be in Malay or English.

        Rules:
        - Extract only rows that represent goods received. Ignore subtotals, tax
          lines, shipping charges, discounts, and totals.
        - "kuantiti" is the quantity of units received, as a whole number. If a row
          shows a quantity like "2 boxes", record 2 — do not convert to inner units.
        - "sku" is the supplier's product or item code for that row, if the document
          shows one. Use null when there is no code, and never invent one.
        - "nama" is the product description exactly as printed on the document.
        - "harga_unit" is the price for one unit, not the line total. Use null when
          the document does not show a unit price.
        - "tarikh_invois" must be in YYYY-MM-DD format. Use null if no date is legible.
        - If the document is unreadable or is not an invoice, return an empty
          "barang" array rather than guessing.

        Report what the document actually says. Do not correct, normalize, or
        complete values you cannot read — use null instead.
        TEKS;

    public function __construct(private readonly Client $client) {}

    public function extract(string $kandungan, string $jenisMime): ExtractedInvoice
    {
        $blokDokumen = $this->blokDokumen($kandungan, $jenisMime);

        try {
            $message = $this->client->messages->create(
                model: config('anthropic.model'),
                maxTokens: 16000,
                system: self::ARAHAN_SISTEM,
                thinking: ['type' => 'adaptive'],
                outputConfig: [
                    'effort' => config('anthropic.effort'),
                    'format' => ['type' => 'json_schema', 'schema' => $this->skema()],
                ],
                messages: [[
                    'role' => 'user',
                    'content' => [
                        $blokDokumen,
                        ['type' => 'text', 'text' => 'Extract the line items from this invoice.'],
                    ],
                ]],
            );
        } catch (APIStatusException $e) {
            Log::warning('Imbasan invois gagal', ['jenis' => $e->type?->value, 'mesej' => $e->getMessage()]);

            throw new InvoiceExtractionException($this->mesejRalatApi($e), previous: $e);
        } catch (AnthropicException $e) {
            Log::warning('Imbasan invois gagal', ['mesej' => $e->getMessage()]);

            throw new InvoiceExtractionException(__('wky.imbas.ralat_sambungan'), previous: $e);
        }

        // Klasifikasi keselamatan boleh menolak permintaan; ia dipulangkan sebagai
        // respons yang berjaya dengan kandungan kosong, bukan sebagai ralat HTTP.
        if ($message->stopReason === 'refusal') {
            throw new InvoiceExtractionException(__('wky.imbas.ralat_ditolak'));
        }

        if ($message->stopReason === 'max_tokens') {
            throw new InvoiceExtractionException(__('wky.imbas.ralat_terpotong'));
        }

        return $this->hurai($this->teksPertama($message->content));
    }

    /**
     * @return ImageBlockParam|DocumentBlockParam
     */
    private function blokDokumen(string $kandungan, string $jenisMime): object
    {
        $base64 = base64_encode($kandungan);

        if (in_array($jenisMime, self::JENIS_IMEJ, true)) {
            return ImageBlockParam::with(
                source: Base64ImageSource::with(data: $base64, mediaType: $jenisMime),
            );
        }

        if ($jenisMime === 'application/pdf') {
            return DocumentBlockParam::with(source: Base64PDFSource::with(data: $base64));
        }

        throw new InvoiceExtractionException(__('wky.imbas.ralat_jenis_fail'));
    }

    /**
     * @param  array<int, object>  $content
     */
    private function teksPertama(array $content): string
    {
        foreach ($content as $blok) {
            if ($blok->type === 'text') {
                return $blok->text;
            }
        }

        throw new InvoiceExtractionException(__('wky.imbas.ralat_kosong'));
    }

    private function hurai(string $json): ExtractedInvoice
    {
        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new InvoiceExtractionException(__('wky.imbas.ralat_kosong'), previous: $e);
        }

        $barang = [];

        foreach ($data['barang'] ?? [] as $baris) {
            $nama = trim((string) ($baris['nama'] ?? ''));
            $kuantiti = (int) ($baris['kuantiti'] ?? 0);

            // Baris tanpa nama atau tanpa kuantiti positif tidak boleh menjadi
            // pergerakan stok, jadi ia digugurkan di sini dan bukan di hilir.
            if ($nama === '' || $kuantiti < 1) {
                continue;
            }

            $sku = isset($baris['sku']) ? trim((string) $baris['sku']) : '';

            $barang[] = new ExtractedLine(
                sku: $sku !== '' ? $sku : null,
                nama: $nama,
                kuantiti: $kuantiti,
                hargaUnit: isset($baris['harga_unit']) ? (float) $baris['harga_unit'] : null,
            );
        }

        return new ExtractedInvoice(
            noInvois: $this->teksAtauNull($data['no_invois'] ?? null),
            tarikhInvois: $this->tarikhAtauNull($data['tarikh_invois'] ?? null),
            namaPembekal: $this->teksAtauNull($data['nama_pembekal'] ?? null),
            barang: $barang,
        );
    }

    private function teksAtauNull(mixed $nilai): ?string
    {
        $teks = trim((string) ($nilai ?? ''));

        return $teks !== '' ? $teks : null;
    }

    private function tarikhAtauNull(mixed $nilai): ?string
    {
        $teks = $this->teksAtauNull($nilai);

        // Model diminta memulangkan YYYY-MM-DD, tetapi tarikh yang tidak menepati
        // format itu digugurkan supaya ia tidak menjadi ralat pangkalan data.
        return $teks !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $teks) === 1 ? $teks : null;
    }

    private function mesejRalatApi(APIStatusException $e): string
    {
        return match ($e->type?->value) {
            'authentication_error' => __('wky.imbas.ralat_kunci'),
            'rate_limit_error' => __('wky.imbas.ralat_had_kadar'),
            'overloaded_error' => __('wky.imbas.ralat_sibuk'),
            'request_too_large' => __('wky.imbas.ralat_terlalu_besar'),
            default => __('wky.imbas.ralat_api', ['mesej' => $e->getMessage()]),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function skema(): array
    {
        $bolehNull = fn (string $jenis): array => ['anyOf' => [['type' => $jenis], ['type' => 'null']]];

        return [
            'type' => 'object',
            'properties' => [
                'no_invois' => $bolehNull('string') + ['description' => 'Invoice or delivery order number.'],
                'tarikh_invois' => $bolehNull('string') + ['description' => 'Invoice date as YYYY-MM-DD.'],
                'nama_pembekal' => $bolehNull('string') + ['description' => 'Supplier company name.'],
                'barang' => [
                    'type' => 'array',
                    'description' => 'One entry per goods line on the document.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'sku' => $bolehNull('string') + ['description' => "Supplier's item code, or null."],
                            'nama' => ['type' => 'string', 'description' => 'Product description as printed.'],
                            'kuantiti' => ['type' => 'integer', 'description' => 'Units received.'],
                            'harga_unit' => $bolehNull('number') + ['description' => 'Price per unit, or null.'],
                        ],
                        'required' => ['sku', 'nama', 'kuantiti', 'harga_unit'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['no_invois', 'tarikh_invois', 'nama_pembekal', 'barang'],
            'additionalProperties' => false,
        ];
    }
}
