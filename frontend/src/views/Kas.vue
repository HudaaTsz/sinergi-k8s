<template>
  <div>
    <div class="flex justify-between items-center mb-6 flex-wrap gap-2">
      <h1 class="text-xl sm:text-2xl font-bold dark:text-slate-100">Kas Organisasi</h1>
      <button v-if="auth.hasRole('Ketua', 'Bendahara', 'Super Admin')" @click="bukaForm"
        class="bg-primary-600 hover:bg-primary-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition-colors">
        + Transaksi Baru
      </button>
    </div>

    <!-- Tabel (desktop/tablet ke atas) -->
    <div class="hidden sm:block bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-slate-700/50">
          <tr class="text-left text-gray-600 dark:text-slate-300">
            <th class="p-3">Kode</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!transaksi.length">
            <td colspan="6" class="p-6 text-center text-gray-400 dark:text-slate-500">Belum ada transaksi.</td>
          </tr>
          <tr v-for="t in transaksi" :key="t.id" @click="detailDipilih = t"
            class="border-t border-gray-100 dark:border-slate-700 dark:text-slate-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/40">
            <td class="p-3">{{ t.kode }}</td>
            <td class="capitalize">{{ t.jenis }}</td>
            <td>{{ t.kategori?.nama }}</td>
            <td>Rp{{ Number(t.jumlah).toLocaleString('id-ID') }}</td>
            <td><span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-slate-700 dark:text-slate-300">{{ t.status }}</span></td>
            <td class="space-x-2 whitespace-nowrap" @click.stop>
              <button v-if="t.status === 'menunggu_ketua' && auth.hasRole('Ketua', 'Super Admin')"
                @click="approveKetua(t)" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Setujui (Ketua)</button>
              <button v-if="t.status === 'menunggu_bendahara' && auth.hasRole('Bendahara', 'Super Admin')"
                @click="approveBendahara(t)" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Setujui (Bendahara)</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Kartu (mobile) -->
    <div class="sm:hidden space-y-3">
      <p v-if="!transaksi.length" class="text-sm text-gray-400 dark:text-slate-500 text-center py-8">
        Belum ada transaksi.
      </p>
      <div v-for="t in transaksi" :key="t.id" @click="detailDipilih = t"
        class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-3 cursor-pointer active:bg-gray-50 dark:active:bg-slate-700/40">
        <div class="flex justify-between items-start gap-2">
          <div class="min-w-0">
            <p class="font-medium dark:text-slate-100 truncate">{{ t.kode }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 capitalize">{{ t.jenis }} · {{ t.kategori?.nama }}</p>
          </div>
          <span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-slate-700 dark:text-slate-300 flex-shrink-0">{{ t.status }}</span>
        </div>
        <p class="text-sm font-semibold mt-2 dark:text-slate-100">Rp{{ Number(t.jumlah).toLocaleString('id-ID') }}</p>

        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700 space-x-3" v-if="tampilkanAksi(t)" @click.stop>
          <button v-if="t.status === 'menunggu_ketua' && auth.hasRole('Ketua', 'Super Admin')"
            @click="approveKetua(t)" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Setujui (Ketua)</button>
          <button v-if="t.status === 'menunggu_bendahara' && auth.hasRole('Bendahara', 'Super Admin')"
            @click="approveBendahara(t)" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Setujui (Bendahara)</button>
        </div>
      </div>
    </div>

    <!-- Modal input transaksi -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submitTransaksi" class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 w-full max-w-md space-y-3 max-h-[90vh] overflow-y-auto">
        <h2 class="font-semibold text-lg dark:text-slate-100">Transaksi Baru</h2>

        <div v-if="masterDataError" class="text-xs bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-300 p-2 rounded-lg">
          {{ masterDataError }}
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Jenis</label>
          <select v-model="form.jenis" class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2">
            <option value="pemasukan">Pemasukan</option>
            <option value="pengeluaran">Pengeluaran</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Dompet Kas</label>
          <select v-model="form.dompet_kas_id" required class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2">
            <option value="" disabled>Pilih dompet kas</option>
            <option v-for="d in dompetList" :key="d.id" :value="d.id">{{ d.nama }} (Rp{{ Number(d.saldo).toLocaleString('id-ID') }})</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Kategori</label>
          <KategoriSelect v-model="form.kategori_id" :items="kategoriList" @tambah-baru="showModalKategori = true" />
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Jumlah (Rp)</label>
          <input v-model="form.jumlah" type="number" min="1" required placeholder="0"
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Deskripsi</label>
          <textarea v-model="form.deskripsi" placeholder="Deskripsi transaksi"
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2"></textarea>
        </div>

        <p class="text-xs text-gray-400 dark:text-slate-500">Pengeluaran &gt; Rp500.000 otomatis butuh approval Ketua &amp; Bendahara.</p>

        <p v-if="submitError" class="text-sm text-rose-600 dark:text-rose-400">{{ submitError }}</p>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showForm = false" class="px-4 py-2 text-sm dark:text-slate-300">Batal</button>
          <button type="submit" :disabled="submitting"
            class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50">
            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>

    <TambahKategoriModal
      :show="showModalKategori"
      :tipe-default="form.jenis"
      @close="showModalKategori = false"
      @created="onKategoriDibuat"
    />

    <TransaksiDetailModal :transaksi="detailDipilih" @close="detailDipilih = null" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'
import TambahKategoriModal from '../components/TambahKategoriModal.vue'
import KategoriSelect from '../components/KategoriSelect.vue'
import TransaksiDetailModal from '../components/TransaksiDetailModal.vue'

const auth = useAuthStore()
const transaksi = ref([])
const dompetList = ref([])
const kategoriList = ref([])
const showForm = ref(false)
const submitting = ref(false)
const submitError = ref('')
const masterDataError = ref('')
const showModalKategori = ref(false)
const detailDipilih = ref(null)

const emptyForm = () => ({ jenis: 'pengeluaran', jumlah: '', deskripsi: '', dompet_kas_id: '', kategori_id: '' })
const form = ref(emptyForm())

function tampilkanAksi(t) {
  return (t.status === 'menunggu_ketua' && auth.hasRole('Ketua', 'Super Admin'))
    || (t.status === 'menunggu_bendahara' && auth.hasRole('Bendahara', 'Super Admin'))
}

async function load() {
  const { data } = await api.get('/transaksi')
  transaksi.value = data.data
}

async function loadMasterData() {
  masterDataError.value = ''
  try {
    const [dompetRes, kategoriRes] = await Promise.all([
      api.get('/master/dompet-kas'),
      api.get('/master/kategori-kas'),
    ])
    dompetList.value = dompetRes.data
    kategoriList.value = kategoriRes.data

    if (!dompetList.value.length) {
      masterDataError.value = 'Data dompet kas kosong. Jalankan "php artisan db:seed --class=MasterDataSeeder" di backend.'
    }
  } catch (e) {
    masterDataError.value = 'Gagal memuat data dompet/kategori kas.'
  }
}

function bukaForm() {
  form.value = emptyForm()
  submitError.value = ''
  showForm.value = true
  if (!dompetList.value.length || !kategoriList.value.length) loadMasterData()
}

function onKategoriDibuat(kategoriBaru) {
  kategoriList.value.push(kategoriBaru)
  form.value.kategori_id = kategoriBaru.id
}

async function submitTransaksi() {
  submitting.value = true
  submitError.value = ''
  try {
    await api.post('/transaksi', form.value)
    showForm.value = false
    load()
  } catch (e) {
    const errors = e.response?.data?.errors
    submitError.value = errors ? Object.values(errors).flat().join(' ') : 'Hanya Staff yang dapat menambah transaksi.'
  } finally {
    submitting.value = false
  }
}

async function approveKetua(t) {
  await api.post(`/transaksi/${t.id}/approve-ketua`)
  load()
}
async function approveBendahara(t) {
  await api.post(`/transaksi/${t.id}/approve-bendahara`)
  load()
}

onMounted(() => {
  load()
  loadMasterData()
})
</script>