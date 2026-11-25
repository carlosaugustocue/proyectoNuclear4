<script setup lang="ts">
import { onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useReportesStore } from '@/stores/reportes'

const reportes = useReportesStore()
onMounted(() => reportes.iniciar())
</script>

<template>
  <AppLayout>
    <router-view v-slot="{ Component, route }">
      <transition name="vista" mode="out-in">
        <component :is="Component" :key="route.path" />
      </transition>
    </router-view>
  </AppLayout>
</template>
