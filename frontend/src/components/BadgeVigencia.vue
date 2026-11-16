<script setup lang="ts">
import type { EstadoVigencia } from '@/utils/catalogo'

const props = defineProps<{ estado: EstadoVigencia }>()

const config: Record<EstadoVigencia, { label: string; classes: string }> = {
  VIGENTE: { label: 'Vigente', classes: 'bg-success-50 text-success-700' },
  PUEDE_ESTAR_DESACTUALIZADO: { label: 'Puede estar desactualizado', classes: 'bg-warning-50 text-warning-700' },
  HISTORIAL: { label: 'Historial', classes: 'bg-slate-100 text-slate-600' },
}
</script>

<template>
  <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="config[props.estado].classes">
    {{ config[props.estado].label }}
  </span>
</template>
