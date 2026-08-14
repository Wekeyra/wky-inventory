<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Suis modul lanjutan bagi ruang kerja.
 *
 * Sistem ini tumbuh melepasi MVP jauh lebih cepat daripada pengguna barunya.
 * Syarikat yang baru memasukkan produk pertamanya tidak perlu melihat Pesanan
 * Belian, COGS dan analitik pusing ganti pada hari pertama — ia hanya
 * menjadikan sistem kelihatan lebih rumit daripada kerja yang hendak dibuat.
 */
class CiriController extends Controller
{
    public function edit(Request $request): View
    {
        return view('tetapan.ciri', [
            'ruangKerja' => $request->user()->workspace,
            'senarai' => Workspace::CIRI,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ciri' => ['nullable', 'array'],
            'ciri.*' => [Rule::in(Workspace::CIRI)],
        ]);

        /*
         | Data modul yang dimatikan TIDAK dibuang. Mematikan Jualan bukan
         | bermakna jualan itu tidak pernah berlaku, dan pergerakan stok yang
         | terhasil daripadanya masih merujuk kodnya. Menghidupkannya semula
         | mesti memulangkan segala-galanya seperti sedia kala — kalau tidak,
         | suis ini menjadi butang padam yang menyamar.
         */
        $request->user()->workspace->update([
            'ciri' => array_values($data['ciri'] ?? []),
        ]);

        return back()->with('status', __('wky.flash.ciri_disimpan'));
    }
}
