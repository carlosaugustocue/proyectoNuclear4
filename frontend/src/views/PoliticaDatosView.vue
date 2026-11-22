<script setup lang="ts">
/* Política de tratamiento de datos personales (Ley 1581 de 2012, Decreto 1074 de 2015; RF-22, R-01). */
import { VERSION_POLITICA } from '@/utils/privacidad'
</script>

<template>
  <article aria-labelledby="titulo-politica" class="space-y-4 text-sm leading-relaxed text-slate-700">
    <header>
      <h1 id="titulo-politica" class="text-lg font-bold text-slate-900">Política de tratamiento de datos personales</h1>
      <p class="text-xs text-slate-500">Versión {{ VERSION_POLITICA }} · Proyecto académico de Ingeniería de Software, UniHumboldt (Armenia)</p>
    </header>

    <section>
      <h2 class="font-semibold text-slate-900">Qué datos tratamos</h2>
      <ul class="ml-4 list-disc space-y-1">
        <li>Al crear una cuenta: tu correo, el hash de tu contraseña (nunca la contraseña), la fecha de tu autorización y la versión de esta política.</li>
        <li>Al reportar precios: el producto, el establecimiento, el precio y la hora. No guardamos la ubicación de tu dispositivo.</li>
        <li>Sin cuenta, tus reportes se agrupan con un seudónimo derivado de tu conexión que no permite identificarte.</li>
      </ul>
    </section>

    <section>
      <h2 class="font-semibold text-slate-900">Para qué los usamos</h2>
      <p>Solo para operar la comparación de precios: validar que los reportes sean confiables, calcular la reputación de los aportes y moderar abusos. Tu correo nunca se publica y tus reportes nunca se muestran asociados a tu identidad.</p>
    </section>

    <section>
      <h2 class="font-semibold text-slate-900">Tus derechos</h2>
      <p>Puedes conocer, actualizar y suprimir tus datos en cualquier momento desde <router-link to="/cuenta" class="font-semibold text-primary-700 underline">Tu cuenta</router-link>. Si eliminas la cuenta, borramos tus datos personales; los precios que reportaste se conservan sin quedar asociados a ninguna persona.</p>
    </section>

    <section>
      <h2 class="font-semibold text-slate-900">Seguridad</h2>
      <p>Las contraseñas se guardan con Argon2id, las sesiones vencen a las 8 horas y la conexión siempre va cifrada (HTTPS).</p>
    </section>
  </article>
</template>
