<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-slate-900 px-4 transition-colors">
    <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 shadow-md rounded-xl p-6 sm:p-8 w-full max-w-sm">
      <h1 class="text-2xl font-bold text-primary-700 dark:text-primary-400 mb-1">WADASABLE</h1>
      <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">Masuk ke akun organisasi kamu</p>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-1 dark:text-slate-300">Email</label>
        <input v-model="email" type="email" required
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 outline-none" />
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-1 dark:text-slate-300">Password</label>
        <input v-model="password" type="password" required
          class="w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 outline-none" />
      </div>

      <p v-if="error" class="text-sm text-red-600 dark:text-rose-400 mb-3">{{ error }}</p>

      <button type="submit" :disabled="loading"
        class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-lg py-2 font-medium disabled:opacity-50 transition-colors">
        {{ loading ? 'Memproses...' : 'Masuk' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const auth = useAuthStore()
const router = useRouter()

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Email atau password salah.'
  } finally {
    loading.value = false
  }
}
</script>