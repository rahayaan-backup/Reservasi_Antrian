@php
    $isAdmin = request()->routeIs('admin.*');
    $isCs = request()->routeIs('cs.*');

    if ($isCs) {
        $badgeStatusCs = \App\Models\Reservasi::query()
            ->whereHas('jadwal', fn ($q) => $q->whereDate('tanggal', today()))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $jumlahBelumSinkronFisik = \App\Models\Reservasi::query()
            ->where('status_sinkron_fisik', \App\Enums\StatusSinkronFisik::BelumDisinkronkan)
            ->where('status', \App\Enums\ReservasiStatus::PerluDatang)
            ->count();

        // Menu sidebar Reservasi menyaring berdasarkan kombinasi query
        // string "tab" + "status" — setiap item harus dicek presisi
        // kombinasinya, bukan cuma prefix route, supaya highlight biru
        // tepat sasaran (satu item aktif dalam satu waktu).
        $tabAktifSaatIni = request()->query('tab', 'aktif');
        $statusAktifSaatIni = request()->query('status', '');
        $diHalamanDaftarReservasi = request()->routeIs('cs.reservasi.index');

        $isDaftarReservasiAktif = $diHalamanDaftarReservasi
            && $tabAktifSaatIni === 'aktif'
            && $statusAktifSaatIni === '';

        $isMenungguReviewAktif = $diHalamanDaftarReservasi
            && $tabAktifSaatIni === 'aktif'
            && $statusAktifSaatIni === 'menunggu_review';

        $isPerluDatangAktif = $diHalamanDaftarReservasi
            && $tabAktifSaatIni === 'aktif'
            && $statusAktifSaatIni === 'perlu_datang';

        $isSelesaiOnlineAktif = $diHalamanDaftarReservasi
            && $tabAktifSaatIni === 'riwayat'
            && $statusAktifSaatIni === 'selesai_online';

        $isSelesaiAktif = $diHalamanDaftarReservasi
            && $tabAktifSaatIni === 'riwayat'
            && $statusAktifSaatIni === 'selesai';

        $isDibatalkanAktif = $diHalamanDaftarReservasi
            && $tabAktifSaatIni === 'riwayat'
            && $statusAktifSaatIni === 'dibatalkan';

        $isRiwayatReservasiAktif = $diHalamanDaftarReservasi
            && $tabAktifSaatIni === 'riwayat'
            && $statusAktifSaatIni === '';
    }
@endphp

<aside
    id="dashboard-sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col overflow-y-auto border-r border-pln-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
>
	<div class="flex h-16 items-center gap-3 px-5">

		<img
			src="{{ asset('images/logo-pln.png') }}"
			alt="Logo PLN"
			class="h-10 w-10 object-contain"
		>

		<div>
			<p class="text-xs text-pln-slate-500">
				Sistem Reservasi Antrian ULP Manado Selatan
			</p>
		</div>

	</div>

    <nav class="flex-1 space-y-6 px-3 py-4" aria-label="Navigasi dashboard">

        @if ($isAdmin)
            <x-dashboard.nav-group title="Menu Utama">
                <x-dashboard.nav-item
                    :href="route('admin.dashboard')"
                    icon="check-circle"
                    :active="request()->routeIs('admin.dashboard')"
                >
                    Dashboard
                </x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('admin.reservasi.index')"
					icon="clipboard-list"
					:active="request()->routeIs('admin.reservasi.*')"
				>
					Reservasi
				</x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('admin.kalender.index')"
					icon="calendar"
					:active="request()->routeIs('admin.kalender.*')"
				>
					Kalender Jadwal
				</x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('admin.laporan.index')"
					icon="chart-bar"
					:active="request()->routeIs('admin.laporan.*')"
				>
					Laporan
				</x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('admin.pengguna.index')"
					icon="users"
					:active="request()->routeIs('admin.pengguna.*')"
				>
					Pengguna
				</x-dashboard.nav-item>
            </x-dashboard.nav-group>

            <x-dashboard.nav-group title="Pengelolaan">
                <x-dashboard.nav-item
                    :href="Route::has('admin.layanan.index') ? route('admin.layanan.index') : null"
                    icon="bolt"
                    :active="request()->routeIs('admin.layanan.*')"
                    :disabled="! Route::has('admin.layanan.index')"
                >
                    Layanan
                </x-dashboard.nav-item>

                <x-dashboard.nav-item
                    :href="Route::has('admin.jadwal.index') ? route('admin.jadwal.index') : null"
                    icon="ticket"
                    :active="request()->routeIs('admin.jadwal.*')"
                    :disabled="! Route::has('admin.jadwal.index')"
                >
                    Jadwal &amp; Kuota
                </x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('admin.pengumuman.index')"
					icon="megaphone"
					:active="request()->routeIs('admin.pengumuman.*')"
				>
					Pengumuman
				</x-dashboard.nav-item>
            </x-dashboard.nav-group>

            <x-dashboard.nav-group title="Pengaturan">
				<x-dashboard.nav-item
					:href="route('admin.pengaturan.index')"
					icon="cog"
					:active="request()->routeIs('admin.pengaturan.*')"
				>
					Pengaturan Sistem
				</x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('admin.profil.index')"
					icon="user"
					:active="request()->routeIs('admin.profil.*')"
				>
					Profil Saya
				</x-dashboard.nav-item>
            </x-dashboard.nav-group>
        @elseif ($isCs)
            <x-dashboard.nav-group title="Menu Utama">
                <x-dashboard.nav-item
                    :href="route('cs.dashboard')"
                    icon="check-circle"
                    :active="request()->routeIs('cs.dashboard')"
                >
                    Dashboard
                </x-dashboard.nav-item>
            </x-dashboard.nav-group>

            <x-dashboard.nav-group title="Reservasi">
                <x-dashboard.nav-item
                    :href="route('cs.reservasi.index')"
                    icon="clipboard-list"
                    :active="$isDaftarReservasiAktif"
                >
                    Daftar Reservasi
                </x-dashboard.nav-item>

                <x-dashboard.nav-item
                    :href="route('cs.reservasi.index', ['tab' => 'aktif', 'status' => 'menunggu_review'])"
                    icon="clock"
                    :active="$isMenungguReviewAktif"
                    :badge="$badgeStatusCs['menunggu_review'] ?? 0"
                >
                    Menunggu Review
                </x-dashboard.nav-item>

                <x-dashboard.nav-item
                    :href="route('cs.reservasi.index', ['tab' => 'aktif', 'status' => 'perlu_datang'])"
                    icon="walking"
                    :active="$isPerluDatangAktif"
                    :badge="$badgeStatusCs['perlu_datang'] ?? 0"
                >
                    Perlu Datang
                </x-dashboard.nav-item>
				
				<x-dashboard.nav-item
					:href="route('cs.reservasi.belum-dicetak-fisik')"
					icon="exclamation-triangle"
					:active="request()->routeIs('cs.reservasi.belum-dicetak-fisik')"
					:badge="$jumlahBelumSinkronFisik ?? 0"
				>
					Belum Dicetak Fisik
				</x-dashboard.nav-item>

                <x-dashboard.nav-item
                    :href="route('cs.reservasi.index', ['tab' => 'riwayat', 'status' => 'selesai_online'])"
                    icon="check"
                    :active="$isSelesaiOnlineAktif"
                    :badge="$badgeStatusCs['selesai_online'] ?? 0"
                >
                    Selesai Online
                </x-dashboard.nav-item>

                <x-dashboard.nav-item
                    :href="route('cs.reservasi.index', ['tab' => 'riwayat', 'status' => 'selesai'])"
                    icon="check-circle"
                    :active="$isSelesaiAktif"
                    :badge="$badgeStatusCs['selesai'] ?? 0"
                >
                    Selesai
                </x-dashboard.nav-item>

                <x-dashboard.nav-item
                    :href="route('cs.reservasi.index', ['tab' => 'riwayat', 'status' => 'dibatalkan'])"
                    icon="x-mark"
                    :active="$isDibatalkanAktif"
                    :badge="$badgeStatusCs['dibatalkan'] ?? 0"
                >
                    Dibatalkan
                </x-dashboard.nav-item>
            </x-dashboard.nav-group>

            <x-dashboard.nav-group title="Lainnya">
				<x-dashboard.nav-item
					:href="route('cs.kalender.index')"
					icon="calendar"
					:active="request()->routeIs('cs.kalender.*')"
				>
					Kalender Jadwal
				</x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('cs.reservasi.index', ['tab' => 'riwayat'])"
					icon="clipboard-list"
					:active="$isRiwayatReservasiAktif"
				>
					Riwayat Reservasi
				</x-dashboard.nav-item>
            </x-dashboard.nav-group>

            <x-dashboard.nav-group title="Pengaturan">
				<x-dashboard.nav-item
					:href="route('cs.profil.index')"
					icon="user"
					:active="request()->routeIs('cs.profil.*')"
				>
					Profil Saya
				</x-dashboard.nav-item>

				<x-dashboard.nav-item
					:href="route('cs.panduan.index')"
					icon="headphones"
					:active="request()->routeIs('cs.panduan.*')"
				>
					Panduan
				</x-dashboard.nav-item>
            </x-dashboard.nav-group>
        @else
            <x-dashboard.nav-group title="Sistem">
                <x-dashboard.nav-item
                    :href="route('system.error-demo')"
                    icon="x-mark"
                    :active="request()->routeIs('system.error-demo')"
                >
                    Contoh Halaman Error
                </x-dashboard.nav-item>
            </x-dashboard.nav-group>
        @endif

    </nav>

    <div class="border-t border-pln-slate-200 p-4">
        <div class="rounded-xl bg-pln-navy-900/5 p-4">
            <p class="text-sm font-semibold text-pln-navy-900">Butuh Bantuan?</p>
            <p class="mt-1 text-xs leading-relaxed text-pln-slate-500">
                Hubungi Contact Center PLN 123.
            </p>
            
                href="tel:123"
                class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-pln-navy-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-pln-navy-800"
            >
                <x-icon name="phone" class="h-3.5 w-3.5" />
                PLN 123
            </a>
        </div>
    </div>
</aside>
