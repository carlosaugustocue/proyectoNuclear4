<script setup lang="ts">
import { Clock, CircleCheck, History } from 'lucide-vue-next'
import type { EstadoVigencia } from '@/utils/catalogo'

const props = defineProps<{ estado: EstadoVigencia }>()

/* Vigencia (RN-04): ícono + texto + color, para no depender solo del color (WCAG 1.4.1). */
const config = {
  VIGENTE: { label: 'Vigente', icono: CircleCheck, classes: 'bg-success-50 text-success-800 ring-success-600/20' },
  PUEDE_ESTAR_DESACTUALIZADO: { label: 'Puede estar desactualizado', icono: Clock, classes: 'bg-warning-50 text-warning-800 ring-warning-500/30' },
  HISTORIAL: { label: 'Historial', icono: History, classes: 'bg-slate-100 text-slate-700 ring-slate-300/60' },
} as const
</script>

<template>
  <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset" :class="config[props.estado].classes">
    <component :is="config[props.estado].icono" class="h-3 w-3" aria-hidden="true" />
    {{ config[props.estado].label }}
  </span>
</template>
