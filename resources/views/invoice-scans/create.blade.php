@extends('layouts.app')
@section('tajuk', __('wky.imbas.tajuk_muat_naik'))

@section('kandungan')
    @unless ($adaKunci)
        <div class="amaran-gagal mb-4 max-w-2xl">
            <x-ikon nama="amaran" kelas="size-5 shrink-0" />
            <span>{{ __('wky.imbas.ralat_tiada_kunci') }}</span>
        </div>
    @endunless

    <form method="POST" action="{{ route('invoice-scans.store') }}" enctype="multipart/form-data"
          class="kad max-w-2xl" id="borangImbas">
        @csrf

        <div class="kad-badan space-y-4">
            <div>
                <label for="invois" class="mb-1 block font-medium">
                    {{ __('wky.imbas.fail_invois') }} <span class="text-merah">*</span>
                </label>

                <button type="button" class="btn-wky mb-3 w-full py-3 sm:w-auto sm:px-5" id="butangKamera">
                    <x-ikon nama="imbas" kelas="size-5" />
                    {{ __('wky.imbas.ambil_gambar') }}
                </button>

                <p class="mb-1.5 text-xs text-malap">{{ __('wky.imbas.atau_pilih_fail') }}</p>

                <input type="file" id="invois" name="invois" required
                       accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                       class="file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-merah-gelap file:px-3 file:py-1.5 file:text-sm file:text-white hover:file:bg-merah"
                       @error('invois') class="medan-ralat" @enderror>

                {{--
                    Peranti tanpa sokongan kamera dalam halaman — contohnya pelayar
                    tanpa HTTPS — jatuh ke sini. Pada telefon, capture membuka
                    aplikasi kamera terus.
                --}}
                <input type="file" id="invoisKamera" accept="image/*" capture="environment" class="hidden" tabindex="-1">

                <p class="mt-1 text-xs text-malap">{{ __('wky.imbas.fail_nota', ['saiz' => round($saizMaksKb / 1024)]) }}</p>
                @error('invois') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror

                <div id="pratontonKotak" class="mt-3 hidden">
                    <div class="flex flex-wrap items-center gap-3">
                        <img id="pratontonImej" class="pratonton-invois" alt="{{ __('wky.imbas.fail_invois') }}">
                        <div class="min-w-0">
                            <p id="pratontonNama" class="text-sm break-all text-teks"></p>
                            <button type="button" class="btn-garis btn-kecil mt-2" id="butangAmbilSemula">
                                <x-ikon nama="segar-semula" kelas="size-4" />
                                {{ __('wky.imbas.ambil_semula') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="catatan" class="mb-1 block font-medium">{{ __('wky.medan.catatan') }}</label>
                <textarea id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
            </div>

            <div class="amaran-info">
                <span>
                    {!! __('wky.imbas.nota_muat_naik', [
                        'tidak' => '<strong>' . e(__('wky.umum.tiada')) . '</strong>',
                        'sahkan' => '<strong>' . e(__('wky.imbas.sahkan_rekod')) . '</strong>',
                    ]) !!}
                </span>
            </div>
        </div>

        {{--
            Tindakan dibawa oleh medan tersembunyi dan bukan oleh nilai butang,
            kerana butang yang dilumpuhkan semasa penghantaran boleh menyebabkan
            nama dan nilainya tercicir daripada data borang.
        --}}
        <input type="hidden" name="tindakan" id="tindakan" value="baca">

        <div class="kad-kaki">
            <button type="submit" class="btn-utama" id="butangImbas" data-tindakan="baca" @disabled(! $adaKunci)>
                <x-ikon nama="imbas" kelas="size-4" /> {{ __('wky.imbas.butang') }}
            </button>
            <button type="submit" class="btn-wky" id="butangSimpan" data-tindakan="simpan">
                <x-ikon nama="simpan" kelas="size-4" /> {{ __('wky.imbas.simpan_sahaja') }}
            </button>
            <a href="{{ route('invoice-scans.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>

    <p class="mt-3 max-w-2xl text-sm text-malap">{{ __('wky.imbas.nota_simpan_sahaja') }}</p>

    <div id="modal-kamera" data-modal
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-sm [&:not(.hidden)]:flex"
         role="dialog" aria-modal="true" aria-labelledby="tajuk-modal-kamera">
        <div class="kad w-full max-w-2xl shadow-2xl">
            <div class="kad-kepala">
                <h2 id="tajuk-modal-kamera" class="flex items-center gap-2 font-semibold">
                    <x-ikon nama="imbas" kelas="size-5 text-merah" />
                    {{ __('wky.imbas.kamera_tajuk') }}
                </h2>
                <button type="button" class="cursor-pointer text-malap hover:text-white" data-modal-tutup
                        aria-label="{{ __('wky.aksi.batal') }}">
                    <x-ikon nama="silang" />
                </button>
            </div>

            <div class="kad-badan space-y-3">
                <div id="kameraRalat" class="amaran-gagal hidden" role="alert">
                    <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                    <span id="kameraRalatTeks"></span>
                </div>

                <div class="rangka-kamera">
                    <video id="kameraVideo" playsinline autoplay muted></video>
                </div>

                <p class="text-xs text-malap">{{ __('wky.imbas.kamera_petua') }}</p>
            </div>

            <div class="kad-kaki">
                <button type="button" class="btn-utama" id="butangTangkap">
                    <x-ikon nama="imbas" kelas="size-4" />
                    {{ __('wky.imbas.tangkap') }}
                </button>
                <button type="button" class="btn-garis hidden" id="butangTukarKamera">
                    <x-ikon nama="segar-semula" kelas="size-4" />
                    {{ __('wky.imbas.tukar_kamera') }}
                </button>
                <button type="button" class="btn-garis" data-modal-tutup>{{ __('wky.aksi.batal') }}</button>
            </div>
        </div>
    </div>
@endsection

@push('skrip')
    <script>
        (function () {
            const borang = document.getElementById('borangImbas');
            const medanTindakan = document.getElementById('tindakan');
            const butangTindakan = borang.querySelectorAll('[data-tindakan]');
            const menunggu = @json(__('wky.imbas.sedang_baca'));

            butangTindakan.forEach(function (butang) {
                butang.addEventListener('click', function () {
                    medanTindakan.value = butang.dataset.tindakan;
                });
            });

            // Membaca invois mengambil masa beberapa saat; kedua-dua butang
            // dikunci supaya pengguna tidak menghantar dokumen yang sama dua kali.
            borang.addEventListener('submit', function () {
                butangTindakan.forEach(function (butang) {
                    butang.disabled = true;

                    if (medanTindakan.value === 'baca' && butang.dataset.tindakan === 'baca') {
                        butang.textContent = menunggu;
                    }
                });
            });

            const medanFail = document.getElementById('invois');
            const medanKamera = document.getElementById('invoisKamera');
            const butangKamera = document.getElementById('butangKamera');
            const butangAmbilSemula = document.getElementById('butangAmbilSemula');
            const modal = document.getElementById('modal-kamera');
            const video = document.getElementById('kameraVideo');
            const butangTangkap = document.getElementById('butangTangkap');
            const butangTukar = document.getElementById('butangTukarKamera');
            const kotakRalat = document.getElementById('kameraRalat');
            const teksRalat = document.getElementById('kameraRalatTeks');
            const pratontonKotak = document.getElementById('pratontonKotak');
            const pratontonImej = document.getElementById('pratontonImej');
            const pratontonNama = document.getElementById('pratontonNama');

            const teks = @json([
                'ditolak' => __('wky.imbas.kamera_ditolak'),
                'gagal' => __('wky.imbas.kamera_gagal'),
            ]);

            // Gambar dikecilkan sebelum dihantar. 2000px masih cukup tajam untuk
            // AI membaca teks invois, tetapi jauh lebih kecil daripada fail penuh
            // kamera telefon moden.
            const SISI_MAKS = 2000;

            let strim = null;
            let arahKamera = 'environment';
            let urlPratonton = null;

            const adaSokonganKamera = () => Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

            butangKamera.addEventListener('click', bukaKamera);
            butangAmbilSemula.addEventListener('click', bukaKamera);

            function bukaKamera() {
                // Tanpa getUserMedia — contohnya halaman bukan HTTPS — input
                // capture masih membuka aplikasi kamera pada telefon.
                if (! adaSokonganKamera()) {
                    medanKamera.click();

                    return;
                }

                kotakRalat.classList.add('hidden');
                modal.classList.remove('hidden');
                mulakanStrim();
            }

            async function mulakanStrim() {
                hentikanStrim();

                try {
                    strim = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: arahKamera },
                            width: { ideal: 1920 },
                            height: { ideal: 1080 },
                        },
                        audio: false,
                    });

                    video.srcObject = strim;
                    butangTangkap.disabled = false;
                    kotakRalat.classList.add('hidden');

                    // Butang tukar kamera hanya berguna apabila ada lebih daripada
                    // satu kamera, jadi ia disembunyikan pada komputer meja biasa.
                    const peranti = await navigator.mediaDevices.enumerateDevices();
                    const bilKamera = peranti.filter((p) => p.kind === 'videoinput').length;
                    butangTukar.classList.toggle('hidden', bilKamera < 2);
                } catch (ralat) {
                    butangTangkap.disabled = true;
                    butangTukar.classList.add('hidden');
                    teksRalat.textContent = ralat.name === 'NotAllowedError' ? teks.ditolak : teks.gagal;
                    kotakRalat.classList.remove('hidden');
                }
            }

            function hentikanStrim() {
                if (strim) {
                    strim.getTracks().forEach((trek) => trek.stop());
                    strim = null;
                }

                video.srcObject = null;
            }

            butangTukar.addEventListener('click', function () {
                arahKamera = arahKamera === 'environment' ? 'user' : 'environment';
                mulakanStrim();
            });

            butangTangkap.addEventListener('click', function () {
                if (! video.videoWidth) {
                    return;
                }

                const skala = Math.min(1, SISI_MAKS / Math.max(video.videoWidth, video.videoHeight));
                const kanvas = document.createElement('canvas');

                kanvas.width = Math.round(video.videoWidth * skala);
                kanvas.height = Math.round(video.videoHeight * skala);
                kanvas.getContext('2d').drawImage(video, 0, 0, kanvas.width, kanvas.height);

                kanvas.toBlob(function (blob) {
                    if (! blob) {
                        return;
                    }

                    letakkanFail(new File([blob], namaFail(), { type: 'image/jpeg' }));
                    modal.classList.add('hidden');
                }, 'image/jpeg', 0.92);
            });

            medanKamera.addEventListener('change', function () {
                if (medanKamera.files[0]) {
                    letakkanFail(medanKamera.files[0]);
                }
            });

            medanFail.addEventListener('change', function () {
                paparPratonton(medanFail.files[0]);
            });

            /** Menyerahkan fail kepada medan sebenar supaya borang menghantarnya seperti biasa. */
            function letakkanFail(fail) {
                const pemindah = new DataTransfer();
                pemindah.items.add(fail);
                medanFail.files = pemindah.files;

                paparPratonton(fail);
            }

            function paparPratonton(fail) {
                if (urlPratonton) {
                    URL.revokeObjectURL(urlPratonton);
                    urlPratonton = null;
                }

                if (! fail || ! fail.type.startsWith('image/')) {
                    pratontonKotak.classList.add('hidden');
                    pratontonImej.removeAttribute('src');

                    return;
                }

                urlPratonton = URL.createObjectURL(fail);
                pratontonImej.src = urlPratonton;
                pratontonNama.textContent = `${fail.name} — ${(fail.size / 1024 / 1024).toFixed(1)} MB`;
                pratontonKotak.classList.remove('hidden');
            }

            function namaFail() {
                const t = new Date();
                const dua = (n) => String(n).padStart(2, '0');

                return `invois-${t.getFullYear()}${dua(t.getMonth() + 1)}${dua(t.getDate())}`
                    + `-${dua(t.getHours())}${dua(t.getMinutes())}${dua(t.getSeconds())}.jpg`;
            }

            // Modal boleh ditutup melalui Escape, klik latar atau butang Batal.
            // Memerhati kelasnya bermakna kamera dimatikan pada ketiga-tiga cara.
            new MutationObserver(function () {
                if (modal.classList.contains('hidden')) {
                    hentikanStrim();
                }
            }).observe(modal, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
@endpush
