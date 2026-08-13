{{--
    Kotak inventori 3D sebagai hiasan latar halaman log masuk.

    Ia dibina daripada enam muka CSS dan bukan model 3D sebenar. Model GLB
    memerlukan pustaka pemapar seperti three.js — beratus kilobait JavaScript
    pada halaman yang kerjanya hanya menerima dua medan teks. Kotak ini pula
    tiada kos muat turun langsung dan tidak menyentuh saiz bundle.

    Semua muka lut sinar, jadi rusuk belakang kelihatan menembusi rusuk hadapan
    semasa ia berputar — itu yang memberi kesan kaca, dan sebab itu juga tiada
    muka yang legap sepenuhnya.
--}}
<div class="kotak-3d" aria-hidden="true">
    <div class="kotak-3d-pentas">
        <div class="kotak-muka kotak-depan">
            <span class="kotak-label"></span>
        </div>
        <div class="kotak-muka kotak-belakang"></div>
        <div class="kotak-muka kotak-kiri"></div>
        <div class="kotak-muka kotak-kanan">
            <span class="kotak-panah"></span>
            <span class="kotak-panah kotak-panah-kedua"></span>
        </div>
        <div class="kotak-muka kotak-atas">
            <span class="kotak-pita"></span>
        </div>
        <div class="kotak-muka kotak-bawah"></div>
    </div>
</div>
