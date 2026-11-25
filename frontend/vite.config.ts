import { fileURLToPath, URL } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    tailwindcss(),
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'masked-icon.svg'],
      manifest: {
        name: 'Comparador de Precios – Armenia',
        short_name: 'Precios Armenia',
        description: 'Plataforma colaborativa de comparación de precios en Armenia, Quindío',
        theme_color: '#0c5c63',
        background_color: '#f7f4ee',
        display: 'standalone',
        start_url: '/',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png',
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable',
          },
        ],
      },
      workbox: {
        // Cachea shell y assets estáticos
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        // Estrategia Network-First para las llamadas a la API
        runtimeCaching: [
          {
            urlPattern: /^https?:\/\/.*\/v1\/.*/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24, // 24 h
              },
              networkTimeoutSeconds: 10,
            },
          },
          {
            // Fotos de productos empacadas con la app (Wikimedia Commons): se guardan al verlas, no al instalar
            // la PWA, para no descargar ~3 MB de golpe.
            urlPattern: ({ url, sameOrigin }) => sameOrigin && url.pathname.startsWith('/productos/'),
            handler: 'CacheFirst',
            options: {
              cacheName: 'fotos-locales-productos',
              expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 30 },
              cacheableResponse: { statuses: [200] },
            },
          },
          {
            // Fotos de productos (Open Food Facts): no cambian; se guardan para verlas sin conexión.
            urlPattern: /^https:\/\/images\.openfoodfacts\.org\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'imagenes-productos',
              expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 30 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true, // necesario para Docker (--host)
    port: 5173,
    // En desarrollo la PWA llama a /v1 en el mismo origen y Vite lo reenvía a la API
    // (nginx en docker compose escucha en 8080). Evita configurar CORS para desarrollo.
    proxy: {
      '/v1': { target: process.env.VITE_PROXY_TARGET ?? 'http://localhost:8080', changeOrigin: true },
    },
  },
})
