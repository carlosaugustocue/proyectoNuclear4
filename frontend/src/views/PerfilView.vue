<script setup lang="ts">
import { User, LogIn, Shield } from 'lucide-vue-next'
</script>

<template>
  <section aria-labelledby="titulo-perfil">
    <h1 id="titulo-perfil" class="mb-1 text-lg font-bold text-slate-900">Tu perfil</h1>
    <p class="mb-6 text-xs text-slate-400">Inicia sesion para reportar precios y ganar reputacion.</p>

    <div class="py-8 text-center">
      <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
        <User class="h-8 w-8 text-slate-400" aria-hidden="true" />
      </div>
      <p class="text-sm text-slate-500">No has iniciado sesion</p>
      <p class="mt-1 text-xs text-slate-400">
        Puedes consultar precios sin cuenta, pero necesitas una para reportar.
      </p>

      <button class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-700">
        <LogIn class="h-4 w-4" aria-hidden="true" />
        Iniciar sesion
      </button>

      <div class="mx-auto mt-8 max-w-xs rounded-lg border border-slate-200 bg-white p-4 text-left">
        <div class="mb-2 flex items-center gap-2">
          <Shield class="h-4 w-4 text-primary-600" aria-hidden="true" />
          <p class="text-xs font-semibold text-slate-700">Privacidad</p>
        </div>
        <p class="text-xs leading-relaxed text-slate-400">
          Tu identidad nunca se asocia publicamente a los precios que reportas.
          No almacenamos la ubicacion de tu dispositivo.
          Cumplimiento Ley 1581/2012.
        </p>
      </div>
    </div>
  </section>
</template>
