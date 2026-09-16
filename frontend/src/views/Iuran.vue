<template>
  <div>
    <div class="flex justify-between items-center mb-6 flex-wrap gap-2">
      <h1 class="text-xl sm:text-2xl font-bold dark:text-slate-100">Iuran Anggota</h1>
      <div class="flex gap-2 flex-wrap">
        <button @click="showTambahAnggota = true"
          class="bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 dark:text-slate-100 px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm hover:bg-gray-50 dark:hover:bg-slate-700">
          + Tambah Anggota
        </button>
        <button v-if="auth.hasRole('Bendahara', 'Super Admin')" @click="showBuatPeriode = true"
          class="bg-primary-600 hover:bg-primary-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition-colors">
          + Buat Periode Iuran
        </button>
      </div>
    </div>

    <!-- Belum ada periode sama sekali -->
    <div v-if="!periode.length" class="bg-white dark:bg-slate-800 border border-dashed border-gray-300 dark:border-slate-600 rounded-2xl p-8 sm:p-10 text-center">
      <p class="text-4xl mb-2">🧾</p>
      <p class="text-gray-500 dark:text-slate-400 mb-1">Belum ada periode iuran.</p>
      <p class="text-sm text-gray-400 dark:text-slate-500">
        Klik "+ Buat Periode Iuran" untuk mulai mencatat iuran bulan ini.
      </p>
    </div>

    <template v-else>
      <!-- Tab pilih periode -->
      <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
        <button v-for="p in periode" :key="p.id" @click="pilihPeriode(p)"
          class="px-3 py-1.5 rounded-full text-xs sm:text-sm whitespace-nowrap transition-colors flex-shrink-0"
          :class="periodeAktif?.id === p.id
            ? 'bg-primary-600 text-white'
            : 'bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700'">
          {{ p.nama }}
          <span class="ml-1 text-xs opacity-75">({{ p.lunas_count }}/{{ p.lunas_count + p.belum_lunas_count + p.kurang_bayar_count }})</span>
        </button>
      </div>

      <div v-if="periodeAktif" class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-4 sm:p-5">
        <div class="flex justify-between items-center mb-4">
          <div class="min-w-0">
            <h2 class="font-semibold dark:text-slate-100 truncate">{{ periodeAktif.nama }}</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500">
              Jatuh tempo {{ periodeAktif.jatuh_tempo }} — Rp{{ Number(periodeAktif.besaran).toLocaleString('id-ID') }} / anggota
            </p>
          </div>
        </div>

        <div v-if="loadingAnggota" class="text-sm text-gray-400 dark:text-slate-500 py-6 text-center">Memuat data anggota...</div>

        <div v-else-if="!daftarAnggota.length" class="text-sm text-gray-400 dark:text-slate-500 py-6 text-center">
          Belum ada anggota. Klik "+ Tambah Anggota" untuk menambahkan.
        </div>

        <template v-else>
          <!-- Tabel (desktop/tablet ke atas) -->
          <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 dark:text-slate-400 border-b border-gray-100 dark:border-slate-700">
                  <th class="py-2">Nama</th><th>RT</th><th>Tagihan</th><th>Dibayar</th><th>Status</th><th>Tanggal Bayar</th><th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in daftarAnggota" :key="a.anggota_id" class="border-b last:border-0 border-gray-50 dark:border-slate-700/50 dark:text-slate-200">
                  <td class="py-2">{{ a.nama }}</td>
                  <td>{{ a.rt || '-' }}</td>
                  <td class="text-xs">Rp{{ Number(a.tagihan).toLocaleString('id-ID') }}</td>
                  <td class="text-xs">Rp{{ Number(a.total_dibayar).toLocaleString('id-ID') }}</td>
                  <td>
                    <span class="px-2 py-1 rounded-full text-xs" :class="statusClass(a.status)">
                      {{ labelStatus(a.status) }}
                    </span>
                    <div v-if="a.kelebihan > 0" class="text-[10px] text-primary-600 dark:text-primary-400 mt-0.5">
                      +Rp{{ Number(a.kelebihan).toLocaleString('id-ID') }} kredit
                    </div>
                  </td>
                  <td class="text-xs text-gray-400 dark:text-slate-500">{{ a.tanggal_bayar || '-' }}</td>
                  <td>
                    <button v-if="a.status !== 'lunas' && auth.hasRole('Bendahara', 'Super Admin')"
                      @click="bukaModalBayar(a)"
                      class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
                      + Bayar Iuran
                    </button>
                    <button v-else-if="a.status === 'lunas' && a.pembayaran_id && auth.hasRole('Bendahara', 'Super Admin')"
                      @click="batalkanTerakhir(a)" class="text-xs text-gray-400 dark:text-slate-500 hover:underline">
                      Batalkan Terakhir
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Kartu (mobile) -->
          <div class="sm:hidden space-y-3">
            <div v-for="a in daftarAnggota" :key="a.anggota_id"
              class="border border-gray-100 dark:border-slate-700 rounded-xl p-3">
              <div class="flex justify-between items-start gap-2">
                <div class="min-w-0">
                  <p class="font-medium dark:text-slate-100 truncate">{{ a.nama }}</p>
                  <p class="text-xs text-gray-400 dark:text-slate-500">RT {{ a.rt || '-' }}</p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs flex-shrink-0" :class="statusClass(a.status)">
                  {{ labelStatus(a.status) }}
                </span>
              </div>
              <div class="grid grid-cols-2 gap-2 mt-2 text-xs">
                <div>
                  <span class="text-gray-400 dark:text-slate-500">Tagihan</span>
                  <p class="dark:text-slate-200">Rp{{ Number(a.tagihan).toLocaleString('id-ID') }}</p>
                </div>
                <div>
                  <span class="text-gray-400 dark:text-slate-500">Dibayar</span>
                  <p class="dark:text-slate-200">Rp{{ Number(a.total_dibayar).toLocaleString('id-ID') }}</p>
                </div>
              </div>
              <p v-if="a.kelebihan > 0" class="text-[10px] text-primary-600 dark:text-primary-400 mt-1">
                +Rp{{ Number(a.kelebihan).toLocaleString('id-ID') }} kredit
              </p>
              <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Bayar: {{ a.tanggal_bayar || '-' }}</p>

              <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700">
                <button v-if="a.status !== 'lunas' && auth.hasRole('Bendahara', 'Super Admin')"
                  @click="bukaModalBayar(a)"
                  class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
                  + Bayar Iuran
                </button>
                <button v-else-if="a.status === 'lunas' && a.pembayaran_id && auth.hasRole('Bendahara', 'Super Admin')"
                  @click="batalkanTerakhir(a)" class="text-xs text-gray-400 dark:text-slate-500 hover:underline">
                  Batalkan Terakhir
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </template>

    <!-- Modal: Tambah Anggota -->
    <div v-if="showTambahAnggota" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submitTambahAnggota" class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 w-full max-w-sm space-y-3 max-h-[90vh] overflow-y-auto">
        <h2 class="font-semibold text-lg dark:text-slate-100">Tambah Anggota</h2>
        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Nama Anggota</label>
          <input v-model="formAnggota.nama" required placeholder="Nama lengkap"
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        </div>
        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">RT</label>
          <input v-model="formAnggota.rt" required placeholder="Misal: 03"
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        </div>
        <p v-if="errorAnggota" class="text-sm text-rose-600 dark:text-rose-400">{{ errorAnggota }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showTambahAnggota = false" class="px-4 py-2 text-sm dark:text-slate-300">Batal</button>
          <button type="submit" :disabled="submittingAnggota"
            class="bg-primary-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50">
            {{ submittingAnggota ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Modal: Buat Periode Iuran -->
    <div v-if="showBuatPeriode" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submitPeriode" class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 w-full max-w-sm space-y-3 max-h-[90vh] overflow-y-auto">
        <h2 class="font-semibold text-lg dark:text-slate-100">Buat Periode Iuran</h2>
        <input v-model="formPeriode.nama" required placeholder="Misal: Iuran Juli 2026"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        <input v-model="formPeriode.besaran" type="number" required placeholder="Besaran iuran (Rp)"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Jatuh Tempo</label>
          <input v-model="formPeriode.jatuh_tempo" type="date" required
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showBuatPeriode = false" class="px-4 py-2 text-sm dark:text-slate-300">Batal</button>
          <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg text-sm">Buat</button>
        </div>
      </form>
    </div>

    <!-- Modal: Bayar Iuran -->
    <div v-if="showBayarModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submitBayar" class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 w-full max-w-sm space-y-3 max-h-[90vh] overflow-y-auto">
        <h2 class="font-semibold text-lg dark:text-slate-100">Bayar Iuran</h2>
        <p class="text-sm text-gray-500 dark:text-slate-400">{{ anggotaDipilih?.nama }} — RT {{ anggotaDipilih?.rt || '-' }}</p>

        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-3 text-sm space-y-1">
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-slate-400">Tagihan periode ini</span>
            <span class="dark:text-slate-100">Rp{{ Number(anggotaDipilih?.tagihan || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-slate-400">Sudah dibayar</span>
            <span class="dark:text-slate-100">Rp{{ Number(anggotaDipilih?.total_dibayar || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between font-medium">
            <span class="text-gray-600 dark:text-slate-300">Sisa tagihan</span>
            <span class="dark:text-slate-100">Rp{{ Number(anggotaDipilih?.sisa_tagihan || 0).toLocaleString('id-ID') }}</span>
          </div>
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Nominal Dibayar (Rp)</label>
          <input v-model="formBayar.nominal" type="number" required min="1" placeholder="Boleh kurang atau lebih dari sisa tagihan"
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
          <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
            Lebih bayar akan disimpan sebagai kredit untuk periode berikutnya.
          </p>
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Masuk ke Kas</label>
          <select v-model="formBayar.dompet_kas_id" required
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2">
            <option value="" disabled>Pilih dompet kas</option>
            <option v-for="d in dompetKasList" :key="d.id" :value="d.id">{{ d.nama }}</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Tanggal Bayar</label>
          <input v-model="formBayar.tanggal_bayar" type="date"
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        </div>

        <p v-if="errorBayar" class="text-sm text-rose-600 dark:text-rose-400">{{ errorBayar }}</p>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="tutupModalBayar" class="px-4 py-2 text-sm dark:text-slate-300">Batal</button>
          <button type="submit" :disabled="submittingBayar"
            class="bg-primary-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50">
            {{ submittingBayar ? 'Memproses...' : 'Simpan Pembayaran' }}
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

const auth = useAuthStore()

const periode = ref([])
const periodeAktif = ref(null)
const daftarAnggota = ref([])
const loadingAnggota = ref(false)

const showTambahAnggota = ref(false)
const formAnggota = ref({ nama: '', rt: '' })
const submittingAnggota = ref(false)
const errorAnggota = ref('')

const showBuatPeriode = ref(false)
const formPeriode = ref({ nama: '', besaran: '', jatuh_tempo: '' })

const showBayarModal = ref(false)
const anggotaDipilih = ref(null)
const dompetKasList = ref([])
const formBayar = ref({ nominal: '', dompet_kas_id: '', tanggal_bayar: '' })
const submittingBayar = ref(false)
const errorBayar = ref('')

function labelStatus(status) {
  if (status === 'lunas') return 'Lunas'
  if (status === 'kurang_bayar') return 'Kurang Bayar'
  return 'Belum Lunas'
}

function statusClass(status) {
  return {
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': status === 'lunas',
    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300': status === 'kurang_bayar',
    'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300': status === 'belum_lunas',
  }
}

async function loadPeriode() {
  const { data } = await api.get('/iuran/periode')
  periode.value = data
  if (data.length && !periodeAktif.value) pilihPeriode(data[0])
}

async function pilihPeriode(p) {
  periodeAktif.value = p
  loadingAnggota.value = true
  try {
    const { data } = await api.get(`/iuran/periode/${p.id}/anggota`)
    daftarAnggota.value = data
  } finally {
    loadingAnggota.value = false
  }
}

async function loadDompetKas() {
  const { data } = await api.get('/master/dompet-kas')
  dompetKasList.value = data
}

function bukaModalBayar(a) {
  anggotaDipilih.value = a
  formBayar.value = {
    nominal: a.sisa_tagihan > 0 ? a.sisa_tagihan : '',
    dompet_kas_id: dompetKasList.value[0]?.id || '',
    tanggal_bayar: new Date().toISOString().slice(0, 10),
  }
  errorBayar.value = ''
  showBayarModal.value = true
}

function tutupModalBayar() {
  showBayarModal.value = false
  anggotaDipilih.value = null
}

async function submitBayar() {
  submittingBayar.value = true
  errorBayar.value = ''
  try {
    await api.post('/iuran/bayar', {
      anggota_id: anggotaDipilih.value.anggota_id,
      iuran_periode_id: periodeAktif.value.id,
      nominal: formBayar.value.nominal,
      dompet_kas_id: formBayar.value.dompet_kas_id,
      tanggal_bayar: formBayar.value.tanggal_bayar || undefined,
    })
    tutupModalBayar()
    await pilihPeriode(periodeAktif.value)
    await loadPeriode()
  } catch (e) {
    const errors = e.response?.data?.errors
    errorBayar.value = errors ? Object.values(errors).flat().join(' ') : 'Gagal menyimpan pembayaran.'
  } finally {
    submittingBayar.value = false
  }
}

async function batalkanTerakhir(a) {
  if (!a.pembayaran_id) return
  if (!confirm(`Batalkan pembayaran terakhir untuk ${a.nama}?`)) return
  await api.post(`/iuran/${a.pembayaran_id}/batalkan-terakhir`)
  await pilihPeriode(periodeAktif.value)
  await loadPeriode()
}

async function submitTambahAnggota() {
  submittingAnggota.value = true
  errorAnggota.value = ''
  try {
    await api.post('/anggota-iuran', formAnggota.value)
    showTambahAnggota.value = false
    formAnggota.value = { nama: '', rt: '' }
    if (periodeAktif.value) await pilihPeriode(periodeAktif.value)
  } catch (e) {
    const errors = e.response?.data?.errors
    errorAnggota.value = errors ? Object.values(errors).flat().join(' ') : 'Gagal menambahkan anggota.'
  } finally {
    submittingAnggota.value = false
  }
}

async function submitPeriode() {
  await api.post('/iuran/periode', formPeriode.value)
  showBuatPeriode.value = false
  formPeriode.value = { nama: '', besaran: '', jatuh_tempo: '' }
  await loadPeriode()
}

onMounted(async () => {
  await loadDompetKas()
  await loadPeriode()
})
</script>