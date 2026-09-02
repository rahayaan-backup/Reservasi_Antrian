<?php

namespace App\Services;

use App\Enums\ReservasiStatus;
use App\Enums\StatusSinkronFisik;
use App\Models\Petugas;
use App\Models\Reservasi;
use Illuminate\Database\Eloquent\Collection;

class SinkronisasiFisikService
{
    public function __construct(private readonly SinkronisasiCounterMesinService $counterService)
    {
    }

    /**
     * Tandai manual oleh CS bahwa nomor antrean reservasi ini sudah
     * dikoordinasikan/dicetak secara fisik di mesin antrean (biasanya
     * lewat komunikasi langsung dengan petugas security di loket).
     */
    public function tandaiSudahDicetak(Reservasi $reservasi, Petugas $petugas): Reservasi
    {
        $reservasi->update([
            'status_sinkron_fisik' => StatusSinkronFisik::SudahDisinkronkan,
            'disinkronkan_pada' => now(),
            'disinkronkan_oleh_petugas_id' => $petugas->id,
        ]);

        return $reservasi->fresh();
    }

    /**
     * Coba deteksi otomatis: baca Total Antrian dari mesin fisik (hanya
     * berhasil kalau perangkat yang menjalankan Laravel sedang tersambung
     * ke jaringan WiFi mesin antrean — mis. mode testing lokal). Kalau
     * nomor reservasi ini sudah <= Total Antrian saat ini, berarti
     * tiketnya sudah pasti tercetak fisik, dan langsung ditandai sinkron
     * tanpa perlu konfirmasi manual.
     *
     * @return bool true jika berhasil dideteksi & ditandai otomatis.
     */
    public function cobaSinkronOtomatis(Reservasi $reservasi): bool
    {
        if (! preg_match('/^([A-Za-z]+)(\d+)$/', $reservasi->nomor_antrean, $cocok)) {
            return false;
        }

        $kodeLayanan = strtoupper($cocok[1]);
        $nomorUrut = (int) $cocok[2];

        $totalAntreanSaatIni = $this->counterService->ambilTotalAntreanSaatIni($kodeLayanan);

        if ($totalAntreanSaatIni === null || $nomorUrut > $totalAntreanSaatIni) {
            return false;
        }

        $reservasi->update([
            'status_sinkron_fisik' => StatusSinkronFisik::SudahDisinkronkan,
            'disinkronkan_pada' => now(),
            'disinkronkan_oleh_petugas_id' => null, // null menandakan terdeteksi otomatis oleh sistem, bukan input manual CS.
        ]);

        return true;
    }

    /**
     * Jumlah reservasi yang masih menunggu konfirmasi sinkronisasi fisik,
     * dibatasi hanya status "Perlu Datang" — begitu reservasi selesai atau
     * dibatalkan, kebutuhan sinkronisasi fisiknya sudah tidak relevan lagi.
     */
    public function hitungBelumSinkron(): int
    {
        return Reservasi::query()
            ->where('status_sinkron_fisik', StatusSinkronFisik::BelumDisinkronkan)
            ->where('status', ReservasiStatus::PerluDatang)
            ->count();
    }

    /**
     * Daftar reservasi yang masih menunggu sinkronisasi, terbaru dahulu,
     * untuk ditampilkan sebagai widget di Dashboard CS.
     */
    public function daftarBelumSinkron(int $batas = 5): Collection
    {
        return Reservasi::query()
            ->where('status_sinkron_fisik', StatusSinkronFisik::BelumDisinkronkan)
            ->where('status', ReservasiStatus::PerluDatang)
            ->with([
                'layanan:id,nama_layanan,kode_layanan',
                'jadwal:id,tanggal,jam_mulai,jam_selesai',
            ])
            ->latest('updated_at')
            ->limit($batas)
            ->get(['id', 'kode_reservasi', 'nomor_antrean', 'nama', 'layanan_id', 'jadwal_id', 'updated_at']);
    }
}
