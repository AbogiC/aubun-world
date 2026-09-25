import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => ({
  plugins: [
    vue(),
    // Only enable Vue DevTools in development — saves ~30 KiB + main-thread work in production
    ...(mode === 'development' ? [vueDevTools()] : []),
  ],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  build: {
    // Modern target = smaller polyfill-free output
    target: 'esnext',
    cssMinify: 'lightningcss',
    // Emit hashed, immutable assets (long cache) — index.html stays short-lived
    assetsInlineLimit: 4096,
    cssCodeSplit: true,
    chunkSizeWarningLimit: 600,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('vue-router') || id.includes('vue') || id.includes('pinia')) return 'vue-vendor'
            if (id.includes('bootstrap')) return 'bootstrap'
            return 'vendor'
          }
        },
      },
    },
  },
}))
