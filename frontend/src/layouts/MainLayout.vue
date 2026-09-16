<template>
  <div class="flex flex-col md:flex-row h-screen bg-gray-50 dark:bg-slate-900 transition-colors">
    <!-- ===== Navbar atas (mobile only) ===== -->
    <div class="md:hidden px-3 pt-3 flex-shrink-0 relative">
      <header class="flex items-center justify-between px-4 py-2.5 rounded-2xl
        bg-slate-100/90 dark:bg-slate-800/90 backdrop-blur border border-gray-200/70 dark:border-slate-700/70 shadow-sm relative z-20">
        <h1 class="text-base font-bold text-primary-600 dark:text-primary-400">Wadasable</h1>
        <div class="flex items-center gap-1.5">
          <button
            @click="theme.toggle()"
            class="w-8 h-8 flex items-center justify-center rounded-full bg-white/70 dark:bg-slate-700/70 hover:bg-white dark:hover:bg-slate-600 transition-colors text-sm"
          >
            {{ theme.dark ? '☀️' : '🌙' }}
          </button>
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="w-8 h-8 flex items-center justify-center rounded-full bg-white/70 dark:bg-slate-700/70 text-gray-700 dark:text-slate-200 text-sm"
          >
            <span v-if="!mobileMenuOpen">☰</span>
            <span v-else>✕</span>
          </button>
        </div>
      </header>

      <!-- Overlay: tap di luar panel utk nutup -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="mobileMenuOpen" @click="mobileMenuOpen = false" class="fixed inset-0 bg-black/20 z-10"></div>
      </Transition>

      <!-- Panel menu: compact, mengambang di kanan atas, tidak full width -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-4 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 -translate-y-4 scale-95"
      >
        <nav v-if="mobileMenuOpen"
          class="absolute top-full right-3 mt-2 w-[68%] max-w-[280px] origin-top-right z-20
            rounded-2xl bg-slate-100/95 dark:bg-slate-800/95 backdrop-blur border border-gray-200/70 dark:border-slate-700/70 shadow-xl overflow-hidden">
          <div class="p-2 space-y-1 max-h-[50vh] overflow-y-auto">
            <RouterLink
              v-for="item in menuItems"
              :key="item.to"
              :to="item.to"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium transition-colors"
              :class="isActive(item)
                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300'
                : 'text-gray-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700/60'"
            >
              <span class="text-sm">{{ item.icon }}</span> {{ item.label }}
            </RouterLink>
          </div>
          <div class="p-3 border-t border-gray-200/70 dark:border-slate-700/70">
            <p class="text-sm font-semibold dark:text-slate-100 truncate">{{ auth.user?.nama }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 truncate mb-2">{{ auth.roles.join(', ') }}</p>
            <div class="flex items-center gap-3">
              <button @click="doLogout" class="text-xs text-red-600 dark:text-red-400 hover:underline">Keluar</button>
            </div>
          </div>
        </nav>
      </Transition>
    </div>

    <!-- ===== Sidebar (desktop/tablet only) ===== -->
    <aside class="hidden md:flex md:w-64 bg-white dark:bg-slate-800 border-r border-gray-200 dark:border-slate-700 flex-col transition-colors flex-shrink-0">
      <div class="p-5 border-b border-gray-200 dark:border-slate-700">
        <h1 class="text-xl font-bold text-primary-600 dark:text-primary-400">Wadasable</h1>
        <p class="text-xs text-gray-500 dark:text-slate-400">Sistem Informasi Organisasi</p>
      </div>

      <nav class="flex-1 overflow-y-auto p-3 space-y-1">
        <RouterLink
          v-for="item in menuItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="isActive(item)
            ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300'
            : 'text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700/60'"
        >
          <span>{{ item.icon }}</span> {{ item.label }}
        </RouterLink>
      </nav>

      <div class="p-4 border-t border-gray-200 dark:border-slate-700">
        <div class="flex items-center justify-between mb-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold dark:text-slate-100 truncate">{{ auth.user?.nama }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ auth.roles.join(', ') }}</p>
          </div>
          <button
            @click="theme.toggle()"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors flex-shrink-0"
            :title="theme.dark ? 'Mode terang' : 'Mode gelap'"
          >
            {{ theme.dark ? '☀️' : '🌙' }}
          </button>
        </div>
        <div class="flex items-center justify-between">
          <button @click="doLogout" class="text-xs text-red-600 dark:text-red-400 hover:underline">Keluar</button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6">
      <RouterView v-slot="{ Component, route }">
        <keep-alive>
          <component :is="Component" :key="route.name" />
        </keep-alive>
      </RouterView>
    </main>

    <!-- AI Assistant: logo mengambang di kanan bawah, tampil di semua halaman -->
    <AiFloatingWidget />

    <ChangePasswordModal :show="showChangePassword" @close="showChangePassword = false" />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'
import AiFloatingWidget from '../components/AiFloatingWidget.vue'
import ChangePasswordModal from '../components/ChangePasswordModal.vue'

const auth = useAuthStore()
const theme = useThemeStore()
const router = useRouter()
const route = useRoute()

const mobileMenuOpen = ref(false)
const showChangePassword = ref(false)

const allMenu = [
  { to: '/', name: 'dashboard', icon: '📊', label: 'Dashboard', roles: null },
  { to: '/anggota', name: 'anggota', icon: '👥', label: 'Anggota', roles: null },
  { to: '/kas', name: 'kas', icon: '💰', label: 'Kas Organisasi', roles: null },
  { to: '/iuran', name: 'iuran', icon: '🧾', label: 'Iuran Anggota', roles: null },
  { to: '/pengajuan-dana', name: 'pengajuan-dana', icon: '📝', label: 'Pengajuan Dana', roles: null },
  { to: '/laporan', name: 'laporan', icon: '📈', label: 'Laporan', roles: ['Super Admin', 'Ketua', 'Bendahara', 'Auditor'] },
]

const menuItems = computed(() => allMenu.filter((m) => !m.roles || auth.hasRole(...m.roles)))

function isActive(item) {
  return route.name === item.name
}

watch(() => route.name, () => { mobileMenuOpen.value = false })

async function doLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>