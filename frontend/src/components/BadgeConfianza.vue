<script setup lang="ts">
import type { EstadoConfianza } from '@/types'

const props = defineProps<{ estado: EstadoConfianza }>()

const config: Record<string, { label: string; classes: string }> = {
  CONFIABLE: { label: 'Corroborado', classes: 'bg-success-50 text-success-700' },
  POR_VERIFICAR: { label: 'Por verificar', classes: 'bg-warning-50 text-warning-700' },
  NO_CORROBORADO: { label: 'Sin corroborar', classes: 'bg-slate-100 text-slate-600' },
  NO_CONFIABLE: { label: 'No confiable', classes: 'bg-danger-50 text-danger-700' },
}
</script>

<template>
  <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="config[props.estado]?.classes">
    {{ config[props.estado]?.label ?? estado }}
  </span>
</template>
