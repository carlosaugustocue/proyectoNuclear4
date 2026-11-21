import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'inicio',
      component: () => import('@/views/InicioView.vue'),
    },
    {
      path: '/buscar',
      name: 'buscar',
      component: () => import('@/views/BuscarView.vue'),
    },
    {
      path: '/comparar/:productoId',
      name: 'comparar',
      component: () => import('@/views/CompararPreciosView.vue'),
    },
    {
      path: '/canasta',
      name: 'canasta',
      component: () => import('@/views/CanastaView.vue'),
    },
    {
      path: '/comercio',
      name: 'comercio',
      component: () => import('@/views/ComercioView.vue'),
    },
    {
      path: '/registrar',
      name: 'registrar',
      component: () => import('@/views/RegistrarPrecioView.vue'),
    },
    {
      path: '/establecimientos',
      name: 'establecimientos',
      component: () => import('@/views/EstablecimientosView.vue'),
    },
    {
      path: '/perfil',
      name: 'perfil',
      component: () => import('@/views/PerfilView.vue'),
    },
  ],
})

export default router
