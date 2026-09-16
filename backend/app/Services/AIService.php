<?php

namespace App\Services;

use App\Models\AnggotaIuran;
use App\Models\DompetKas;
use App\Models\EventKegiatan;
use App\Models\IuranPeriode;
use App\Models\PembayaranIuran;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AIService
 * ---------
 * Chat/generate jawaban -> Groq API (cloud, gratis dengan rate limit,
 * format kompatibel OpenAI).
 * RAG embeddings -> tetap lewat Ollama lokal (Groq tidak punya endpoint
 * embeddings). Kalau Ollama tidak tersedia, RAG otomatis di-skip tanpa
 * mengganggu fitur chat/tool-calling utama.
 */
class AIService
{
    // Groq (chat/generate)
    protected string $groqUrl;
    protected string $groqApiKey;
    protected string $groqModel;

    // Ollama (khusus embeddings utk RAG dokumen)
    protected string $ollamaUrl;
    protected string $embedModel;

    public function __construct()
    {
        $this->groqUrl = rtrim(config('services.groq.url', 'https://api.groq.com/openai/v1'), '/');
        $this->groqApiKey = config('services.groq.api_key', env('GROQ_API_KEY', ''));
        $this->groqModel = config('services.groq.model', env('GROQ_MODEL', 'llama-3.3-70b-versatile'));

        $this->ollamaUrl = rtrim(config('services.ollama.url', env('OLLAMA_URL', 'http://localhost:11434')), '/');
        $this->embedModel = config('services.ollama.embed_model', env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'));
    }

    protected function getToolSpecs(): array
    {
        return [
            'get_saldo_kas' => 'Mengambil total saldo kas organisasi saat ini (semua dompet).',
            'get_pengeluaran_bulan_ini' => 'Total pengeluaran bulan berjalan.',
            'get_pemasukan_bulan_ini' => 'Total pemasukan bulan berjalan.',
            'get_status_iuran_anggota' => 'Status & riwayat pembayaran iuran seorang anggota berdasarkan nama. Param: nama',
            'get_anggota_belum_bayar_iuran' => 'Daftar anggota yang belum lunas iuran periode terbaru.',
            'get_sisa_budget_event' => 'Sisa anggaran suatu kegiatan/event. Param: nama_event (partial match)',
            'get_pengeluaran_per_kategori' => 'Total pengeluaran per kategori dalam N bulan terakhir. Param: kategori, bulan_terakhir',
        ];
    }

    public function chat(string $pertanyaan, User $user): array
    {
        $toolResult = $this->cobaPanggilTool($pertanyaan);
        $ragContext = $this->cariKonteksDokumen($pertanyaan);

        $systemPrompt = $this->buildSystemPrompt($user, $toolResult, $ragContext);
        $jawaban = $this->generate($systemPrompt, $pertanyaan);

        return [
            'jawaban' => $jawaban,
            'tool_dipakai' => $toolResult['tool'] ?? null,
            'sumber_dokumen' => $ragContext['sumber'] ?? [],
        ];
    }

    protected function cobaPanggilTool(string $pertanyaan): array
    {
        $toolListText = collect($this->getToolSpecs())
            ->map(fn ($desc, $name) => "- {$name}: {$desc}")
            ->implode("\n");

        $routerPrompt = <<<PROMPT
Kamu adalah router tool. Berdasarkan pertanyaan user, tentukan apakah perlu
memanggil salah satu tool berikut untuk menjawab dengan data akurat.

Daftar tool:
{$toolListText}

Balas HANYA dengan JSON valid, tanpa teks lain, format:
{"tool": "nama_tool_atau_null", "params": {}}

Jika tidak ada tool yang cocok (pertanyaan umum/prosedural), balas:
{"tool": null, "params": {}}
PROMPT;

        $raw = $this->generate($routerPrompt, $pertanyaan, temperature: 0);
        $json = json_decode($this->extractJson($raw), true);
        $toolName = $json['tool'] ?? null;

        if (!$toolName || !array_key_exists($toolName, $this->getToolSpecs())) {
            return [];
        }

        return ['tool' => $toolName, 'data' => $this->jalankanTool($toolName, $json['params'] ?? [])];
    }

    protected function jalankanTool(string $name, array $params): mixed
    {
        return match ($name) {
            'get_saldo_kas' => (float) DompetKas::sum('saldo'),

            'get_pengeluaran_bulan_ini' => (float) Transaksi::where('jenis', 'pengeluaran')
                ->where('status', 'disetujui')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('jumlah'),

            'get_pemasukan_bulan_ini' => (float) Transaksi::where('jenis', 'pemasukan')
                ->where('status', 'disetujui')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('jumlah'),

            'get_status_iuran_anggota' => (function () use ($params) {
                $anggota = AnggotaIuran::where('nama', 'ilike', '%' . ($params['nama'] ?? '') . '%')->first();
                if (!$anggota) return null;

                return PembayaranIuran::where('anggota_id', $anggota->id)
                    ->with('periode')
                    ->latest()
                    ->limit(6)
                    ->get(['status', 'tanggal_bayar', 'iuran_periode_id'])
                    ->toArray();
            })(),

            'get_anggota_belum_bayar_iuran' => (function () {
                $periodeTerbaru = IuranPeriode::latest('jatuh_tempo')->first();
                if (!$periodeTerbaru) return [];
                return PembayaranIuran::where('iuran_periode_id', $periodeTerbaru->id)
                    ->where('status', 'belum_lunas')
                    ->with('anggota:id,nama,rt')
                    ->get()
                    ->map(fn ($p) => $p->anggota->nama . ' (RT ' . $p->anggota->rt . ')');
            })(),

            'get_sisa_budget_event' => (function () use ($params) {
                $event = EventKegiatan::where('nama', 'ilike', '%' . ($params['nama_event'] ?? '') . '%')->first();
                if (!$event) return null;
                return [
                    'nama' => $event->nama,
                    'budget' => (float) $event->budget,
                    'terpakai' => (float) $event->terpakai(),
                    'sisa' => (float) $event->sisaBudget(),
                ];
            })(),

            'get_pengeluaran_per_kategori' => Transaksi::query()
                ->join('kategori_kas', 'kategori_kas.id', '=', 'transaksi.kategori_id')
                ->when($params['kategori'] ?? null, fn ($q, $k) => $q->where('kategori_kas.nama', 'ilike', "%{$k}%"))
                ->where('transaksi.jenis', 'pengeluaran')
                ->where('transaksi.status', 'disetujui')
                ->where('transaksi.created_at', '>=', now()->subMonths((int) ($params['bulan_terakhir'] ?? 3)))
                ->selectRaw('kategori_kas.nama as kategori, SUM(transaksi.jumlah) as total')
                ->groupBy('kategori_kas.nama')
                ->get(),

            default => null,
        };
    }

    /**
     * RAG dokumen: tetap butuh embeddings dari Ollama lokal (Groq tidak
     * punya endpoint embeddings). Kalau Ollama tidak jalan/gagal, method ini
     * mengembalikan array kosong secara diam-diam — chat tetap jalan normal
     * tanpa konteks dokumen, bukan error.
     */
    protected function cariKonteksDokumen(string $pertanyaan, int $topK = 4): array
    {
        $embedding = $this->embed($pertanyaan);
        if (!$embedding) return [];

        $vectorLiteral = '[' . implode(',', $embedding) . ']';

        try {
            $rows = DB::select("
                SELECT dc.isi_teks, d.judul, d.tipe,
                       1 - (dc.embedding <=> ?::vector) AS similarity
                FROM dokumen_chunks dc
                JOIN dokumen d ON d.id = dc.dokumen_id
                WHERE d.untuk_ai_knowledge_base = true
                ORDER BY dc.embedding <=> ?::vector
                LIMIT ?
            ", [$vectorLiteral, $vectorLiteral, $topK]);
        } catch (\Throwable $e) {
            Log::warning('RAG cariKonteksDokumen gagal: ' . $e->getMessage());
            return [];
        }

        return [
            'chunks' => collect($rows)->pluck('isi_teks')->all(),
            'sumber' => collect($rows)->map(fn ($r) => "{$r->judul} ({$r->tipe})")->unique()->values()->all(),
        ];
    }

    public function simpanChunkDenganEmbedding(int $dokumenId, string $teks): void
    {
        $paragraf = collect(explode("\n\n", $teks))->filter(fn ($p) => trim($p) !== '');

        foreach ($paragraf as $chunk) {
            $embedding = $this->embed($chunk);
            if (!$embedding) continue;

            $vectorLiteral = '[' . implode(',', $embedding) . ']';
            DB::statement(
                'INSERT INTO dokumen_chunks (dokumen_id, isi_teks, embedding, created_at, updated_at)
                 VALUES (?, ?, ?::vector, NOW(), NOW())',
                [$dokumenId, $chunk, $vectorLiteral]
            );
        }
    }

    protected function formatRupiah(int|float $angka): string
    {
        return 'Rp' . number_format($angka, 0, ',', '.');
    }

    protected function formatDataUntukPrompt(mixed $data): mixed
    {
        if (is_float($data) || (is_int($data) && abs($data) >= 1000)) {
            return $this->formatRupiah($data);
        }

        if (is_array($data)) {
            return array_map(fn ($v) => $this->formatDataUntukPrompt($v), $data);
        }

        if (is_object($data) && method_exists($data, 'toArray')) {
            return $this->formatDataUntukPrompt($data->toArray());
        }

        return $data;
    }

    protected function buildSystemPrompt(User $user, array $toolResult, array $ragContext): string
    {
        $dataLive = '';
        if (isset($toolResult['data'])) {
            $dataFormatted = $this->formatDataUntukPrompt($toolResult['data']);
            $dataLive = "Data live dari sistem (SUDAH dalam format Rupiah final — SALIN PERSIS angka ini apa adanya ke jawabanmu, JANGAN mengonversi, membulatkan, atau menghitung ulang dalam bentuk apa pun):\n"
                . json_encode($dataFormatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $dokumenContext = !empty($ragContext['chunks'])
            ? "Referensi dari dokumen organisasi:\n" . implode("\n---\n", $ragContext['chunks'])
            : '';

        return <<<PROMPT
Kamu adalah AI Asisten organisasi bernama "SINERGI AI". Jawab dalam Bahasa
Indonesia yang ramah, singkat, dan jelas.

ATURAN PENTING SOAL ANGKA:
- Semua nominal uang di "Data live" SUDAH diformat final (contoh: "Rp8.260.000").
- SALIN string itu PERSIS apa adanya ke jawabanmu. JANGAN membaca ulang,
  membulatkan, mengonversi ke "juta"/"ribu", atau menghitung ulang skalanya.
- Jangan pernah menyebut angka yang tidak ada persis di "Data live".

Jawab HANYA berdasarkan data live dan/atau referensi dokumen yang diberikan
di bawah. Jika informasi tidak tersedia, katakan dengan jujur bahwa kamu
tidak memiliki datanya dan sarankan menghubungi Bendahara/Pengurus terkait.

Pengguna saat ini: {$user->nama} ({$user->jabatan})

{$dataLive}

{$dokumenContext}
PROMPT;
    }

    /**
     * Generate jawaban lewat Groq (format chat completions kompatibel OpenAI).
     */
    protected function generate(string $systemPrompt, string $userMessage, float $temperature = 0.4): string
    {
        if (empty($this->groqApiKey)) {
            return 'Maaf, layanan AI belum dikonfigurasi. Pastikan GROQ_API_KEY sudah diisi di file .env.';
        }

        try {
            $response = Http::timeout(60)
                ->withToken($this->groqApiKey)
                ->post("{$this->groqUrl}/chat/completions", [
                    'model' => $this->groqModel,
                    'temperature' => $temperature,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error('Groq request gagal: ' . $e->getMessage());
            return 'Maaf, layanan AI sedang tidak dapat diakses. Coba lagi sebentar lagi.';
        }

        if (!$response->successful()) {
            Log::error('Groq response error: ' . $response->status() . ' ' . $response->body());
            return 'Maaf, layanan AI sedang tidak dapat diakses (kode: ' . $response->status() . ').';
        }

        return $response->json('choices.0.message.content', '');
    }

    /**
     * Embeddings tetap lewat Ollama lokal (Groq tidak menyediakan endpoint
     * embeddings). Kalau Ollama tidak tersedia/gagal, return null diam-diam
     * — dipakai oleh cariKonteksDokumen() untuk skip RAG dengan aman.
     */
    protected function embed(string $text): ?array
    {
        try {
            $response = Http::timeout(15)->post("{$this->ollamaUrl}/api/embeddings", [
                'model' => $this->embedModel,
                'prompt' => $text,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        return $response->successful() ? $response->json('embedding') : null;
    }

    protected function extractJson(string $raw): string
    {
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            return $m[0];
        }
        return '{"tool": null, "params": {}}';
    }
}