<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'
import LiveChatBox from './components/LiveChatBox.vue'
import ToastProvider from './components/ToastProvider.vue'
import DialogProvider from './components/DialogProvider.vue'
import OnboardingModal from './components/OnboardingModal.vue'

const auth = useAuthStore()
const cart = useCartStore()

onMounted(() => {
  // Inicialización de datos persistentes (Cargar carrito de la nube si existe sesión)
  if (auth.isAuthenticated && auth.user.cart_data) {
    cart.loadFromServer(auth.user.cart_data)
  }
})
</script>

<template>
  <router-view></router-view>
  
  <ToastProvider />
  <DialogProvider />
  <OnboardingModal />
  
  <!-- Global Floating Customer Chat (solo visible si no estás en rutas admin, no es demo, y estás LOGUEADO) -->
  <LiveChatBox 
    v-if="!$route.path.includes('/admin') && !$route.path.includes('/demo') && !['tenant', 'checkout', 'pos', 'kitchen', 'caja', 'order-status', 'my-orders'].includes($route.name) && auth.isAuthenticated" 
    :isAdmin="false" 
  />
</template>
