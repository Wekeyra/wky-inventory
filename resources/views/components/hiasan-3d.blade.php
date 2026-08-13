{{--
    Hiasan latar halaman auth: empat objek gudang 3D yang berselerak di sekitar
    borang.

    Semuanya dibina daripada satah CSS dan bukan model 3D sebenar. Model GLB
    memerlukan pustaka pemapar seperti three.js — beratus kilobait JavaScript
    pada halaman yang kerjanya hanya menerima dua medan teks.

    Setiap objek membawa perspektifnya sendiri. Satu perspektif dikongsi pada
    bekas induk akan mengukur semua objek dari titik yang sama, jadi objek di
    tepi skrin akan terherot teruk kerana ia dipandang dari sudut yang jauh
    daripada pusatnya sendiri.

    Tiga lapisan bersarang pada setiap objek:
      .objek-condong — condong mengikut tetikus (ditulis oleh JavaScript)
      .objek-putar   — putaran perlahan berterusan (animasi CSS)
      satah          — bentuk objek itu sendiri

    Ia dipisahkan kerana transform ialah satu sifat: kalau JavaScript dan
    animasi CSS menulis pada elemen yang sama, satu akan memadam satu lagi.

    data-dalam ialah faktor kedalaman parallax. Objek yang sepatutnya terasa
    lebih dekat bergerak lebih banyak daripada objek yang jauh — itu yang
    menjadikannya parallax dan bukan sekadar empat benda bergoyang serentak.
--}}
<div class="hiasan-3d" aria-hidden="true">
    {{-- Kotak terbuka di atas palet. --}}
    <div class="objek objek-kotak">
        <div class="objek-condong" data-dalam="1">
            <div class="objek-putar">
                <div class="kotak-dinding kotak-depan">
                    <div class="kotak-muka"><span class="kotak-label"></span></div>
                    <div class="kotak-kepak"></div>
                </div>

                <div class="kotak-dinding kotak-belakang">
                    <div class="kotak-muka"></div>
                    <div class="kotak-kepak"></div>
                </div>

                <div class="kotak-dinding kotak-kanan">
                    <div class="kotak-muka">
                        <span class="kotak-panah"></span>
                        <span class="kotak-panah kotak-panah-kedua"></span>
                    </div>
                    <div class="kotak-kepak"></div>
                </div>

                <div class="kotak-dinding kotak-kiri">
                    <div class="kotak-muka"></div>
                    <div class="kotak-kepak"></div>
                </div>

                <div class="kotak-muka kotak-bawah"></div>

                <div class="palet-papan"></div>
                <div class="palet-papan palet-papan-bawah"></div>
            </div>
        </div>
    </div>

    {{-- Rak gudang: dua sisi tegak dan tiga tingkat. --}}
    <div class="objek objek-rak">
        <div class="objek-condong" data-dalam="0.6">
            <div class="objek-putar">
                <div class="rak-sisi rak-sisi-kiri"></div>
                <div class="rak-sisi rak-sisi-kanan"></div>

                <div class="rak-tingkat rak-tingkat-atas"></div>
                <div class="rak-tingkat rak-tingkat-tengah"></div>
                <div class="rak-tingkat rak-tingkat-bawah"></div>

                {{-- Sekotak kecil pada tingkat tengah supaya rak itu tidak kosong. --}}
                <div class="rak-muatan"></div>
            </div>
        </div>
    </div>

    {{-- Label kod bar. --}}
    <div class="objek objek-kodbar">
        <div class="objek-condong" data-dalam="1.35">
            <div class="objek-putar">
                <div class="kodbar-muka">
                    <span class="kodbar-jalur"></span>
                    <span class="kodbar-nombor"></span>
                </div>
                <div class="kodbar-muka kodbar-belakang"></div>
            </div>
        </div>
    </div>

    {{-- Forklift, dilihat dari sisi. --}}
    <div class="objek objek-forklift">
        <div class="objek-condong" data-dalam="0.85">
            <div class="objek-putar">
                <div class="fl-badan fl-badan-kiri"></div>
                <div class="fl-badan fl-badan-kanan"></div>

                <div class="fl-tiang fl-tiang-kiri"></div>
                <div class="fl-tiang fl-tiang-kanan"></div>

                <div class="fl-garpu fl-garpu-kiri"></div>
                <div class="fl-garpu fl-garpu-kanan"></div>

                <div class="fl-roda fl-roda-depan"></div>
                <div class="fl-roda fl-roda-belakang"></div>
            </div>
        </div>
    </div>
</div>
