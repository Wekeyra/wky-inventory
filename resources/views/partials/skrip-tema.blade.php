{{--
    Skrip menyekat-fasad (blocking) yang mesti berjalan sebelum <body> dicat,
    supaya tiada kelipan tema salah (FOUC). Sebab itu ia disertakan terus
    dalam <head>, bukan dalam app.js yang hanya dimuatkan selepas DOM sedia.

    Keutamaan: pilihan tersimpan pengguna > keutamaan sistem > terang (lalai).
    Ditulis dalam cuba/tangkap kerana localStorage boleh gagal dalam mod
    penyemakan persendirian sesetengah pelayar, dan tema terang tetap satu
    hasil yang selamat jika ia gagal.
--}}
<script>
    (function () {
        try {
            var simpanan = localStorage.getItem('wky-tema');
            var gelap = simpanan
                ? simpanan === 'gelap'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;

            document.documentElement.classList.toggle('dark', gelap);
        } catch (ralat) {
            // Tema terang (lalai) kekal terpakai.
        }
    })();
</script>
