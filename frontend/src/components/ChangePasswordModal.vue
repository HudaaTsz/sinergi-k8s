<template>
  <div v-if="show" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[70] p-4">
    <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 rounded-xl p-5 w-full max-w-sm space-y-3">
      <h3 class="font-semibold dark:text-slate-100">Ubah Password</h3>

      <div>
        <label class="text-xs text-gray-500 dark:text-slate-400">Password Saat Ini</label>
        <input v-model="form.current_password" type="password" required
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
      </div>
      <div>
        <label class="text-xs text-gray-500 dark:text-slate-400">Password Baru</label>
        <input v-model="form.new_password" type="password" required minlength="8"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
      </div>
      <div>
        <label class="text-xs text-gray-500 dark:text-slate-400">Konfirmasi Password Baru</label>
        <input v-model="form.new_password_confirmation" type="password" required minlength="8"
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2" />
      </div>

      <p v-if="error" class="text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
      <p v-if="success" class="text-sm text-emerald-600 dark:text-emerald-400">{{ success }}</p>

      <div class="flex justify-end gap-2 pt-1">
        <button type="button" @click="$emit('close')" class="px-3 py-2 text-sm dark:text-slate-300">Tutup</button>
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

const props = defineProps({ show: { type: Boolean, default: false } })
const emit = defineEmits(['close'])

const form = ref({ current_password: '', new_password: '', new_password_confirmation: '' })
const submitting = ref(false)
const error = ref('')
const success = ref('')

watch(() => props.show, (val) => {
  if (val) {
    form.value = { current_password: '', new_password: '', new_password_confirmation: '' }
    error.value = ''
    success.value = ''
  }
})

async function submit() {
  submitting.value = true
  error.value = ''
  success.value = ''
  try {
    await api.post('/me/password', form.value)
    success.value = 'Password berhasil diubah.'
    form.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : 'Gagal mengubah password.'
  } finally {
    submitting.value = false
  }
}
</script>