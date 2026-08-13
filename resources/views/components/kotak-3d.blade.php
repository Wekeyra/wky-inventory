{{--
    Kotak inventori 3D terbuka sebagai hiasan latar halaman auth.

    Ia dibina daripada muka CSS dan bukan model 3D sebenar. Model GLB
    memerlukan pustaka pemapar seperti three.js — beratus kilobait JavaScript
    pada halaman yang kerjanya hanya menerima dua medan teks. Kotak ini pula
    tiada kos muat turun langsung dan tidak menyentuh saiz bundle.

    Setiap dinding ialah bekasnya sendiri supaya kepak boleh berengsel pada
    tepi atas dinding itu. Kalau kepak diletak terus dalam pentas, putaran
    rotateY dinding akan berlaku pada paksi kepak itu sendiri dan kepak
    tercabut daripada dindingnya.

    Tiga lapisan bersarang, setiap satu dengan tugas tersendiri:
      .kotak-3d-pentas — condong mengikut tetikus (ditetapkan oleh JavaScript)
      .kotak-3d-putar  — putaran perlahan berterusan (animasi CSS)
      dinding + kepak  — bentuk kotak itu sendiri

    Ia dipisahkan begitu kerana transform ialah satu sifat: kalau JavaScript
    dan animasi CSS menulis pada elemen yang sama, satu akan memadam satu lagi.
--}}
<div class="kotak-3d" aria-hidden="true">
    <div class="kotak-3d-pentas">
        <div class="kotak-3d-putar">
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
        </div>
    </div>
</div>
