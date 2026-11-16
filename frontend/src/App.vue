<script setup lang="ts">
import { onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useReportesStore } from '@/stores/reportes'

const reportes = useReportesStore()
onMounted(() => reportes.iniciar())
</script>

<template>
  <AppLayout>
    <router-view />
  </AppLayout>
</template>
