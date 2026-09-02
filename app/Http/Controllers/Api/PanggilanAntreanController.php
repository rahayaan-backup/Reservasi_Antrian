<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PanggilanAntrean;
use App\Services\PanggilanAntreanService;
use App\Services\SinkronisasiCounterMesinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PanggilanAntreanController extends Controller
{
    public function __construct(
        private readonly PanggilanAntreanService $panggilanService,
        private readonly SinkronisasiCounterMesinService $sinkronisasiService,
    ) {
    }

    /**
     * Dipanggil laptop jembatan lewat polling berkala. Mengembalikan job
     * yang masih pending, sekaligus langsung menguncinya (status jadi
     * "diproses") agar tidak diambil dua kali oleh polling yang tumpang
     * tindih.
     */
    public function pending(): JsonResponse
    {
        $jobs = $this->panggilanService->ambilJobPendingDanKunci();

        return response()->json([
            'success' => true,
            'message' => $jobs->isEmpty() ? 'Tidak ada job baru.' : "{$jobs->count()} job siap diproses.",
            'data' => $jobs->map(fn (PanggilanAntrean $job) => [
                'id' => $job->id,
                'kode_layanan' => $job->kode_layanan,
                'nomor_urut' => $job->nomor_urut,
                'field_mesin' => $job->namaFieldMesin(),
            ]),
        ]);
    }

    /**
     * Dipanggil laptop jembatan setelah berhasil mengirim perintah ke
     * mesin antrean fisik.
     */
    public function tandaiSelesai(PanggilanAntrean $panggilan): JsonResponse
    {
        $this->panggilanService->tandaiSelesai($panggilan);

        return response()->json([
            'success' => true,
            'message' => 'Job ditandai selesai.',
        ]);
    }

    /**
     * Dipanggil laptop jembatan kalau gagal mengirim perintah (mis. mesin
     * antrean tidak merespons, WiFi jembatan putus dari sisi mesin).
     */
    public function tandaiGagal(PanggilanAntrean $panggilan, Request $request): JsonResponse
    {
        $pesan = $request->input('pesan', 'Gagal diproses oleh laptop jembatan.');

        $this->panggilanService->tandaiGagal($panggilan, $pesan);

        return response()->json([
            'success' => true,
            'message' => 'Job ditandai gagal.',
        ]);
    }

    /**
     * Dipanggil laptop jembatan di SETIAP siklus polling (bahkan saat tidak
     * ada job), sebagai tanda "saya masih hidup". Disimpan di cache dengan
     * TTL singkat — kalau bridge mati, nilai ini otomatis kedaluwarsa tanpa
     * perlu proses pembersihan terpisah. Dipakai untuk badge status
     * "Mesin Antrean: Online/Offline" di dashboard CS.
     */
    public function heartbeat(): JsonResponse
    {
        Cache::put('jembatan_antrean_terakhir_terlihat', now(), now()->addSeconds(30));

        return response()->json(['success' => true]);
    }

    /**
     * Dipanggil laptop jembatan setiap siklus polling, membawa hasil baca
     * "Total Antrian" langsung dari halaman status mesin fisik. Karena
     * Laravel di Railway tidak bisa menjangkau IP lokal mesin (192.168.4.x)
     * secara langsung, laptop jembatan yang membaca lalu "menitipkan"
     * datanya ke sini — dipakai untuk menjaga NomorAntreanCounter tetap
     * sinkron dengan kondisi mesin fisik yang sebenarnya (lihat
     * SinkronisasiCounterMesinService::simpanLaporanCounterDariJembatan()).
     *
     * Payload yang diharapkan: { "counter": { "A": 14, "B": 3, "C": null } }
     */
    public function counterMesin(Request $request): JsonResponse
    {
        $this->sinkronisasiService->simpanLaporanCounterDariJembatan(
            $request->input('counter', [])
        );

        return response()->json(['success' => true]);
    }
}
