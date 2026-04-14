<script setup>
import { useToast } from '../composables/useToast'
import { CheckCircle, AlertCircle, Info, X } from 'lucide-vue-next'

const { toasts, remove } = useToast()

const getIcon = (type) => {
  if (type === 'success') return CheckCircle
  if (type === 'error') return AlertCircle
  return Info
}

const getStyles = (type) => {
  if (type === 'success') return 'bg-emerald-50 border-emerald-200 text-emerald-800'
  if (type === 'error') return 'bg-red-50 border-red-200 text-red-800'
  return 'bg-blue-50 border-blue-200 text-blue-800'
}

const getIconColor = (type) => {
  if (type === 'success') return 'text-emerald-500'
  if (type === 'error') return 'text-red-500'
  return 'text-blue-500'
}
</script>

<template>
  <div class="fixed top-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none">
    <TransitionGroup 
      name="toast"
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-x-12 opacity-0"
      enter-to-class="transform translate-x-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-x-0 opacity-100"
      leave-to-class="transform translate-x-12 opacity-0"
    >
      <div 
        v-for="toast in toasts" 
        :key="toast.id"
        :class="['pointer-events-auto flex items-center min-w-[300px] max-w-md p-4 rounded-2xl border shadow-lg backdrop-blur-sm', getStyles(toast.type)]"
      >
        <component :is="getIcon(toast.type)" :class="['w-5 h-5 mr-3 shrink-0', getIconColor(toast.type)]" />
        
        <p class="text-sm font-semibold flex-1">{{ toast.message }}</p>
        
        <button @click="remove(toast.id)" class="ml-4 text-gray-400 hover:text-gray-600 transition-colors">
          <X class="w-4 h-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-move {
  transition: all 0.3s ease;
}
</style>
