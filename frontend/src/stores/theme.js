import { defineStore } from 'pinia'

export const useThemeStore = defineStore('theme', {
  state: () => ({
    // Default SELALU light mode. Hanya jadi dark kalau user pernah menekan
    // toggle sebelumnya (tersimpan di localStorage). Tidak lagi otomatis
    // mengikuti preferensi sistem operasi.
    dark: localStorage.getItem('theme') === 'dark',
  }),
  actions: {
    init() {
      this.apply()
    },
    toggle() {
      this.dark = !this.dark
      this.apply()
    },
    apply() {
      document.documentElement.classList.toggle('dark', this.dark)
      localStorage.setItem('theme', this.dark ? 'dark' : 'light')
    },
  },
})