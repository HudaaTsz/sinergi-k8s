<template>
  <div class="max-w-2xl mx-auto flex flex-col h-[calc(100vh-3rem)]">
    <div class="flex items-center justify-between mb-1">
      <h1 class="text-2xl font-bold dark:text-slate-100">🤖 WADASABLE AI Assistant</h1>
      <button @click="chat.reset()" class="text-xs text-gray-400 hover:text-red-500 dark:text-slate-500">
        Reset percakapan
      </button>
    </div>
    <p class="text-sm text-gray-500 dark:text-slate-400 mb-4">
      Tanya apa saja seputar saldo, iuran, pengajuan dana, atau prosedur organisasi (SOP/AD-ART).
      Percakapan ini tetap tersimpan walau kamu pindah menu.
    </p>

    <div class="flex-1 overflow-y-auto bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 space-y-3 mb-3 transition-colors">
      <div v-for="(m, i) in chat.messages" :key="i" :class="m.role === 'user' ? 'text-right' : 'text-left'">
        <div class="inline-block px-4 py-2 rounded-2xl max-w-[80%] animate-slide-up"
          :class="m.role === 'user'
            ? 'bg-primary-600 text-white'
            : 'bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-slate-100'">
          {{ m.text }}
        </div>
        <p v-if="m.sumber?.length" class="text-xs text-gray-400 dark:text-slate-500 mt-1">Sumber: {{ m.sumber.join(', ') }}</p>
      </div>
      <p v-if="chat.loading" class="text-sm text-gray-400 dark:text-slate-500">WADASABLE AI sedang mengetik...</p>
    </div>

    <div class="flex gap-2 mb-3 flex-wrap">
      <button v-for="q in contohPertanyaan" :key="q" @click="kirim(q)"
        class="text-xs bg-primary-50 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 px-3 py-1 rounded-full hover:bg-primary-100 dark:hover:bg-primary-900/70">
        {{ q }}
      </button>
    </div>

    <form @submit.prevent="kirim(input)" class="flex gap-2">
      <input v-model="input" placeholder="Tulis pertanyaan..."
        class="flex-1 border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 dark:text-slate-100 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-primary-500" />
      <button type="submit" :disabled="chat.loading || !input"
        class="bg-primary-600 text-white px-4 py-2 rounded-lg disabled:opacity-50">Kirim</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAiChatStore } from '../stores/aiChat'

defineOptions({ name: 'AIAssistant' })

const chat = useAiChatStore()
const input = ref('')

const contohPertanyaan = [
  'Berapa saldo kas saat ini?',
  'Kapan saya terakhir membayar iuran?',
  'Bagaimana cara mengajukan dana?',
  'Berapa sisa dana acara seminar?',
]

async function kirim(pertanyaan) {
  if (!pertanyaan) return
  input.value = ''
  await chat.kirim(pertanyaan)
}
</script>