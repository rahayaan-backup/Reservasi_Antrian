@props(['reservasi'])
@php
    $ikonLayanan = match ($reservasi->layanan->kode_layanan) {
        'A' => 'bolt',
        'B' => 'document-text',
        default => 'wrench-screwdriver',
    };

    // Komponen ini dipakai bersama oleh halaman Admin & CS — link "Lihat
    // Detail" harus menyesuaikan konteks yang sedang aktif, bukan
    // hardcode ke satu route saja, supaya masing-masing area mengarah ke
    // halaman detail miliknya sendiri (dengan guard middleware yang benar).
    $routeDetail = request()->routeIs('admin.*') ? 'admin.reservasi.show' : 'cs.reservasi.show';
    $adaRouteDetail = Route::has($routeDetail);

    $petugas = $reservasi->statusHistories->first()?->petugas?->nama_petugas;
@endphp
<tr class="border-b border-pln-slate-100 last:border-0">
    <td class="whitespace-nowrap px-4 py-3 font-mono text-sm font-semibold text-pln-navy-900">
        {{ $reservasi->nomor_antrean }}
    </td>
    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-pln-slate-500">
        {{ $reservasi->kode_reservasi }}
    </td>
    <td class="px-4 py-3">
        <p class="text-sm font-medium text-pln-slate-900">{{ $reservasi->nama }}</p>
        <p class="text-xs text-pln-slate-400">{{ $reservasi->nomor_hp }}</p>
    </td>
    <td class="whitespace-nowrap px-4 py-3">
        <span class="flex items-center gap-2 text-sm text-pln-slate-700">
            <x-icon :name="$ikonLayanan" class="h-4 w-4 text-pln-slate-400" />
            {{ $reservasi->layanan->nama_layanan }}
        </span>
    </td>
    <td class="whitespace-nowrap px-4 py-3 text-sm text-pln-slate-700">
        {{ $reservasi->jadwal->tanggal->translatedFormat('d M Y') }}
        <span class="block text-xs text-pln-slate-400">
            {{ substr($reservasi->jadwal->jam_mulai, 0, 5) }} - {{ substr($reservasi->jadwal->jam_selesai, 0, 5) }}
        </span>
    </td>
    <td class="whitespace-nowrap px-4 py-3">
        <x-badge :variant="$reservasi->status->badgeVariant()">{{ $reservasi->status->label() }}</x-badge>
    </td>
    <td class="whitespace-nowrap px-4 py-3 text-sm text-pln-slate-600">
        {{ $petugas ?? '-' }}
    </td>
    <td class="whitespace-nowrap px-4 py-3 text-right">
        @if ($adaRouteDetail)
            <a href="{{ route($routeDetail, $reservasi) }}" class="text-sm font-semibold text-pln-navy-700 hover:text-pln-navy-900">
                Lihat Detail
            </a>
        @else
            <span class="text-sm font-semibold text-pln-slate-300" title="Halaman Detail Reservasi akan tersedia pada sprint berikutnya">
                Lihat Detail
            </span>
        @endif
    </td>
</tr>
