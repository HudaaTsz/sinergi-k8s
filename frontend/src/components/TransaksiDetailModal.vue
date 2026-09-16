<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="transaksi" class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
      <Transition
        appear
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
      >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="font-semibold dark:text-slate-100">Detail Transaksi</h3>
            <button @click="$emit('close')" class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400">✕</button>
          </div>

          <div class="p-5 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400 dark:text-slate-500">{{ transaksi.kode }}</span>
              <span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-slate-700 dark:text-slate-300">{{ transaksi.status }}</span>
            </div>

            <div>
              <p class="text-xs text-gray-400 dark:text-slate-500">Nominal</p>
              <p class="text-2xl font-bold" :class="transaksi.jenis === 'pemasukan' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                {{ transaksi.jenis === 'pemasukan' ? '+' : '-' }}Rp{{ Number(transaksi.jumlah).toLocaleString('id-ID') }}
              </p>
              <p class="text-xs text-gray-400 dark:text-slate-500 capitalize mt-0.5">{{ transaksi.jenis }} · {{ transaksi.kategori?.nama }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <p class="text-xs text-gray-400 dark:text-slate-500">Tanggal</p>
                <p class="text-sm dark:text-slate-100">{{ formatTanggal(transaksi.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400 dark:text-slate-500">Ditambahkan oleh</p>
                <p class="text-sm dark:text-slate-100">{{ transaksi.pembuat?.nama || '-' }}</p>
              </div>
            </div>

            <div>
              <p class="text-xs text-gray-400 dark:text-slate-500">Deskripsi</p>
              <p class="text-sm dark:text-slate-100 mt-0.5">{{ transaksi.deskripsi || '-' }}</p>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
defineProps({ transaksi: { type: Object, default: null } })
defineEmits(['close'])

function formatTanggal(tgl) {
  if (!tgl) return '-'
  return new Date(tgl).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>