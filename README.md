# SINERGI — Sistem Informasi Keuangan & Organisasi

Aplikasi manajemen kas, anggota, iuran, pengajuan dana, laporan, dan AI Assistant
untuk organisasi (himpunan, komunitas, karang taruna, dll).

## Stack

| Layer     | Teknologi |
|-----------|-----------|
| Frontend  | Vue 3 + Vite + Pinia + Vue Router + TailwindCSS |
| Backend   | Laravel 11 (REST API) + Sanctum (auth) + Spatie Laravel-Permission (role) |
| Database  | **PostgreSQL 15+** (dengan extension `pgvector` untuk AI knowledge base) |
| AI Engine | **Ollama** (model open-source & gratis: `llama3.1` / `mistral`, jalan lokal) |
| Notifikasi| Queue Laravel + driver Email / WhatsApp (Fonnte/Wablas) / Telegram Bot API |

## Kenapa PostgreSQL, bukan MongoDB?

Data di sistem ini sangat **relasional** dan butuh **integritas transaksi**:
- Approval berlapis (Ketua → Bendahara) harus konsisten, tidak boleh "setengah update".
- Saldo kas harus akurat (agregasi SUM dari banyak tabel: transaksi, transfer, iuran).
- Laporan (neraca, cashflow, per divisi/kegiatan) butuh JOIN & agregasi SQL yang kuat.
- Constraint (misal: total pengeluaran event tidak boleh melebihi budget) lebih aman
  ditegakkan dengan foreign key + check constraint di RDBMS.

MongoDB lebih cocok untuk data tak terstruktur/log/chat history — dan di sini kita
tetap pakai tabel Postgres biasa untuk chat log AI, jadi MongoDB tidak diperlukan.

## Kenapa Ollama, bukan OpenAI API?

Kamu minta model AI yang **open source dan gratis**. OpenAI (GPT) berbayar dan tidak
open-source. Alternatif open-source gratis yang setara secara kemampuan reasoning
untuk kasus pakai ini:

- **Llama 3.1 8B** (Meta) — bagus untuk reasoning & tool-use.
- **Mistral 7B** — ringan, cepat, cocok untuk server kecil.
- **Qwen2.5** — bagus untuk Bahasa Indonesia.

Semua dijalankan lokal lewat **Ollama** (https://ollama.com), gratis, tanpa API key,
tanpa biaya per-token. Laravel memanggil Ollama lewat HTTP ke `localhost:11434`.

## Struktur Folder

```
sinergi-app/
├── backend/          # Laravel API
│   ├── app/Models/
│   ├── app/Http/Controllers/Api/
│   ├── app/Services/AIService.php   <- integrasi Ollama + RAG
│   ├── database/migrations/
│   └── routes/api.php
└── frontend/         # Vue 3 SPA
    └── src/
        ├── views/
        ├── stores/   (Pinia)
        └── router/
```

## Instalasi

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Pastikan PostgreSQL & extension pgvector aktif:
#   CREATE EXTENSION IF NOT EXISTS vector;

php artisan migrate --seed
php artisan serve
```

### 2. Install Ollama (AI, gratis & lokal)

```bash
# Linux/Mac
curl -fsSL https://ollama.com/install.sh | sh

# Tarik model open-source (pilih salah satu)
ollama pull llama3.1
ollama pull qwen2.5        # lebih baik untuk Bahasa Indonesia

ollama serve   # default jalan di http://localhost:11434
```

Set di `.env` backend:
```
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5
```

### 3. Frontend

```bash
cd frontend
npm install
npm run dev
```

## Role & Hak Akses

| Role | Akses |
|------|-------|
| Super Admin | Semua modul, user & konfigurasi sistem |
| Ketua | Lihat semua laporan, approval tahap 1 |
| Bendahara | Kelola transaksi, anggaran, laporan keuangan, approval tahap 2 |
| Sekretaris | Surat, dokumen, agenda |
| Koordinator Divisi | Kegiatan & anggaran divisinya |
| Anggota | Data pribadi, status iuran, ajukan dana, dokumen yang diizinkan |
| Auditor | Read-only ke semua laporan & riwayat transaksi |

## Status Implementasi

✅ Selesai (siap pakai, jadi contoh pola untuk modul lain):
- Auth + Role (Sanctum + Spatie Permission)
- Dashboard (saldo, pemasukan/pengeluaran bulan ini, grafik, transaksi terbaru)
- Anggota (CRUD, status aktif/nonaktif, riwayat iuran)
- Kas & Transaksi (pemasukan/pengeluaran/transfer, upload bukti, kategori)
- **Approval berlapis** (Ketua → Bendahara, threshold Rp500rb)
- Iuran (besaran, jatuh tempo, status lunas/belum, reminder)
- Pengajuan Dana (alur: Anggota → Ketua → Bendahara → Cair)
- Anggaran per kegiatan (budget vs terpakai vs sisa)
- AI Assistant (chat, tool-calling ke data live, RAG dari dokumen)

🚧 Kerangka disediakan, tinggal dikembangkan dengan pola yang sama:
- Surat masuk/keluar & nomor otomatis
- Inventaris
- Dokumen (proposal/LPJ/SK/SOP) — sudah ada tabel `documents` utk AI RAG
- Kalender agenda
- Export PDF/Excel laporan (gunakan `barryvdh/laravel-dompdf` & `maatwebsite/excel`)
- Notifikasi WhatsApp/Telegram (queue job + webhook provider)

Lanjutkan modul berikutnya kapan saja — beri tahu saya modul mana yang mau
diprioritaskan dan saya lengkapi dengan pola yang sama (migration → model →
controller → route → Vue view).
"# sinergi-k8s" 
"# sinergi-k8s" 
