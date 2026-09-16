<template>
  <div ref="root" class="relative">
    <button type="button" @click="open = !open"
      class="w-full flex items-center justify-between border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg px-3 py-2 text-left">
      <span :class="!selectedLabel ? 'text-gray-400 dark:text-slate-500' : ''">
        {{ selectedLabel || placeholder }}
      </span>
      <span class="text-xs text-gray-400 dark:text-slate-500">▾</span>
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div v-if="open"
        class="absolute z-30 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg shadow-lg overflow-hidden">
        <div class="max-h-48 overflow-y-auto">
          <button v-for="item in items" :key="item.id" type="button"
            @click="pilih(item)"
            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors"
            :class="modelValue === item.id ? 'bg-primary-50 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300' : 'dark:text-slate-100'">
            {{ item.nama }}
          </button>
          <p v-if="!items.length" class="px-3 py-2 text-sm text-gray-400 dark:text-slate-500">Belum ada kategori.</p>
        </div>
        <div class="border-t border-gray-200 dark:border-slate-600">
          <button type="button" @click="tambahBaru"
            class="w-full text-left px-3 py-2 text-sm text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 font-medium transition-colors">
            + Tambah Kategori
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: [Number, String],
  items: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Pilih kategori' },
})
const emit = defineEmits(['update:modelValue', 'tambah-baru'])

const open = ref(false)
const root = ref(null)

const selectedLabel = computed(() => props.items.find((i) => i.id === props.modelValue)?.nama || '')

function pilih(item) {
  emit('update:modelValue', item.id)
  open.value = false
}

function tambahBaru() {
  open.value = false
  emit('tambah-baru')
}

function onClickOutside(e) {
  if (root.value && !root.value.contains(e.target)) open.value = false
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>