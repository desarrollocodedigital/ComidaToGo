import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => ({
  base: mode === 'production' ? '/Dev/COMIDATOGO/' : '/',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    proxy: {
      // Proxy API requests to PHP backend
      '/api.php': {
        target: 'http://localhost/ComidaToGo/public',
        changeOrigin: true,
      },
      '/uploads': {
        target: 'http://localhost/ComidaToGo/public',
        changeOrigin: true,
      }
    }
  },
  build: {
    outDir: '../public',
    emptyOutDir: false, // Importante: no borrar api.php o index.php
    rollupOptions: {
      output: {
        entryFileNames: `assets/[name].js`,
        chunkFileNames: `assets/[name].js`,
        assetFileNames: `assets/[name].[ext]`
      }
    }
  }
}))
