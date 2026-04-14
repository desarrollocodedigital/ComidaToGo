<script setup>
import { useAuthStore } from './stores/auth'
import LiveChatBox from './components/LiveChatBox.vue'

const auth = useAuthStore()
</script>

<template>
  <router-view></router-view>
  
  <!-- Global Floating Customer Chat (solo visible si no estás en rutas admin, no es demo, y estás LOGUEADO) -->
  <LiveChatBox v-if="!$route.path.includes('/admin') && !$route.path.includes('/demo') && auth.isAuthenticated" :isAdmin="false" />
</template>
