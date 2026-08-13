import Chart from 'chart.js/auto';

// Chart.js digunakan oleh carta Ringkasan Bulanan pada dashboard, yang diselitkan
// sebagai skrip terbaris dalam Blade. Ia didedahkan di sini supaya Vite yang
// membungkusnya dan tiada muatan CDN diperlukan.
window.Chart = Chart;

/**
 * Menu jatuh ringkas. Elemen pencetus membawa data-jatuh="<id panel>".
 * Menggantikan komponen dropdown Bootstrap tanpa membawa masuk keseluruhan pustakanya.
 */
function mulakanMenuJatuh() {
    const pencetus = document.querySelectorAll('[data-jatuh]');

    pencetus.forEach((butang) => {
        const panel = document.getElementById(butang.dataset.jatuh);

        if (! panel) {
            return;
        }

        butang.addEventListener('click', (peristiwa) => {
            peristiwa.stopPropagation();
            const sedangBuka = ! panel.classList.contains('hidden');

            tutupSemuaMenu();

            if (! sedangBuka) {
                panel.classList.remove('hidden');
                butang.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', tutupSemuaMenu);

    document.addEventListener('keydown', (peristiwa) => {
        if (peristiwa.key === 'Escape') {
            tutupSemuaMenu();
        }
    });
}

function tutupSemuaMenu() {
    document.querySelectorAll('[data-jatuh]').forEach((butang) => {
        document.getElementById(butang.dataset.jatuh)?.classList.add('hidden');
        butang.setAttribute('aria-expanded', 'false');
    });
}

/**
 * Modal. data-modal-buka="<id>" membuka, data-modal-tutup menutup.
 * Fokus dialihkan ke medan pertama supaya borang boleh terus diisi dari papan kekunci.
 */
function mulakanModal() {
    document.querySelectorAll('[data-modal-buka]').forEach((butang) => {
        butang.addEventListener('click', () => bukaModal(butang.dataset.modalBuka));
    });

    document.querySelectorAll('[data-modal-tutup]').forEach((butang) => {
        butang.addEventListener('click', () => butang.closest('[data-modal]')?.classList.add('hidden'));
    });

    document.querySelectorAll('[data-modal]').forEach((modal) => {
        // Klik pada latar gelap di luar kotak dialog menutup modal.
        modal.addEventListener('click', (peristiwa) => {
            if (peristiwa.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });

    document.addEventListener('keydown', (peristiwa) => {
        if (peristiwa.key === 'Escape') {
            document.querySelectorAll('[data-modal]:not(.hidden)').forEach((modal) => modal.classList.add('hidden'));
        }
    });
}

function bukaModal(id) {
    const modal = document.getElementById(id);

    if (! modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.querySelector('select, input, textarea')?.focus();
}

/** Butang tutup pada mesej flash. */
function mulakanAmaran() {
    document.querySelectorAll('[data-amaran-tutup]').forEach((butang) => {
        butang.addEventListener('click', () => butang.closest('[data-amaran]')?.remove());
    });
}

/**
 * Butang mata pada medan kata laluan. Pencetus membawa
 * data-tunjuk-kata-laluan="<id medan>".
 */
function mulakanTunjukKataLaluan() {
    document.querySelectorAll('[data-tunjuk-kata-laluan]').forEach((butang) => {
        const medan = document.getElementById(butang.dataset.tunjukKataLaluan);

        if (! medan) {
            return;
        }

        butang.addEventListener('click', () => {
            const sedangTersembunyi = medan.type === 'password';

            medan.type = sedangTersembunyi ? 'text' : 'password';

            const label = sedangTersembunyi
                ? butang.dataset.labelSembunyi
                : butang.dataset.labelTunjuk;

            butang.setAttribute('aria-pressed', sedangTersembunyi ? 'true' : 'false');
            butang.setAttribute('aria-label', label);
            butang.setAttribute('title', label);

            butang.querySelector('[data-ikon="tunjuk"]')?.classList.toggle('hidden', sedangTersembunyi);
            butang.querySelector('[data-ikon="sembunyi"]')?.classList.toggle('hidden', ! sedangTersembunyi);

            // Menukar type mengalihkan kursor ke permulaan, jadi ia dikembalikan
            // ke hujung supaya pengguna boleh terus menaip.
            const hujung = medan.value.length;

            medan.focus();
            medan.setSelectionRange(hujung, hujung);
        });
    });
}

/**
 * Menapis senarai sebab mengikut jenis pergerakan yang dipilih.
 *
 * Semua sebab dipaparkan dalam satu <select> dan ditanda dengan data-jenis,
 * bukan dibina semula daripada JSON. Dengan cara ini teksnya diterjemah oleh
 * Blade seperti teks lain di halaman, dan borang yang dihantar tanpa JavaScript
 * tetap membawa nilai yang sah.
 */
function mulakanSebabPergerakan() {
    document.querySelectorAll('[data-sebab]').forEach((sebab) => {
        const jenis = sebab.form?.querySelector('[name="jenis"]');

        if (! jenis) {
            return;
        }

        const segarkan = () => {
            let pertama = null;

            [...sebab.options].forEach((pilihan) => {
                const padan = pilihan.dataset.jenis === jenis.value;

                pilihan.hidden = ! padan;
                pilihan.disabled = ! padan;

                if (padan && pertama === null) {
                    pertama = pilihan;
                }
            });

            // Menetapkan .selected dan bukan select.value, kerana sebab yang
            // sama muncul di bawah lebih daripada satu jenis — menetapkan nilai
            // akan memilih kembaran pertamanya, yang mungkin yang tersembunyi.
            if (sebab.selectedOptions[0]?.disabled && pertama) {
                pertama.selected = true;
            }
        };

        jenis.addEventListener('change', segarkan);
        segarkan();
    });
}

/**
 * Pengimbas barcode dengan kamera peranti.
 *
 * Pencetus membawa data-imbas-barcode="<id medan>" dan membuka modal yang
 * dicipta oleh komponen <x-imbas-barcode>. Kod yang dijumpai ditulis ke dalam
 * medan itu, dan data-imbas-hantar menghantar borangnya terus — kerana pada
 * halaman carian, mengimbas dan kemudian menekan Cari ialah dua langkah untuk
 * satu niat.
 *
 * Butang bermula tersembunyi dalam HTML dan hanya didedahkan di sini. Pelayar
 * tanpa BarcodeDetector (Safari, Firefox) tidak sepatutnya menunjukkan butang
 * yang tidak akan berfungsi; pengimbas USB yang menaip ke dalam medan tetap
 * berjalan seperti biasa pada pelayar itu.
 */
function mulakanPengimbasBarcode() {
    const pencetus = document.querySelectorAll('[data-imbas-barcode]');

    if (pencetus.length === 0) {
        return;
    }

    const disokong = 'BarcodeDetector' in window
        && Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    if (! disokong) {
        return;
    }

    // Format satu dimensi ialah yang tercetak pada bungkusan runcit; QR
    // disertakan kerana label dalaman yang dicetak sendiri selalunya QR.
    const FORMAT = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'itf', 'qr_code'];
    const SELANG = 250; // Milisaat antara percubaan; setiap bingkai ialah kerja yang terbuang.

    pencetus.forEach((butang) => {
        const medan = document.getElementById(butang.dataset.imbasBarcode);
        const modal = document.getElementById(butang.dataset.modalBuka);

        if (! medan || ! modal) {
            return;
        }

        const video = modal.querySelector('[data-imbas-video]');
        const kotakRalat = modal.querySelector('[data-imbas-ralat]');
        const teksRalat = modal.querySelector('[data-imbas-ralat-teks]');

        let strim = null;
        let pemasa = null;

        butang.classList.remove('hidden');
        butang.addEventListener('click', mula);

        // Modal boleh ditutup melalui Escape, klik latar atau butang Batal.
        // Memerhati kelasnya bermakna kamera dimatikan pada ketiga-tiga cara.
        new MutationObserver(() => {
            if (modal.classList.contains('hidden')) {
                henti();
            }
        }).observe(modal, { attributes: true, attributeFilter: ['class'] });

        async function mula() {
            kotakRalat.classList.add('hidden');

            try {
                strim = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });

                video.srcObject = strim;

                const pengesan = new window.BarcodeDetector({ formats: FORMAT });

                pemasa = setInterval(() => kesan(pengesan), SELANG);
            } catch (ralat) {
                // Teks ralat dibawa sebagai data-* pada modal supaya JavaScript
                // ini tidak perlu tahu bahasa halaman.
                teksRalat.textContent = ralat.name === 'NotAllowedError'
                    ? modal.dataset.ralatDitolak
                    : modal.dataset.ralatGagal;
                kotakRalat.classList.remove('hidden');
            }
        }

        async function kesan(pengesan) {
            if (! video.videoWidth) {
                return;
            }

            try {
                const jumpa = await pengesan.detect(video);

                if (jumpa.length === 0) {
                    return;
                }

                medan.value = jumpa[0].rawValue;

                // Kod yang ditulis oleh JavaScript tidak mencetuskan peristiwa
                // input dengan sendirinya, jadi pendengar pada halaman —
                // seperti pemilih produk pada borang stok — tidak akan tahu.
                medan.dispatchEvent(new Event('input', { bubbles: true }));
                medan.dispatchEvent(new Event('change', { bubbles: true }));

                modal.classList.add('hidden');

                if (butang.dataset.imbasHantar) {
                    medan.form?.submit();
                }
            } catch {
                // Bingkai yang tidak dapat dibaca bukan ralat; bingkai
                // seterusnya datang dalam beberapa milisaat.
            }
        }

        function henti() {
            clearInterval(pemasa);
            pemasa = null;

            if (strim) {
                strim.getTracks().forEach((trek) => trek.stop());
                strim = null;
            }

            video.srcObject = null;
        }
    });
}

/**
 * Objek gudang 3D pada halaman auth condong mengikut kedudukan tetikus.
 *
 * Setiap objek membawa data-dalam, iaitu faktor kedalamannya. Objek yang
 * sepatutnya terasa lebih dekat bergerak lebih banyak daripada objek yang jauh
 * — itu yang menjadikannya parallax dan bukan sekadar empat benda bergoyang
 * serentak dengan sudut yang sama.
 *
 * Sudut yang dikira tidak digunakan terus. Nilai semasa dihanyutkan ke arah
 * sasaran sedikit demi sedikit pada setiap bingkai, kerana melompat terus ke
 * sudut tetikus menjadikan objek tersentak-sentak mengikut setiap gerakan kecil
 * dan bukan bergerak seperti benda yang ada berat.
 */
function mulakanHiasanParallax() {
    const objek = [...document.querySelectorAll('.objek-condong')];

    if (objek.length === 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const HAD = 14;      // Sudut condong maksimum, dalam darjah.
    const CONDONG = -14; // Sudut rehat, sama seperti yang ditetapkan dalam CSS.
    const HANYUT = 0.07; // Pecahan jarak yang ditempuh setiap bingkai.

    // Faktor kedalaman dibaca sekali di sini dan bukan pada setiap bingkai;
    // membaca atribut DOM dalam gelung animasi ialah kerja yang berulang tanpa
    // sebab, kerana nilainya tidak pernah berubah.
    const dalam = objek.map((el) => parseFloat(el.dataset.dalam) || 1);

    let sasarX = 0;
    let sasarY = 0;
    let semasaX = 0;
    let semasaY = 0;
    let menungguBingkai = false;

    window.addEventListener('mousemove', (peristiwa) => {
        // -1 hingga 1 merentas tetingkap, jadi tengah skrin bermakna tiada condong.
        sasarY = ((peristiwa.clientX / window.innerWidth) * 2 - 1) * HAD;
        sasarX = ((peristiwa.clientY / window.innerHeight) * 2 - 1) * -HAD;

        if (! menungguBingkai) {
            menungguBingkai = true;
            requestAnimationFrame(lukis);
        }
    }, { passive: true });

    function lukis() {
        semasaX += (sasarX - semasaX) * HANYUT;
        semasaY += (sasarY - semasaY) * HANYUT;

        objek.forEach((el, i) => {
            el.style.transform =
                `rotateX(${CONDONG + semasaX * dalam[i]}deg) rotateY(${semasaY * dalam[i]}deg)`;
        });

        // Gelung berhenti apabila objek sudah cukup hampir dengan sasarannya,
        // supaya tiada bingkai dikira selagi tetikus tidak bergerak.
        if (Math.abs(sasarX - semasaX) > 0.02 || Math.abs(sasarY - semasaY) > 0.02) {
            requestAnimationFrame(lukis);

            return;
        }

        menungguBingkai = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    mulakanMenuJatuh();
    mulakanModal();
    mulakanAmaran();
    mulakanTunjukKataLaluan();
    mulakanSebabPergerakan();
    mulakanPengimbasBarcode();
    mulakanHiasanParallax();

    // Modal stok pantas dibuka semula selepas ralat pengesahan supaya input kekal kelihatan.
    const modalAuto = document.querySelector('[data-modal-auto]');

    if (modalAuto) {
        bukaModal(modalAuto.id);
    }
});
