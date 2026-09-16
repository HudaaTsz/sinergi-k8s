<template>
  <div>
    <h1 class="text-2xl font-bold mb-2 dark:text-slate-100">Laporan</h1>
    <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">
      Buat laporan kas bulanan — tabel per tanggal (otomatis menyesuaikan
      jumlah hari di bulan itu, termasuk 29 Februari di tahun kabisat), dana
      masuk, dana keluar, dan breakdown per kategori pemasukan.
    </p>

    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-5 max-w-lg">
      <h2 class="font-semibold mb-4 dark:text-slate-100">📄 Buat Laporan Bulanan</h2>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Bulan</label>
          <select v-model="bulan" class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2">
            <option v-for="(nama, i) in namaBulan" :key="i" :value="i + 1">{{ nama }}</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Tahun</label>
          <select v-model="tahun" class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2">
            <option v-for="t in daftarTahun" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <button @click="unduh('excel')" :disabled="loading"
          class="bg-primary-600 hover:bg-primary-700 text-white rounded-lg py-2 font-medium disabled:opacity-50 transition-colors text-sm">
          {{ loading === 'excel' ? 'Membuat...' : '📊 Excel (.xlsx)' }}
        </button>
        <button @click="unduh('pdf')" :disabled="loading"
          class="bg-gray-700 hover:bg-gray-800 dark:bg-slate-600 dark:hover:bg-slate-500 text-white rounded-lg py-2 font-medium disabled:opacity-50 transition-colors text-sm">
          {{ loading === 'pdf' ? 'Membuat...' : '📄 PDF' }}
        </button>
      </div>

      <p v-if="error" class="text-sm text-rose-600 dark:text-rose-400 mt-3">{{ error }}</p>
      <p v-if="sukses" class="text-sm text-emerald-600 dark:text-emerald-400 mt-3">{{ sukses }}</p>

      <div class="mt-4 text-xs text-gray-400 dark:text-slate-500 bg-gray-50 dark:bg-slate-700/40 rounded-lg p-3">
        💡 Untuk membuka Excel sebagai <b>Google Sheets</b>: buka
        <a href="https://drive.google.com" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">Google Drive</a>,
        tarik file .xlsx yang diunduh ke Drive, klik dua kali, lalu pilih
        "Buka dengan Google Sheets".
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api/axios'

const namaBulan = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const sekarang = new Date()
const bulan = ref(sekarang.getMonth() + 1)
const tahun = ref(sekarang.getFullYear())
const daftarTahun = Array.from({ length: 6 }, (_, i) => sekarang.getFullYear() - 3 + i)

const loading = ref(null) // null | 'excel' | 'pdf'
const error = ref('')
const sukses = ref('')

async function unduh(jenis) {
  loading.value = jenis
  error.value = ''
  sukses.value = ''
  try {
    const endpoint = jenis === 'pdf' ? '/laporan/export-pdf' : '/laporan/export'
    const ekstensi = jenis === 'pdf' ? 'pdf' : 'xlsx'

    const response = await api.get(endpoint, {
      params: { bulan: bulan.value, tahun: tahun.value },
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.download = `Laporan-Kas-${namaBulan[bulan.value - 1]}-${tahun.value}.${ekstensi}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    sukses.value = 'Laporan berhasil dibuat dan diunduh.'
  } catch (e) {
    if (e.response?.status === 403) {
      error.value = 'Kamu tidak punya akses untuk membuat laporan ini.'
    } else {
      error.value = 'Gagal membuat laporan. Coba lagi.'
    }
  } finally {
    loading.value = null
  }
}
</script>