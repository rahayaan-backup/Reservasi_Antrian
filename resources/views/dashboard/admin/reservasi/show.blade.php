@extends('layouts.dashboard')

@section('title', 'Detail Reservasi')
@section('page-title', 'Detail Reservasi')
@section('page-subtitle', 'Dashboard > Reservasi > Detail Reservasi')
@section('user-initial', 'A')
@section('user-name', 'Admin PLN')
@section('user-role', 'Administrator')

@section('content')

    <div class="space-y-6">

        <x-reservasi.breadcrumb :items="[
            ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
            ['label' => 'Reservasi', 'href' => route('admin.reservasi.index')],
            ['label' => 'Detail Reservasi'],
        ]" />

        {{-- Kartu Nomor Antrean --}}
        <x-card padding="p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-pln-navy-700">Nomor Antrean</p>
                    <p class="mt-1 font-mono text-4xl font-bold text-pln-navy-950">
                        {{ substr($reservasi->nomor_antrean, 0, 1) }}-{{ substr($reservasi->nomor_antrean, 1) }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <x-badge :variant="$reservasi->status->badgeVariant()" class="text-sm">
                            {{ $reservasi->status->label() }}
                        </x-badge>
                        <span class="text-sm text-pln-slate-500">Reservasi bersifat read-only di panel Admin</span>
                    </div>
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-pln-slate-500">
                        <x-icon name="calendar" class="h-4 w-4" />
                        {{ $reservasi->jadwal->tanggal->translatedFormat('d M Y') }} &middot;
                        {{ substr($reservasi->jadwal->jam_mulai, 0, 5) }} - {{ substr($reservasi->jadwal->jam_selesai, 0, 5) }}
                    </p>
                </div>

                <div class="flex shrink-0 gap-3">
                    
                        href="{{ route('admin.reservasi.export', ['status' => $reservasi->status->value]) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-pln-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-pln-navy-800 transition hover:bg-pln-slate-50"
                    >
                        <x-icon name="download" class="h-4 w-4" />
                        Unduh Bukti
                    </a>
                </div>
            </div>

            <div class="mt-6 border-t border-pln-slate-100 pt-6">
                <x-cs-reservasi.status-progress :reservasi="$reservasi" />

                @if ($reservasi->status_sinkron_fisik !== \App\Enums\StatusSinkronFisik::TidakPerlu)
                    <div class="mt-5 rounded-lg border border-pln-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-pln-slate-400">Sinkronisasi Mesin Fisik</p>
                        <div class="mt-1 flex items-center gap-2">
                            <x-badge :variant="$reservasi->status_sinkron_fisik->badgeVariant()">
                                {{ $reservasi->status_sinkron_fisik->label() }}
                            </x-badge>
                            @if ($reservasi->status_sinkron_fisik === \App\Enums\StatusSinkronFisik::SudahDisinkronkan && $reservasi->disinkronkan_pada)
                                <span class="text-xs text-pln-slate-400">
                                    {{ $reservasi->disinkronkan_pada->translatedFormat('d M Y, H:i') }}
                                    @if ($reservasi->disinkronkanOleh)
                                        oleh {{ $reservasi->disinkronkanOleh->nama_petugas }}
                                    @else
                                        (terdeteksi otomatis)
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </x-card>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Informasi Pelanggan --}}
            <x-card padding="p-6">
                <x-slot:header>
                    <h2 class="flex items-center gap-2 font-display text-base font-semibold text-pln-navy-900">
                        <x-icon name="user" class="h-5 w-5 text-pln-navy-700" />
                        Informasi Pelanggan
                    </h2>
                </x-slot:header>

                <div class="space-y-4">
                    <x-reservasi.info-row icon="user" label="Nama Lengkap" :value="$reservasi->nama" />
                    <x-reservasi.info-row icon="phone" label="Nomor HP" :value="$reservasi->nomor_hp" />
                    <x-reservasi.info-row icon="envelope" label="Email" :value="$reservasi->email ?? '-'" />
                </div>
            </x-card>

            {{-- Informasi Reservasi --}}
            <x-card padding="p-6">
                <x-slot:header>
                    <h2 class="flex items-center gap-2 font-display text-base font-semibold text-pln-navy-900">
                        <x-icon name="document-text" class="h-5 w-5 text-pln-navy-700" />
                        Informasi Reservasi
                    </h2>
                </x-slot:header>

                <div class="space-y-4">
                    <x-reservasi.info-row icon="ticket" label="Kode Reservasi" :value="$reservasi->kode_reservasi" />
                    <x-reservasi.info-row icon="bolt" label="Jenis Layanan" :value="$reservasi->layanan->nama_layanan" />
                    <x-reservasi.info-row icon="calendar" label="Tanggal Reservasi" :value="$reservasi->jadwal->tanggal->translatedFormat('d M Y')" />
                    <x-reservasi.info-row icon="clock" label="Jam Kedatangan" :value="substr($reservasi->jadwal->jam_mulai, 0, 5) . ' - ' . substr($reservasi->jadwal->jam_selesai, 0, 5)" />
                    <x-reservasi.info-row icon="clock" label="Dibuat Pada" :value="$reservasi->created_at->translatedFormat('d M Y - H:i') . ' WITA'" />
                    <x-reservasi.info-row icon="user" label="Ditangani Oleh" :value="optional($reservasi->statusHistories->last())->petugas?->nama_petugas ?? '-'" />
                </div>
            </x-card>

            {{-- Keluhan --}}
            <x-card padding="p-6">
                <x-slot:header>
                    <h2 class="flex items-center gap-2 font-display text-base font-semibold text-pln-navy-900">
                        <x-icon name="document-text" class="h-5 w-5 text-pln-navy-700" />
                        Keluhan / Keterangan
                    </h2>
                </x-slot:header>

                <p class="text-sm leading-relaxed text-pln-slate-700">{{ $reservasi->keluhan }}</p>
            </x-card>

        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Dokumen --}}
            <x-card padding="p-6">
                <x-slot:header>
                    <h2 class="flex items-center gap-2 font-display text-base font-semibold text-pln-navy-900">
                        <x-icon name="document-text" class="h-5 w-5 text-pln-navy-700" />
                        Dokumen yang Diunggah
                    </h2>
                </x-slot:header>

                @if ($reservasi->dokumen->isEmpty())
                    <x-empty-state
                        title="Belum ada dokumen"
                        description="Pelanggan tidak mengunggah dokumen pendukung saat membuat reservasi ini."
                    />
                @else
                    <div class="space-y-2.5">
                        @foreach ($reservasi->dokumen as $dokumen)
                            <x-cs-reservasi.document-item :dokumen="$dokumen" :reservasi="$reservasi" />
                        @endforeach
                    </div>
                @endif
            </x-card>

            {{-- Riwayat Status --}}
            <x-card padding="p-6">
                <x-slot:header>
                    <h2 class="flex items-center gap-2 font-display text-base font-semibold text-pln-navy-900">
                        <x-icon name="clock" class="h-5 w-5 text-pln-navy-700" />
                        Riwayat Status
                    </h2>
                </x-slot:header>

                <x-reservasi.status-timeline :reservasi="$reservasi" />
            </x-card>

        </div>

        {{-- Riwayat Catatan (read-only, tanpa form tambah catatan) --}}
        <x-card padding="p-6">
            <x-slot:header>
                <h2 class="flex items-center gap-2 font-display text-base font-semibold text-pln-navy-900">
                    <x-icon name="document-text" class="h-5 w-5 text-pln-navy-700" />
                    Catatan Customer Service
                </h2>
            </x-slot:header>

            @if ($reservasi->notes->isEmpty())
                <p class="text-sm text-pln-slate-400">Belum ada catatan untuk reservasi ini.</p>
            @else
                <div class="space-y-3">
                    @foreach ($reservasi->notes as $note)
                        <x-cs-reservasi.note-item :note="$note" />
                    @endforeach
                </div>
            @endif
        </x-card>

        <div>
            <x-button href="{{ route('admin.reservasi.index') }}" variant="ghost" size="md">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Daftar Reservasi
            </x-button>
        </div>

    </div>

@endsection
