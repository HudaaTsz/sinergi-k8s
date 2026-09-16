<template>
  <div class="fixed bottom-4 right-4 sm:bottom-5 sm:right-5 z-50 flex flex-col items-end">
    <!-- Panel chat (muncul saat expanded) -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-90 translate-y-4"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-90 translate-y-4"
    >
      <div v-if="expanded" class="mb-3 w-[22rem] max-w-[calc(100vw-2rem)] sm:max-w-[calc(100vw-2.5rem)] h-[30rem] max-h-[calc(100vh-7rem)] sm:max-h-[calc(100vh-8rem)]
        bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-2xl
        flex flex-col overflow-hidden origin-bottom-right">

        <!-- Header panel -->
        <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white shrink-0">
          <div class="flex items-center gap-2">
            <span class="text-lg">🤖</span>
            <div>
              <p class="text-sm font-semibold leading-tight">WADASABLE AI</p>
              <p class="text-[11px] text-primary-100 leading-tight">Siap membantu</p>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button @click="chat.reset()" title="Reset percakapan" class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-white/20 text-sm">↺</button>
            <button @click="expanded = false" title="Tutup" class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-white/20 text-sm">✕</button>
          </div>
        </div>

        <!-- Messages -->
        <div ref="scrollArea" class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50 dark:bg-slate-900/40">
          <div v-for="(m, i) in chat.messages" :key="i" :class="m.role === 'user' ? 'text-right' : 'text-left'">
            <div class="inline-block px-3 py-2 rounded-2xl max-w-[85%] text-sm break-words animate-slide-up"
              :class="m.role === 'user'
                ? 'bg-primary-600 text-white'
                : 'bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 border border-gray-200 dark:border-slate-600'">
              {{ m.text }}
            </div>
            <p v-if="m.sumber?.length" class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Sumber: {{ m.sumber.join(', ') }}</p>
          </div>
          <p v-if="chat.loading" class="text-xs text-gray-400 dark:text-slate-500 px-1">WADASABLE AI sedang mengetik...</p>
        </div>

        <!-- Contoh pertanyaan (hanya tampil kalau chat masih kosong/awal) -->
        <div v-if="chat.messages.length <= 1" class="px-3 pb-2 flex gap-1.5 flex-wrap shrink-0">
          <button v-for="q in contohPertanyaan" :key="q" @click="kirim(q)"
            class="text-[11px] bg-primary-50 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 px-2.5 py-1 rounded-full hover:bg-primary-100 dark:hover:bg-primary-900/70">
            {{ q }}
          </button>
        </div>

        <!-- Input -->
        <form @submit.prevent="kirim(input)" class="flex gap-2 p-3 border-t border-gray-200 dark:border-slate-700 shrink-0">
          <input v-model="input" placeholder="Tulis pertanyaan..."
            class="flex-1 text-sm border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-primary-500" />
          <button type="submit" :disabled="chat.loading || !input"
            class="bg-primary-600 text-white px-3 py-2 rounded-lg text-sm disabled:opacity-50 flex-shrink-0">➤</button>
        </form>
      </div>
    </Transition>

    <!-- Tombol logo mengambang -->
    <button
      @click="expanded = !expanded"
      class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 text-white text-xl sm:text-2xl
        shadow-lg shadow-primary-600/40 flex items-center justify-center hover:scale-105 active:scale-95
        transition-transform relative flex-shrink-0"
      title="WADASABLE AI Assistant"
    >
      {{ expanded ? '✕' : '🤖' }}
      <span v-if="!expanded" class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-emerald-400 rounded-full border-2 border-white dark:border-slate-900"></span>
    </button>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useAiChatStore } from '../stores/aiChat'

const chat = useAiChatStore()
const expanded = ref(false)
const input = ref('')
const scrollArea = ref(null)

const contohPertanyaan = [
  'Berapa saldo kas saat ini?',
  'Kapan saya terakhir membayar iuran?',
  'Bagaimana cara mengajukan dana?',
]

async function kirim(pertanyaan) {
  if (!pertanyaan) return
  input.value = ''
  await chat.kirim(pertanyaan)
}

watch(() => chat.messages.length, async () => {
  await nextTick()
  if (scrollArea.value) scrollArea.value.scrollTop = scrollArea.value.scrollHeight
})
</script>