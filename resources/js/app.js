import './bootstrap';

/**
 * Modal handler (dipakai di layout dashboard & halaman detail reservasi pelanggan).
 */
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-target]');
    if (opener) {
        const modal = document.getElementById(opener.dataset.modalTarget);
        modal?.showModal();
    }

    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
        closer.closest('dialog')?.close();
    }

    const dialog = event.target.closest('dialog');
    if (dialog && event.target === dialog) {
        dialog.close();
    }
});

/**
 * Sidebar toggle (mobile) — layout dashboard.
 */
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebar = document.getElementById('dashboard-sidebar');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');

sidebarToggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('-translate-x-full');
    sidebarOverlay?.classList.toggle('hidden');
});

sidebarOverlay?.addEventListener('click', () => {
    sidebar?.classList.add('-translate-x-full');
    sidebarOverlay?.classList.add('hidden');
});

/**
 * Mobile nav toggle — layout public.
 */
const mobileNavToggle = document.querySelector('[data-mobile-nav-toggle]');
const mobileNav = document.getElementById('mobile-nav');

mobileNavToggle?.addEventListener('click', () => {
    const isHidden = mobileNav?.classList.toggle('hidden');
    mobileNavToggle.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
});

document.querySelectorAll('#mobile-nav a').forEach((link) => {
    link.addEventListener('click', () => {
        mobileNav?.classList.add('hidden');
        mobileNavToggle?.setAttribute('aria-expanded', 'false');
    });
});

/**
 * Back to top button — layout public.
 */
const backToTopButton = document.querySelector('[data-back-to-top]');

if (backToTopButton) {
    const toggleBackToTop = () => {
        if (window.scrollY > 400) {
            backToTopButton.classList.remove('hidden');
            backToTopButton.classList.add('flex');
        } else {
            backToTopButton.classList.add('hidden');
            backToTopButton.classList.remove('flex');
        }
    };

    window.addEventListener('scroll', toggleBackToTop, { passive: true });
    toggleBackToTop();

    backToTopButton.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/**
 * Character counter generik untuk textarea mana pun.
 */
document.querySelectorAll('[data-char-count-target]').forEach((textarea) => {
    const key = textarea.dataset.charCountTarget;
    const counter = document.querySelector(`[data-char-counter="${key}"]`);

    if (!counter) {
        return;
    }

    const max = textarea.getAttribute('maxlength') ?? '';

    const updateCounter = () => {
        counter.textContent = max ? `${textarea.value.length} / ${max}` : `${textarea.value.length}`;
    };

    textarea.addEventListener('input', updateCounter);
    updateCounter();
});

/**
 * File upload preview (halaman Reservasi pelanggan).
 */
document.querySelectorAll('[data-file-upload]').forEach((wrapper) => {
    const input = wrapper.querySelector('[data-file-upload-input]');
    const list = wrapper.querySelector('[data-file-upload-list]');

    if (!input || !list) {
        return;
    }

    input.addEventListener('change', () => {
        list.innerHTML = '';

        Array.from(input.files ?? []).forEach((file) => {
            const item = document.createElement('li');
            item.className = 'flex items-center gap-2 rounded-lg bg-pln-slate-50 px-3 py-2 text-sm text-pln-slate-700';

            const sizeKb = Math.round(file.size / 1024);
            item.innerHTML = `
                <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-pln-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 4h8l4 4v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                </svg>
                <span class="flex-1 truncate">${file.name}</span>
                <span class="shrink-0 text-xs text-pln-slate-400">${sizeKb} KB</span>
            `;

            list.appendChild(item);
        });
    });
});

/**
 * Fetch jadwal tersedia berdasarkan jenis layanan + tanggal.
 * Dipakai oleh Form Reservasi (create) dan Form Ubah Jadwal.
 */
const layananOptions = document.querySelectorAll('[data-layanan-option]');
const tanggalInput = document.querySelector('[data-tanggal-input]');
const jadwalSelect = document.querySelector('[data-jadwal-select]');

if (layananOptions.length && tanggalInput && jadwalSelect) {
    const config = window.reservasiConfig ?? {};

    const getSelectedLayananId = () => {
        const checked = document.querySelector('input[name="layanan_id"]:checked');
        return checked ? checked.value : null;
    };

    const muatJadwalTersedia = async () => {
        const layananId = getSelectedLayananId();
        const tanggal = tanggalInput.value;

        jadwalSelect.innerHTML = '<option value="">Memuat jadwal...</option>';
        jadwalSelect.disabled = true;

        if (!layananId || !tanggal) {
            jadwalSelect.innerHTML = '<option value="">Pilih jenis layanan &amp; tanggal dahulu</option>';
            return;
        }

        try {
            const url = new URL(config.jadwalTersediaUrl, window.location.origin);
            url.searchParams.set('layanan_id', layananId);
            url.searchParams.set('tanggal', tanggal);

            if (config.kecualiJadwalId) {
                url.searchParams.set('kecuali_jadwal_id', config.kecualiJadwalId);
            }

            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            const result = await response.json();

            jadwalSelect.innerHTML = '';
            jadwalSelect.disabled = false;

            if (!result.data || result.data.length === 0) {
                jadwalSelect.innerHTML = '<option value="">Tidak ada jadwal tersedia pada tanggal ini</option>';
                return;
            }

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Pilih jam kedatangan';
            jadwalSelect.appendChild(defaultOption);

            result.data.forEach((jadwal) => {
                const option = document.createElement('option');
                option.value = jadwal.id;
                option.textContent = `${jadwal.label} (sisa ${jadwal.sisa_kuota} slot)`;

                if (config.oldJadwalId && String(config.oldJadwalId) === String(jadwal.id)) {
                    option.selected = true;
                }

                jadwalSelect.appendChild(option);
            });
        } catch (error) {
            jadwalSelect.innerHTML = '<option value="">Gagal memuat jadwal, silakan coba lagi</option>';
            jadwalSelect.disabled = false;
        }
    };

    layananOptions.forEach((option) => {
        option.addEventListener('change', muatJadwalTersedia);
    });

    tanggalInput.addEventListener('change', muatJadwalTersedia);

    if (getSelectedLayananId() && tanggalInput.value) {
        muatJadwalTersedia();
    }
}

/**
 * Collapse/expand konten pada mobile (halaman Detail Reservasi).
 */
document.querySelectorAll('[data-toggle-target]').forEach((button) => {
    const target = document.getElementById(button.dataset.toggleTarget);
    const icon = button.querySelector('[data-toggle-icon]');

    if (!target) {
        return;
    }

    button.addEventListener('click', () => {
        const isHidden = target.classList.toggle('hidden');
        button.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
        icon?.classList.toggle('rotate-180', isHidden);
    });
});

/**
 * "Lihat Semua" dokumen (halaman Detail Reservasi).
 */
document.querySelectorAll('[data-dokumen-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('div');
        const hiddenItems = card?.parentElement?.querySelectorAll('.hidden') ?? [];

        hiddenItems.forEach((item) => item.classList.remove('hidden'));
        button.remove();
    });
});

/**
 * Toggle tampilkan/sembunyikan password (halaman Login).
 * Tombol [data-password-toggle] mengubah type input [data-password-input]
 * antara "password" dan "text", serta menukar ikon mata terbuka/tercoret.
 */
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const wrapper = button.closest('.relative');
    const input = wrapper?.querySelector('[data-password-input]');
    const iconShow = button.querySelector('[data-password-icon-show]');
    const iconHide = button.querySelector('[data-password-icon-hide]');

    if (!input) {
        return;
    }

    button.addEventListener('click', () => {
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        iconShow?.classList.toggle('hidden', isHidden);
        iconHide?.classList.toggle('hidden', !isHidden);
    });
});

/**
 * Notifikasi real-time reservasi baru untuk Customer Service.
 * Polling ringan (fetch berkala) ke endpoint cek-reservasi-baru — tanpa
 * WebSocket/Pusher, cukup untuk skala CS memantau dari satu tab dashboard
 * yang selalu terbuka. Suara dibangkitkan langsung di browser (Web Audio
 * API), tidak perlu file audio terpisah.
 */
if (window.notifikasiReservasiConfig) {
    const config = window.notifikasiReservasiConfig;
    let idTerakhirDiketahui = null;
    let audioContext = null;

    /**
     * AudioContext browser modern butuh "izin" dari interaksi pengguna
     * dulu (klik/tap) sebelum bisa memutar suara — ini batasan standar
     * browser untuk mencegah situs memutar suara otomatis tanpa izin.
     * Kita siapkan context begitu CS melakukan klik pertama di halaman.
     */
	function siapkanAudioContext() {
		if (!audioContext) {
			const AudioContextClass = window.AudioContext || window.webkitAudioContext;
			audioContext = new AudioContextClass();
		}

		if (audioContext.state === 'suspended') {
			audioContext.resume();
		}

		const indikator = document.getElementById('notifikasi-suara-indikator');
		indikator?.classList.add('hidden');
	}

    ['click', 'keydown', 'touchstart'].forEach((eventName) => {
        document.addEventListener(eventName, siapkanAudioContext, { once: true });
    });
    
    /**
     * Browser menahan (throttle) AudioContext di tab yang sedang tidak aktif
     * / di-minimize, sebagai bagian dari kebijakan hemat daya. Begitu CS
     * kembali ke tab ini, pastikan context "dibangunkan" lagi supaya siap
     * memutar suara saat notifikasi berikutnya datang — tanpa ini, suara
     * akan tetap diam meski badge & toast tetap muncul normal.
     */
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && audioContext?.state === 'suspended') {
            audioContext.resume();
        }
    });
    /**
     * Bunyikan nada "ding-dong" dua nada sederhana menggunakan oscillator,
     * tanpa perlu file .mp3/.wav apa pun.
     */
	function bunyikanNotifikasi() {
		if (!audioContext) {
			return;
		}

		const mainkanSekarang = () => {
			const mainkanNada = (frekuensi, waktuMulai, durasi) => {
				const oscillator = audioContext.createOscillator();
				const gain = audioContext.createGain();

				oscillator.type = 'sine';
				oscillator.frequency.value = frekuensi;

				gain.gain.setValueAtTime(0, audioContext.currentTime + waktuMulai);
				gain.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + waktuMulai + 0.02);
				gain.gain.linearRampToValueAtTime(0, audioContext.currentTime + waktuMulai + durasi);

				oscillator.connect(gain);
				gain.connect(audioContext.destination);

				oscillator.start(audioContext.currentTime + waktuMulai);
				oscillator.stop(audioContext.currentTime + waktuMulai + durasi);
			};

			mainkanNada(880, 0, 0.15);
			mainkanNada(660, 0.15, 0.25);
		};

		// AudioContext bisa "tertidur" lagi (state jadi suspended) kalau tab
		// sempat tidak fokus / idle — browser modern melakukan ini otomatis
		// untuk hemat daya. Maka setiap kali mau bunyi, pastikan dulu context
		// sedang aktif; kalau tidak, resume dulu baru mainkan nadanya.
		if (audioContext.state === 'suspended') {
			audioContext.resume().then(mainkanSekarang);
		} else {
			mainkanSekarang();
		}
	}

    /**
     * Buat (kalau belum ada) container toast di pojok kanan atas halaman.
     */
    function ambilContainerToast() {
        let container = document.getElementById('notifikasi-toast-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'notifikasi-toast-container';
            container.className = 'fixed right-4 top-20 z-50 flex w-full max-w-sm flex-col gap-3';
            document.body.appendChild(container);
        }

        return container;
    }

    /**
     * Tampilkan satu kartu toast untuk reservasi baru, otomatis hilang
     * setelah beberapa detik, atau bisa diklik untuk langsung menuju
     * halaman detail reservasi tersebut.
     */
    function tampilkanToast(reservasi) {
        const container = ambilContainerToast();

        const toast = document.createElement('a');
        toast.href = reservasi.url_detail;
        toast.className = 'flex items-start gap-3 rounded-xl border border-pln-amber-500/40 bg-white p-4 shadow-lg transition hover:border-pln-amber-500 animate-[toast-masuk_0.3s_ease-out]';
        toast.innerHTML = `
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-pln-amber-500 text-white">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 8a6 6 0 1 1 12 0c0 4 1.5 5.5 1.5 5.5H4.5S6 12 6 8Z" />
                    <path stroke-linecap="round" d="M10 19a2 2 0 0 0 4 0" />
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-pln-navy-900">Reservasi Baru Masuk</p>
                <p class="mt-0.5 truncate text-sm text-pln-slate-700">
                    <span class="font-mono font-semibold">${reservasi.nomor_antrean}</span> &middot; ${reservasi.nama}
                </p>
                <p class="text-xs text-pln-slate-400">${reservasi.layanan} &middot; ${reservasi.dibuat_pada} WITA</p>
            </div>
        `;

        container.prepend(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease-out';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 8000);
    }

    /**
     * Perbarui angka badge lonceng di topbar tanpa reload halaman.
     */
    function perbaruiBadgeBel(jumlah) {
        const badge = document.getElementById('notifikasi-bell-badge');

        if (!badge) {
            return;
        }

        if (jumlah > 0) {
            badge.textContent = jumlah > 99 ? '99+' : jumlah;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    async function cekReservasiBaru() {
        try {
            const url = new URL(config.cekUrl, window.location.origin);

            if (idTerakhirDiketahui !== null) {
                url.searchParams.set('sejak_id', idTerakhirDiketahui);
            }

            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const hasil = await response.json();

            if (!hasil.success) {
                return;
            }

            const { id_terakhir, total_menunggu_review, reservasi_baru } = hasil.data;

            perbaruiBadgeBel(total_menunggu_review);

            if (idTerakhirDiketahui !== null && reservasi_baru.length > 0) {
                bunyikanNotifikasi();
                reservasi_baru.forEach((reservasi) => tampilkanToast(reservasi));
            }

            idTerakhirDiketahui = id_terakhir;
        } catch (error) {
            // Diam-diam gagal (mis. koneksi terputus sesaat) — polling
            // berikutnya akan otomatis mencoba lagi, tidak perlu
            // mengganggu CS dengan pesan error setiap kali jaringan hiccup.
        }
    }

    cekReservasiBaru();
    setInterval(cekReservasiBaru, config.intervalMs);
}
