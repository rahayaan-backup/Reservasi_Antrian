<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\NomorAntreanCounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SinkronisasiCounterMesinService
{
    /**
     * Baca "Total Antrian" saat ini langsung dari halaman status mesin
     * antrean fisik (http://192.168.4.1:8081), untuk dipakai sebagai basis
     * penomoran reservasi online — supaya nomor online tidak pernah lebih
     * kecil dari nomor yang sudah dicetak fisik hari itu.
     *
     * Mengembalikan null kalau mesin tidak terjangkau (mis. laptop belum
     * konek ke WiFi mesin, atau mode produksi belum pakai jembatan aktif)
     * — pemanggil wajib menangani null dengan fallback ke counter internal
     * (NomorAntreanCounter, Sprint 2) supaya sistem tetap jalan meski
     * mesin fisik sedang tidak terjangkau.
     *
     * Method ini HANYA berfungsi pada mode "langsung" (Laravel & mesin di
     * jaringan yang sama). Pada mode "jembatan" (produksi/Railway), method
     * ini selalu mengembalikan null karena Laravel secara fisik tidak bisa
     * menjangkau IP lokal mesin (192.168.4.x) dari internet — sinkronisasi
     * pada mode "jembatan" dilakukan lewat method
     * simpanLaporanCounterDariJembatan() di bawah, yang dipanggil dari
     * laporan berkala laptop jembatan.
     */
    public function ambilTotalAntreanSaatIni(string $kodeLayanan): ?int
    {
        if (config('services.mesin_antrean.mode') !== 'langsung') {
            return null;
        }

        $config = config('services.mesin_antrean');

        try {
            $response = Http::withBasicAuth($config['username'], $config['password'])
                ->timeout(5)
                ->get($config['url'] . '/');

            if (! $response->successful()) {
                return null;
            }

            return $this->parseTotalAntreanDariHtml($response->body(), $kodeLayanan);
        } catch (\Throwable $e) {
            Log::warning('Gagal membaca counter mesin antrean: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Ekstrak angka "Total Antrian X : N" dari HTML halaman status mesin.
     * Pola pencarian mengikuti tampilan yang terlihat di web control panel
     * ("Total Antrian A : 14", dst untuk B dan C).
     */
    private function parseTotalAntreanDariHtml(string $html, string $kodeLayanan): ?int
    {
        $polaLayanan = match (strtoupper($kodeLayanan)) {
            'A' => 'A',
            'B' => 'B',
            'C' => 'C',
            default => null,
        };

        if (! $polaLayanan) {
            return null;
        }

        if (preg_match('/Total\s+Antrian\s+' . $polaLayanan . '\s*:\s*(\d+)/i', $html, $cocok)) {
            return (int) $cocok[1];
        }

        return null;
    }

    /**
     * Terima laporan "Total Antrian" dari laptop jembatan (dipakai pada
     * mode "jembatan", produksi/Railway). Karena Laravel di cloud tidak
     * bisa mengakses IP lokal mesin secara langsung, laptop jembatan yang
     * membaca halaman status mesin (dengan parsing HTML yang identik
     * dengan parseTotalAntreanDariHtml() di atas, dijalankan versi
     * JavaScript-nya di bridge.js), lalu "menitipkan" hasilnya ke sini
     * lewat endpoint API secara berkala (setiap siklus polling).
     *
     * Counter internal (NomorAntreanCounter) HANYA dimajukan kalau angka
     * dari mesin LEBIH BESAR dari yang tersimpan saat ini — supaya nomor
     * tidak pernah mundur akibat laporan yang telat/basi, dan supaya
     * perilakunya konsisten dengan logika yang sama persis dipakai pada
     * mode "langsung" di ReservasiService::generateNomorAntrean().
     *
     * @param  array<string, int|null>  $counterPerLayanan  Contoh: ['A' => 14, 'B' => 3, 'C' => null]
     */
    public function simpanLaporanCounterDariJembatan(array $counterPerLayanan): void
    {
        $tanggalHariIni = now()->toDateString();

        foreach ($counterPerLayanan as $kodeLayanan => $totalDariMesin) {
            if ($totalDariMesin === null || $totalDariMesin === '') {
                continue;
            }

            $totalDariMesin = (int) $totalDariMesin;

            $layanan = Layanan::query()
                ->where('kode_layanan', strtoupper($kodeLayanan))
                ->first();

            if (! $layanan) {
                Log::warning("Laporan counter mesin: kode layanan '{$kodeLayanan}' tidak dikenali, dilewati.");
                continue;
            }

            DB::transaction(function () use ($layanan, $tanggalHariIni, $totalDariMesin) {
                $counter = NomorAntreanCounter::query()
                    ->where('layanan_id', $layanan->id)
                    ->where('tanggal', $tanggalHariIni)
                    ->lockForUpdate()
                    ->first();

                if (! $counter) {
                    NomorAntreanCounter::create([
                        'layanan_id' => $layanan->id,
                        'tanggal' => $tanggalHariIni,
                        'urutan_terakhir' => $totalDariMesin,
                    ]);

                    return;
                }

                if ($totalDariMesin > $counter->urutan_terakhir) {
                    $counter->update(['urutan_terakhir' => $totalDariMesin]);
                }
            });
        }
    }
}
