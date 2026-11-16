<script setup lang="ts">
defineProps<{ nombre: string; activa: boolean }>()
defineEmits<{ click: [] }>()
</script>

<template>
  <button
    type="button"
    class="flex shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors"
    :class="activa
      ? 'border-primary-600 bg-primary-600 text-white'
      : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
    :aria-pressed="activa"
    @click="$emit('click')"
  >
    {{ nombre }}
  </button>
</template>
