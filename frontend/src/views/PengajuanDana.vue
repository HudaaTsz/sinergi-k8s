<template>
  <div>
    <div class="flex justify-between items-center mb-6 flex-wrap gap-2">
      <h1 class="text-xl sm:text-2xl font-bold dark:text-slate-100">Pengajuan Dana</h1>
      <button @click="showForm = true"
        class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
        + Ajukan Dana
      </button>
    </div>

    <!-- Alur visual -->
    <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mb-6 overflow-x-auto pb-1">
      <span class="px-2 py-1 bg-gray-100 dark:bg-slate-700 dark:text-slate-300 rounded-full whitespace-nowrap">Anggota Ajukan</span> →
      <span class="px-2 py-1 bg-gray-100 dark:bg-slate-700 dark:text-slate-300 rounded-full whitespace-nowrap">Ketua</span> →
      <span class="px-2 py-1 bg-gray-100 dark:bg-slate-700 dark:text-slate-300 rounded-full whitespace-nowrap">Bendahara</span> →
      <span class="px-2 py-1 bg-gray-100 dark:bg-slate-700 dark:text-slate-300 rounded-full whitespace-nowrap">Dana Dicairkan</span>
    </div>

    <div v-if="!daftar.length" class="text-sm text-gray-400 dark:text-slate-500 text-center py-10">
      Belum ada pengajuan dana.
    </div>

    <div class="space-y-3">
      <div v-for="p in daftar" :key="p.id"
        class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4">
        <div class="flex justify-between items-start gap-3 flex-wrap sm:flex-nowrap">
          <div class="min-w-0">
            <p class="font-semibold dark:text-slate-100 break-words">
              {{ p.judul }} <span class="text-xs text-gray-400 dark:text-slate-500">({{ p.kode }})</span>
            </p>
            <p class="text-sm text-gray-500 dark:text-slate-400 break-words">{{ p.keterangan }}</p>
            <p class="text-sm mt-1 dark:text-slate-300">
              Diajukan oleh: {{ p.pemohon?.nama }} — Rp{{ Number(p.jumlah_diajukan).toLocaleString('id-ID') }}
            </p>
          </div>
          <span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-slate-700 dark:text-slate-300 whitespace-nowrap">
            {{ labelStatus(p.status) }}
          </span>
        </div>

        <div class="mt-3 flex gap-2" v-if="p.status === 'diajukan' && auth.hasRole('Ketua', 'Bendahara', 'Super Admin')">
          <button @click="keputusan(p, 'ketua', true)"
            class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition-colors">Setujui</button>
          <button @click="keputusan(p, 'ketua', false)"
            class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">Tolak</button>
        </div>

        <div class="mt-3 flex gap-2" v-if="p.status === 'disetujui_ketua' && auth.hasRole('Bendahara', 'Super Admin')">
          <button @click="keputusan(p, 'bendahara', true)"
            class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition-colors">Setujui</button>
          <button @click="keputusan(p, 'bendahara', false)"
            class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">Tolak</button>
        </div>

        <div class="mt-3" v-if="p.status === 'disetujui_bendahara' && auth.hasRole('Bendahara', 'Super Admin')">
          <button @click="bukaModalCairkan(p)"
            class="text-xs bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-lg transition-colors">
            Cairkan Dana
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Ajukan Dana -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 rounded-xl p-6 w-full max-w-md space-y-3">
        <h2 class="font-semibold text-lg dark:text-slate-100">Ajukan Dana</h2>
        <input v-model="form.judul" required placeholder="Judul kegiatan/keperluan"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-400 rounded-lg px-3 py-2" />
        <textarea v-model="form.keterangan" required placeholder="Keterangan"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-400 rounded-lg px-3 py-2"></textarea>
        <input v-model="form.jumlah_diajukan" type="number" required min="1" placeholder="Jumlah (Rp)"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-400 rounded-lg px-3 py-2" />
        <p v-if="errorForm" class="text-sm text-rose-600 dark:text-rose-400">{{ errorForm }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="tutupForm" class="px-4 py-2 text-sm dark:text-slate-300">Batal</button>
          <button type="submit" :disabled="submitting"
            class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ submitting ? 'Mengajukan...' : 'Ajukan' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Modal: Cairkan Dana -->
    <div v-if="showModalCairkan" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submitCairkan" class="bg-white dark:bg-slate-800 rounded-xl p-6 w-full max-w-sm space-y-3">
        <h2 class="font-semibold text-lg dark:text-slate-100">Cairkan Dana</h2>
        <p class="text-sm text-gray-500 dark:text-slate-400">
          {{ pengajuanDipilih?.judul }} — Rp{{ Number(pengajuanDipilih?.jumlah_diajukan || 0).toLocaleString('id-ID') }}
        </p>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Ambil dari Kas</label>
          <select v-model="formCairkan.dompet_kas_id" required
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2">
            <option value="" disabled>Pilih dompet kas</option>
            <option v-for="d in dompetKasList" :key="d.id" :value="d.id">{{ d.nama }}</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Kategori</label>
          <KategoriSelect v-model="formCairkan.kategori_id" :items="kategoriKasList" @tambah-baru="showModalKategori = true" />
        </div>

        <TambahKategoriModal
          :show="showModalKategori"
          tipe-default="pengeluaran"
          @close="showModalKategori = false"
          @created="onKategoriDibuat"
        />

        <p v-if="errorCairkan" class="text-sm text-rose-600 dark:text-rose-400">{{ errorCairkan }}</p>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="tutupModalCairkan" class="px-4 py-2 text-sm dark:text-slate-300">Batal</button>
          <button type="submit" :disabled="submittingCairkan"
            class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ submittingCairkan ? 'Memproses...' : 'Cairkan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'
import KategoriSelect from '../components/KategoriSelect.vue'
import TambahKategoriModal from '../components/TambahKategoriModal.vue'

const auth = useAuthStore()
const daftar = ref([])

const showForm = ref(false)
const form = ref({ judul: '', keterangan: '', jumlah_diajukan: '' })
const submitting = ref(false)
const errorForm = ref('')

const dompetKasList = ref([])
const kategoriKasList = ref([])
const showModalCairkan = ref(false)
const pengajuanDipilih = ref(null)
const formCairkan = ref({ dompet_kas_id: '', kategori_id: '' })
const submittingCairkan = ref(false)
const errorCairkan = ref('')
const showModalKategori = ref(false)

function labelStatus(status) {
  const map = {
    diajukan: 'Diajukan',
    disetujui_ketua: 'Disetujui Ketua',
    ditolak_ketua: 'Ditolak Ketua',
    disetujui_bendahara: 'Disetujui Bendahara',
    ditolak_bendahara: 'Ditolak Bendahara',
    dicairkan: 'Dicairkan',
  }
  return map[status] || status
}

async function load() {
  const { data } = await api.get('/pengajuan-dana')
  daftar.value = data.data
}

async function loadMasterData() {
  const [dompet, kategori] = await Promise.all([
    api.get('/master/dompet-kas'),
    api.get('/master/kategori-kas'),
  ])
  dompetKasList.value = dompet.data
  kategoriKasList.value = kategori.data
}

async function submit() {
  submitting.value = true
  errorForm.value = ''
  try {
    await api.post('/pengajuan-dana', form.value)
    tutupForm()
    load()
  } catch (e) {
    const errors = e.response?.data?.errors
    errorForm.value = errors ? Object.values(errors).flat().join(' ') : 'Gagal mengajukan dana.'
  } finally {
    submitting.value = false
  }
}

function tutupForm() {
  showForm.value = false
  form.value = { judul: '', keterangan: '', jumlah_diajukan: '' }
  errorForm.value = ''
}

async function keputusan(p, tahap, setuju) {
  await api.post(`/pengajuan-dana/${p.id}/keputusan-${tahap}`, { setuju })
  load()
}

function bukaModalCairkan(p) {
  pengajuanDipilih.value = p
  formCairkan.value = {
    dompet_kas_id: dompetKasList.value[0]?.id || '',
    kategori_id: kategoriKasList.value[0]?.id || '',
  }
  errorCairkan.value = ''
  showModalCairkan.value = true
}

function tutupModalCairkan() {
  showModalCairkan.value = false
  pengajuanDipilih.value = null
}

function onKategoriDibuat(kategoriBaru) {
  kategoriKasList.value.push(kategoriBaru)
  formCairkan.value.kategori_id = kategoriBaru.id
}

async function submitCairkan() {
  submittingCairkan.value = true
  errorCairkan.value = ''
  try {
    await api.post(`/pengajuan-dana/${pengajuanDipilih.value.id}/cairkan`, formCairkan.value)
    tutupModalCairkan()
    load()
  } catch (e) {
    errorCairkan.value = e.response?.data?.message || 'Gagal mencairkan dana.'
  } finally {
    submittingCairkan.value = false
  }
}

onMounted(() => {
  load()
  loadMasterData()
})
</script>