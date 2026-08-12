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

document.addEventListener('DOMContentLoaded', () => {
    mulakanMenuJatuh();
    mulakanModal();
    mulakanAmaran();
    mulakanTunjukKataLaluan();

    // Modal stok pantas dibuka semula selepas ralat pengesahan supaya input kekal kelihatan.
    const modalAuto = document.querySelector('[data-modal-auto]');

    if (modalAuto) {
        bukaModal(modalAuto.id);
    }
});
