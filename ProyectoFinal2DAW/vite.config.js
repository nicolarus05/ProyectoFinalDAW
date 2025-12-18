import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/caja.css',
        'resources/css/calendar.css',
        'resources/css/citas-create.css',
        'resources/css/clientes.css',
        'resources/css/cobros.css',
        'resources/css/dashboard.css',
        'resources/css/deudas.css',
        'resources/css/profile.css',
        'resources/js/app.js',
        'resources/js/calendar.js',
        'resources/js/citas-create.js',
        'resources/js/clientes.js',
        'resources/js/cobros.js',
        'resources/js/deudas.js'
      ],
      refresh: true,
    }),
  ],
  build: {
    // Minificar en producción
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true, // Eliminar console.log en producción
        drop_debugger: true, // Eliminar debugger en producción
      },
    },
    // Optimizar CSS
    cssMinify: true,
    // Generar sourcemaps solo en desarrollo
    sourcemap: false,
    // Optimizar chunking
    rollupOptions: {
      output: {
        manualChunks: {
          // Separar vendor code
          vendor: ['lodash'],
        },
        // Nombres de archivo con hash para cache busting
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
      },
    },
    // Tamaño de advertencia de chunk
    chunkSizeWarningLimit: 600,
  },
  // Optimizaciones de servidor de desarrollo
  server: {
    hmr: {
      host: 'localhost',
    },
  },
});
