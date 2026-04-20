<script setup>
import { useDialogStore } from '../stores/dialog'
import { AlertCircle, CheckCircle2, Info, HelpCircle, X } from 'lucide-vue-next'

const dialog = useDialogStore()
</script>

<template>
  <Transition name="fade">
    <div v-if="dialog.isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="dialog.handleCancel"></div>
      
      <!-- Modal Panel -->
      <div 
        class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden transform transition-all animate-scale-in"
      >
        <div class="p-6">
          <!-- Icon Header -->
          <div class="flex justify-center mb-4">
            <div 
              class="w-16 h-16 rounded-full flex items-center justify-center"
              :class="{
                'bg-blue-50 text-blue-500': dialog.type === 'info',
                'bg-orange-50 text-orange-500': dialog.type === 'confirm' || dialog.type === 'warning',
                'bg-green-50 text-green-500': dialog.type === 'success'
              }"
            >
              <Info v-if="dialog.type === 'info'" class="w-8 h-8" />
              <HelpCircle v-if="dialog.type === 'confirm'" class="w-8 h-8" />
              <AlertCircle v-if="dialog.type === 'warning'" class="w-8 h-8" />
              <CheckCircle2 v-if="dialog.type === 'success'" class="w-8 h-8" />
            </div>
          </div>

          <!-- Text Content -->
          <div class="text-center">
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ dialog.title }}</h3>
            <p class="text-gray-600 leading-relaxed whitespace-pre-line" :class="{'mb-4': dialog.showInput}">{{ dialog.message }}</p>
            
            <div v-if="dialog.showInput" class="mt-4 px-2">
              <textarea 
                v-model="dialog.inputValue" 
                :placeholder="dialog.inputPlaceholder"
                rows="3"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:outline-none text-slate-700 font-medium text-sm transition-all resize-none"
                autofocus
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex border-t border-gray-100">
          <button 
            v-if="dialog.cancelText"
            @click="dialog.handleCancel"
            class="flex-1 px-6 py-4 text-gray-500 font-medium hover:bg-gray-50 transition-colors border-r border-gray-100"
          >
            {{ dialog.cancelText }}
          </button>
          <button 
            @click="dialog.handleConfirm"
            class="flex-1 px-6 py-4 font-bold transition-colors hover:opacity-90"
            :class="{
              'bg-blue-500 text-white': dialog.type === 'info',
              'bg-orange-500 text-white': dialog.type === 'confirm' || dialog.type === 'warning',
              'bg-green-500 text-white': dialog.type === 'success'
            }"
          >
            {{ dialog.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

@keyframes scale-in {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.animate-scale-in {
  animation: scale-in 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
