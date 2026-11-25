<script setup lang="ts">
import BottomTabBar from '@/components/BottomTabBar.vue'
import TopBar from '@/components/TopBar.vue'
</script>

<template>
  <div class="flex min-h-dvh flex-col bg-surface">
    <TopBar />
    <main class="mx-auto w-full max-w-2xl flex-1 px-4 pb-28 pt-5">
      <slot />
    </main>
    <BottomTabBar />
  </div>
</template>
