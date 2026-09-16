<template>
  <div class="space-y-8">
    <!-- ===== AKUN INTERN (khusus Super Admin) ===== -->
    <div v-if="isSuperAdmin">
      <h1 class="text-xl sm:text-2xl font-bold mb-4 dark:text-slate-100">Akun Intern</h1>
      <div v-if="loadingIntern" class="text-sm text-gray-400 dark:text-slate-500 py-4">Memuat...</div>

      <div v-else>
        <div class="hidden sm:block bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700/50">
              <tr class="text-left text-gray-500 dark:text-slate-400">
                <th class="p-3">Nama</th><th>Jabatan</th><th>Divisi</th><th>Status</th><th>QR</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in anggotaIntern" :key="a.id" class="border-t border-gray-100 dark:border-slate-700 dark:text-slate-200">
                <td class="p-3">{{ a.nama }}</td>
                <td>{{ a.jabatan || '-' }}</td>
                <td>{{ a.divisi || '-' }}</td>
                <td>
                  <span class="px-2 py-1 rounded-full text-xs"
                    :class="a.status_keanggotaan === 'aktif'
                      ? 'bg-green-100 text-green-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                      : 'bg-gray-200 text-gray-600 dark:bg-slate-700 dark:text-slate-400'">
                    {{ a.status_keanggotaan }}
                  </span>
                </td>
                <td>
                  <qrcode-vue :value="a.nomor_anggota || String(a.id)" :size="48" />
                </td>
                <td>
                  <button @click="bukaResetPassword(a)" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Reset Password</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="sm:hidden space-y-3">
          <div v-for="a in anggotaIntern" :key="a.id"
            class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4">
            <div class="flex justify-between items-start gap-3">
              <div class="min-w-0">
                <p class="font-medium dark:text-slate-100 truncate">{{ a.nama }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ a.jabatan || '-' }} · {{ a.divisi || '-' }}</p>
                <span class="inline-block mt-2 px-2 py-1 rounded-full text-xs"
                  :class="a.status_keanggotaan === 'aktif'
                    ? 'bg-green-100 text-green-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                    : 'bg-gray-200 text-gray-600 dark:bg-slate-700 dark:text-slate-400'">
                  {{ a.status_keanggotaan }}
                </span>
                <button @click="bukaResetPassword(a)" class="block text-xs text-primary-600 dark:text-primary-400 hover:underline mt-2">Reset Password</button>
              </div>
              <qrcode-vue :value="a.nomor_anggota || String(a.id)" :size="44" class="flex-shrink-0" />
            </div>
          </div>
          <p v-if="!anggotaIntern.length" class="text-sm text-gray-400 dark:text-slate-500 text-center py-6">
            Belum ada data.
          </p>
        </div>
      </div>
    </div>

    <!-- ===== AKUN EXTERN (semua role login bisa lihat) ===== -->
    <div>
      <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
        <h1 class="text-xl sm:text-2xl font-bold dark:text-slate-100">
          {{ isSuperAdmin ? 'Akun Extern' : 'Anggota' }}
        </h1>
        <button v-if="auth.hasRole('Bendahara', 'Super Admin')" @click="showTambahAnggota = true"
          class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors">
          + Tambah Anggota
        </button>
      </div>

      <div v-if="loadingExtern" class="text-sm text-gray-400 dark:text-slate-500 py-4">Memuat...</div>
      <div v-else-if="errorExtern" class="text-sm text-rose-600 dark:text-rose-400 py-4">{{ errorExtern }}</div>

      <div v-else>
        <div class="hidden sm:block bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700/50">
              <tr class="text-left text-gray-500 dark:text-slate-400">
                <th class="p-3">Nama</th><th>RT</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in anggotaExtern" :key="a.id" class="border-t border-gray-100 dark:border-slate-700 dark:text-slate-200">
                <td class="p-3">{{ a.nama }}</td>
                <td>{{ a.rt || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="sm:hidden space-y-2">
          <div v-for="a in anggotaExtern" :key="a.id"
            class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-3 flex justify-between items-center">
            <span class="font-medium dark:text-slate-100">{{ a.nama }}</span>
            <span class="text-xs text-gray-500 dark:text-slate-400">RT {{ a.rt || '-' }}</span>
          </div>
          <p v-if="!anggotaExtern.length" class="text-sm text-gray-400 dark:text-slate-500 text-center py-6">
            Belum ada data.
          </p>
        </div>
      </div>
    </div>

    <!-- Modal: Tambah Anggota -->
    <div v-if="showTambahAnggota" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submitTambahAnggota" class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 w-full max-w-sm space-y-3">
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
  </div>

  <!-- Modal: Reset Password (Akun Intern) -->
    <div v-if="showResetPassword" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <form @submit.prevent="submitResetPassword" class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 w-full max-w-sm space-y-3">
        <h2 class="font-semibold text-lg dark:text-slate-100">Reset Password</h2>
        <p class="text-sm text-gray-500 dark:text-slate-400">{{ internDipilih?.nama }}</p>
        <div>
          <label class="text-xs text-gray-500 dark:text-slate-400">Password Baru</label>
          <input v-model="formResetPassword.new_password" type="password" required minlength="8"
            class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
        </div>
        <p v-if="errorResetPassword" class="text-sm text-rose-600 dark:text-rose-400">{{ errorResetPassword }}</p>
        <p v-if="successResetPassword" class="text-sm text-emerald-600 dark:text-emerald-400">{{ successResetPassword }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showResetPassword = false" class="px-4 py-2 text-sm dark:text-slate-300">Tutup</button>
          <button type="submit" :disabled="submittingResetPassword"
            class="bg-primary-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50">
            {{ submittingResetPassword ? 'Menyimpan...' : 'Reset' }}
          </button>
        </div>
      </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import QrcodeVue from 'qrcode.vue'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const isSuperAdmin = computed(() => {
  try {
    return auth.hasRole('Super Admin')
  } catch {
    return false
  }
})

const showResetPassword = ref(false)
const internDipilih = ref(null)
const formResetPassword = ref({ new_password: '' })
const submittingResetPassword = ref(false)
const errorResetPassword = ref('')
const successResetPassword = ref('')

function bukaResetPassword(a) {
  internDipilih.value = a
  formResetPassword.value = { new_password: '' }
  errorResetPassword.value = ''
  successResetPassword.value = ''
  showResetPassword.value = true
}

async function submitResetPassword() {
  submittingResetPassword.value = true
  errorResetPassword.value = ''
  successResetPassword.value = ''
  try {
    await api.post(`/anggota/${internDipilih.value.id}/reset-password`, formResetPassword.value)
    successResetPassword.value = 'Password berhasil direset.'
  } catch (e) {
    const errors = e.response?.data?.errors
    errorResetPassword.value = errors ? Object.values(errors).flat().join(' ') : 'Gagal mereset password.'
  } finally {
    submittingResetPassword.value = false
  }
}

const anggotaIntern = ref([])
const anggotaExtern = ref([])
const loadingIntern = ref(false)
const loadingExtern = ref(false)
const errorExtern = ref('')

const showTambahAnggota = ref(false)
const formAnggota = ref({ nama: '', rt: '' })
const submittingAnggota = ref(false)
const errorAnggota = ref('')

async function loadIntern() {
  if (!isSuperAdmin.value) return
  loadingIntern.value = true
  try {
    const { data } = await api.get('/anggota')
    anggotaIntern.value = data.data
  } catch (e) {
    console.error('Gagal memuat Akun Intern:', e)
  } finally {
    loadingIntern.value = false
  }
}

async function loadExtern() {
  loadingExtern.value = true
  errorExtern.value = ''
  try {
    const { data } = await api.get('/anggota-iuran')
    anggotaExtern.value = data
  } catch (e) {
    errorExtern.value = 'Gagal memuat daftar anggota.'
    console.error('Gagal memuat Akun Extern:', e)
  } finally {
    loadingExtern.value = false
  }
}

async function submitTambahAnggota() {
  submittingAnggota.value = true
  errorAnggota.value = ''
  try {
    await api.post('/anggota-iuran', formAnggota.value)
    showTambahAnggota.value = false
    formAnggota.value = { nama: '', rt: '' }
    await loadExtern()
  } catch (e) {
    const errors = e.response?.data?.errors
    errorAnggota.value = errors ? Object.values(errors).flat().join(' ') : 'Gagal menambahkan anggota.'
  } finally {
    submittingAnggota.value = false
  }
}

onMounted(() => {
  loadIntern()
  loadExtern()
})
</script>