<template>
  <div v-if="show" class="fixed inset-0 bg-black/40 flex items-center justify-center z-[60] p-4">
    <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 rounded-xl p-5 w-full max-w-xs space-y-3">
      <h3 class="font-semibold dark:text-slate-100">Tambah Kategori Baru</h3>

      <div>
        <label class="text-xs text-gray-500 dark:text-slate-400">Nama Kategori</label>
        <input v-model="nama" required placeholder="Misal: Healing"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
      </div>

      <div>
        <label class="text-xs text-gray-500 dark:text-slate-400">Tipe</label>
        <select v-model="tipe" class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2">
          <option value="pengeluaran">Pengeluaran</option>
          <option value="pemasukan">Pemasukan</option>
          <option value="keduanya">Keduanya</option>
        </select>
      </div>

      <p v-if="error" class="text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>

      <div class="flex justify-end gap-2 pt-1">
        <button type="button" @click="$emit('close')" class="px-3 py-2 text-sm dark:text-slate-300">Batal</button>
        <button type="submit" :disabled="submitting"
          class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded-lg text-sm disabled:opacity-50">
          {{ submitting ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '../api/axios'

const props = defineProps({
  show: { type: Boolean, default: false },
  tipeDefault: { type: String, default: 'pengeluaran' },
})
const emit = defineEmits(['close', 'created'])

const nama = ref('')
const tipe = ref(props.tipeDefault)
const submitting = ref(false)
const error = ref('')

watch(() => props.show, (val) => {
  if (val) {
    nama.value = ''
    tipe.value = props.tipeDefault
    error.value = ''
  }
})

async function submit() {
  submitting.value = true
  error.value = ''
  try {
    const { data } = await api.post('/kategori-kas', { nama: nama.value, tipe: tipe.value })
    emit('created', data)
    emit('close')
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : 'Gagal menambah kategori.'
  } finally {
    submitting.value = false
  }
}
</script>