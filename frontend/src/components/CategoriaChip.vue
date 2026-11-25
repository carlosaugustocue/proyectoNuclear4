<script setup lang="ts">
defineProps<{ nombre: string; activa: boolean }>()
defineEmits<{ click: [] }>()
</script>

<template>
  <button
    type="button"
    class="flex h-9 shrink-0 items-center gap-1.5 rounded-full px-4 text-sm font-semibold transition duration-200 active:scale-95"
    :class="activa
      ? 'bg-slate-900 text-white shadow-tarjeta'
      : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300'"
    :aria-pressed="activa"
    @click="$emit('click')"
  >
    {{ nombre }}
  </button>
</template>
