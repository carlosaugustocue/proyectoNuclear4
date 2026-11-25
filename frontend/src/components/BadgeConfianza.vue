<script setup lang="ts">
import { ShieldCheck, ShieldAlert, ShieldQuestion, ShieldX } from 'lucide-vue-next'
import type { EstadoConfianza } from '@/types'

const props = defineProps<{ estado: EstadoConfianza }>()

/* Confianza (RN-05 a RN-07): cada estado con su escudo, para que se distinga sin color. */
const config: Record<string, { label: string; icono: typeof ShieldCheck; classes: string }> = {
  CONFIABLE: { label: 'Corroborado', icono: ShieldCheck, classes: 'bg-success-50 text-success-800 ring-success-600/20' },
  POR_VERIFICAR: { label: 'Por verificar', icono: ShieldAlert, classes: 'bg-warning-50 text-warning-800 ring-warning-500/30' },
  NO_CORROBORADO: { label: 'Sin corroborar', icono: ShieldQuestion, classes: 'bg-slate-100 text-slate-700 ring-slate-300/60' },
  NO_CONFIABLE: { label: 'No confiable', icono: ShieldX, classes: 'bg-danger-50 text-danger-700 ring-danger-500/30' },
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
    :class="config[props.estado]?.classes ?? 'bg-slate-100 text-slate-700 ring-slate-300/60'"
  >
    <component :is="config[props.estado]?.icono ?? ShieldQuestion" class="h-3 w-3" aria-hidden="true" />
    {{ config[props.estado]?.label ?? estado }}
  </span>
</template>
