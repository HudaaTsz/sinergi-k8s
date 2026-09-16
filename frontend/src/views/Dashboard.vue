<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-2">
      <div>
        <p class="text-sm text-gray-500 dark:text-slate-400">{{ salam }}, {{ auth.user?.nama?.split(' ')[0] }} 👋</p>
        <h1 class="text-xl sm:text-2xl font-bold dark:text-slate-100">Dashboard</h1>
      </div>
      <p class="text-sm text-gray-400 dark:text-slate-500">{{ tanggalHariIni }}</p>
    </div>

    <div v-if="!data" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div v-for="i in 4" :key="i" class="h-28 rounded-2xl bg-gray-100 dark:bg-slate-800 animate-pulse"></div>
    </div>

    <template v-else>
      <!-- Stat cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-br from-primary-600 to-primary-900 shadow-lg shadow-primary-600/20 animate-slide-up">
          <p class="text-sm text-primary-100">Total Saldo Kas</p>
          <p class="text-xl sm:text-2xl font-bold mt-1 break-words">{{ formatRupiah(data.total_saldo) }}</p>
          <span class="absolute -right-4 -bottom-4 text-7xl opacity-10">💰</span>
        </div>

        <div class="rounded-2xl p-5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm animate-slide-up [animation-delay:60ms]">
          <p class="text-sm text-gray-500 dark:text-slate-400">Pemasukan Bulan Ini</p>
          <p class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 break-words">{{ formatRupiah(data.pemasukan_bulan_ini) }}</p>
          <TrendBadge :value="data.trend_pemasukan" positive-is-good />
        </div>

        <div class="rounded-2xl p-5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm animate-slide-up [animation-delay:120ms]">
          <p class="text-sm text-gray-500 dark:text-slate-400">Pengeluaran Bulan Ini</p>
          <p class="text-xl sm:text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1 break-words">{{ formatRupiah(data.pengeluaran_bulan_ini) }}</p>
          <TrendBadge :value="data.trend_pengeluaran" :positive-is-good="false" />
        </div>

        <div class="rounded-2xl p-5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm animate-slide-up [animation-delay:180ms]">
          <p class="text-sm text-gray-500 dark:text-slate-400">Menunggu Approval</p>
          <p class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ data.menunggu_approval }} transaksi</p>
          <RouterLink to="/kas" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Lihat &rarr;</RouterLink>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-4 sm:p-5 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold dark:text-slate-100">Arus Kas — 6 Bulan Terakhir</h2>
          </div>
          <div class="relative w-full" style="height: 260px;">
            <Line v-if="chartData" :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-4 sm:p-5 shadow-sm">
          <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="font-semibold dark:text-slate-100">Per Kategori</h2>
            <!-- Tab Pemasukan / Pengeluaran -->
            <div class="flex bg-gray-100 dark:bg-slate-700 rounded-full p-0.5 text-xs">
              <button
                @click="tabKategori = 'pengeluaran'"
                class="px-3 py-1 rounded-full transition-colors"
                :class="tabKategori === 'pengeluaran'
                  ? 'bg-white dark:bg-slate-600 text-rose-600 dark:text-rose-300 shadow-sm font-medium'
                  : 'text-gray-500 dark:text-slate-400'"
              >Pengeluaran</button>
              <button
                @click="tabKategori = 'pemasukan'"
                class="px-3 py-1 rounded-full transition-colors"
                :class="tabKategori === 'pemasukan'
                  ? 'bg-white dark:bg-slate-600 text-emerald-600 dark:text-emerald-300 shadow-sm font-medium'
                  : 'text-gray-500 dark:text-slate-400'"
              >Pemasukan</button>
            </div>
          </div>

          <div class="relative w-full" style="height: 220px;">
            <Doughnut v-if="doughnutData" :key="tabKategori" :data="doughnutData" :options="doughnutOptions" />
          </div>
          <p v-if="!doughnutData" class="text-sm text-gray-400 dark:text-slate-500 text-center py-8">
            Belum ada {{ tabKategori }} bulan ini
          </p>
        </div>
      </div>

      <!-- Recent transactions -->
      <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-4 sm:p-5 shadow-sm">
        <h2 class="font-semibold mb-4 dark:text-slate-100">Transaksi Terbaru</h2>
        <div v-if="!data.transaksi_terbaru.length" class="text-sm text-gray-400 dark:text-slate-500 py-6 text-center">
          Belum ada transaksi.
        </div>
        <div v-for="t in data.transaksi_terbaru" :key="t.id"
          class="flex items-center justify-between gap-2 py-3 border-b last:border-0 border-gray-100 dark:border-slate-700 flex-wrap sm:flex-nowrap">
          <div class="flex items-center gap-3 min-w-0">
            <span class="w-9 h-9 rounded-full flex items-center justify-center text-lg flex-shrink-0"
              :class="t.jenis === 'pemasukan' ? 'bg-emerald-50 dark:bg-emerald-900/40' : 'bg-rose-50 dark:bg-rose-900/40'">
              {{ t.jenis === 'pemasukan' ? '⬇️' : '⬆️' }}
            </span>
            <div class="min-w-0">
              <p class="text-sm font-medium dark:text-slate-100 truncate">{{ t.deskripsi || t.kategori?.nama || t.kode }}</p>
              <p class="text-xs text-gray-400 dark:text-slate-500 truncate">{{ t.kode }} &middot; {{ t.pembuat?.nama }}</p>
            </div>
          </div>
          <div class="text-right flex-shrink-0">
            <p class="text-sm font-semibold" :class="t.jenis === 'pemasukan' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
              {{ t.jenis === 'pemasukan' ? '+' : '-' }}{{ formatRupiah(t.jumlah) }}
            </p>
            <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(t.status)">{{ statusLabel(t.status) }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, h } from 'vue'
import { Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, Title, Tooltip, Legend, LineElement, CategoryScale,
  LinearScale, PointElement, ArcElement,
} from 'chart.js'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, ArcElement)

const auth = useAuthStore()
const theme = useThemeStore()
const data = ref(null)
const tabKategori = ref('pengeluaran')

const salam = computed(() => {
  const h = new Date().getHours()
  if (h < 11) return 'Selamat pagi'
  if (h < 15) return 'Selamat siang'
  if (h < 18) return 'Selamat sore'
  return 'Selamat malam'
})

const tanggalHariIni = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

onMounted(async () => {
  const res = await api.get('/dashboard')
  data.value = res.data
})

const chartData = computed(() => {
  if (!data.value) return null
  return {
    labels: data.value.grafik.map((g) => g.bulan),
    datasets: [
      { label: 'Pemasukan', data: data.value.grafik.map((g) => g.pemasukan), borderColor: '#10b981', backgroundColor: '#10b98120', tension: 0.35, fill: true },
      { label: 'Pengeluaran', data: data.value.grafik.map((g) => g.pengeluaran), borderColor: '#f43f5e', backgroundColor: '#f43f5e20', tension: 0.35, fill: true },
    ],
  }
})

const axisColor = computed(() => (theme.dark ? '#94a3b8' : '#6b7280'))
const gridColor = computed(() => (theme.dark ? '#33415560' : '#e5e7eb'))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { color: axisColor.value } } },
  scales: {
    x: { ticks: { color: axisColor.value }, grid: { color: gridColor.value } },
    y: { ticks: { color: axisColor.value }, grid: { color: gridColor.value } },
  },
}))

const paletteKategori = ['#4f46e5', '#f59e0b', '#10b981', '#f43f5e', '#0ea5e9', '#a855f7', '#84cc16']

const kategoriAktif = computed(() => {
  if (!data.value) return []
  return tabKategori.value === 'pemasukan'
    ? data.value.pemasukan_per_kategori
    : data.value.pengeluaran_per_kategori
})

const doughnutData = computed(() => {
  if (!kategoriAktif.value?.length) return null
  return {
    labels: kategoriAktif.value.map((k) => k.kategori),
    datasets: [{
      data: kategoriAktif.value.map((k) => k.total),
      backgroundColor: paletteKategori,
      borderWidth: 0,
    }],
  }
})

const doughnutOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { color: axisColor.value, boxWidth: 12, font: { size: 11 } } } },
}))

function formatRupiah(value) {
  if (value == null) return '-'
  return 'Rp' + Number(value).toLocaleString('id-ID')
}

function statusClass(status) {
  return {
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': status === 'disetujui',
    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300': status?.startsWith('menunggu'),
    'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300': status === 'ditolak',
  }
}

function statusLabel(status) {
  return { disetujui: 'Disetujui', menunggu_ketua: 'Menunggu Ketua', menunggu_bendahara: 'Menunggu Bendahara', ditolak: 'Ditolak' }[status] || status
}

const TrendBadge = {
  props: { value: [Number, null], positiveIsGood: { type: Boolean, default: true } },
  setup(props) {
    return () => {
      if (props.value == null) return h('span', { class: 'text-xs text-gray-400 dark:text-slate-500' }, 'Belum ada data bulan lalu')
      const naik = props.value >= 0
      const bagus = props.positiveIsGood ? naik : !naik
      return h('span', {
        class: `text-xs font-medium ${bagus ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}`,
      }, `${naik ? '▲' : '▼'} ${Math.abs(props.value)}% dari bulan lalu`)
    }
  },
}
</script>