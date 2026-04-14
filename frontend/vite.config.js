import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
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
    outDir: '../public/assets',
    emptyOutDir: true,
    assetsDir: '', // Assets directamente en assets/
    rollupOptions: {
      output: {
        entryFileNames: `main.js`,
        chunkFileNames: `[name].js`,
        assetFileNames: `[name].[ext]`
      }
    }
  }
})
