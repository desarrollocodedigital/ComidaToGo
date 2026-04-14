<script setup>
import { useCartStore } from '../stores/cart'
import { X, Trash2, ShoppingBag } from 'lucide-vue-next'

defineProps({
  isOpen: Boolean
})
defineEmits(['close', 'checkout'])

const cartStore = useCartStore()
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex justify-end">
    <!-- Overlay -->
    <div @click="$emit('close')" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"></div>

    <!-- Drawer Panel -->
    <div class="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300">
      
      <!-- Header -->
      <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
        <div class="flex items-center gap-2">
           <ShoppingBag class="w-5 h-5 text-orange-500" />
           <h2 class="text-lg font-bold">Tu Pedido</h2>
        </div>
        <button @click="$emit('close')" class="p-2 hover:bg-gray-100 rounded-full">
          <X class="w-5 h-5 text-gray-500" />
        </button>
      </div>

      <!-- Items List -->
      <div class="flex-1 overflow-y-auto p-5 space-y-6">
        <div v-if="cartStore.items.length === 0" class="text-center py-20 opacity-50">
           <ShoppingBag class="w-16 h-16 mx-auto mb-4 text-gray-300" />
           <p>Tu carrito está vacío</p>
        </div>

        <div v-for="item in cartStore.items" :key="item.key" class="flex gap-4">
           <!-- Qty -->
           <div class="flex flex-col items-center gap-1 border border-gray-200 rounded-lg p-1 h-fit shrink-0">
             <button @click="item.quantity++" class="text-xs p-1 hover:text-orange-600">+</button>
             <span class="font-bold text-sm">{{ item.quantity }}</span>
             <button @click="item.quantity > 1 ? item.quantity-- : cartStore.removeItem(item.key)" class="text-xs p-1 hover:text-red-500">-</button>
           </div>
           
           <!-- Info -->
           <div class="flex-1 flex gap-3">
             <img v-if="item.imageUrl" :src="item.imageUrl" class="w-14 h-14 rounded-lg object-cover bg-gray-100 shrink-0 border border-gray-50">
             <div class="flex-1">
               <div class="flex justify-between items-start mb-1">
                 <h3 class="font-bold text-gray-900 leading-tight pr-2">{{ item.name }}</h3>
                 <span class="font-bold text-gray-900 shrink-0">${{ (item.unitPrice * item.quantity).toFixed(2) }}</span>
               </div>
               
               <!-- Modifiers List -->
             <div v-if="item.modifiers && item.modifiers.length > 0" class="flex flex-wrap gap-1 mt-1">
               <span 
                 v-for="mod in item.modifiers" 
                 :key="mod.id"
                 class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded"
               >
                 {{ mod.name }}
               </span>
             </div>
             
             <!-- Special Instructions -->
             <p v-if="item.specialInstructions" class="text-xs text-slate-500 italic mt-1 pb-1">
               "{{ item.specialInstructions }}"
             </p>
             </div>
           </div>

           <!-- Remove -->
           <button @click="cartStore.removeItem(item.key)" class="text-gray-400 hover:text-red-500 self-start mt-1">
             <Trash2 class="w-4 h-4" />
           </button>
        </div>
      </div>

      <!-- Footer checkount -->
      <div v-if="cartStore.items.length > 0" class="p-6 border-t border-gray-100 bg-gray-50 shrink-0">
        <div class="flex justify-between mb-4 text-lg font-bold text-gray-900">
           <span>Total</span>
           <span>${{ cartStore.cartTotal.toFixed(2) }}</span>
        </div>
        <button 
          @click="$emit('checkout')"
          class="w-full bg-orange-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:bg-orange-700 transition-colors"
        >
          Ir a Pagar
        </button>
      </div>

    </div>
  </div>
</template>
