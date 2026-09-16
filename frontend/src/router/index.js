import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { guest: true } },
  {
    path: '/',
    component: () => import('../layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/Dashboard.vue') },
      { path: 'anggota', name: 'anggota', component: () => import('../views/Anggota.vue') },
      { path: 'kas', name: 'kas', component: () => import('../views/Kas.vue') },
      { path: 'iuran', name: 'iuran', component: () => import('../views/Iuran.vue') },
      { path: 'pengajuan-dana', name: 'pengajuan-dana', component: () => import('../views/PengajuanDana.vue') },
      { path: 'laporan', name: 'laporan', component: () => import('../views/Laporan.vue') },
      // Route '/ai-assistant' dihapus — AI sekarang berbentuk logo mengambang
      // (AiFloatingWidget) yang tampil global di MainLayout, bukan halaman terpisah.
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login' }
  }
  if (to.meta.guest && auth.isLoggedIn) {
    return { name: 'dashboard' }
  }
})

export default router