import { defineStore } from 'pinia'
import api from '../api/axios'

export const useAiChatStore = defineStore('aiChat', {
  state: () => ({
    messages: [
      { role: 'ai', text: 'Halo! Saya WADASABLE AI. Ada yang bisa saya bantu seputar keuangan atau prosedur organisasi?' },
    ],
    loading: false,
  }),
  actions: {
    async kirim(pertanyaan) {
      if (!pertanyaan) return
      this.messages.push({ role: 'user', text: pertanyaan })
      this.loading = true
      try {
        const { data } = await api.post('/ai/chat', { pertanyaan })
        this.messages.push({ role: 'ai', text: data.jawaban, sumber: data.sumber_dokumen })
      } catch (e) {
        this.messages.push({ role: 'ai', text: 'Maaf, terjadi kesalahan menghubungi AI. Pastikan Ollama berjalan.' })
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.messages = [
        { role: 'ai', text: 'Halo! Saya WADASABLE AI. Ada yang bisa saya bantu seputar keuangan atau prosedur organisasi?' },
      ]
    },
  },
})