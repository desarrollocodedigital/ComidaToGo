<script setup>
import { ref, computed, watch } from 'vue'
import { X, Plus, Minus } from 'lucide-vue-next'

const props = defineProps({
  isOpen: Boolean,
  product: Object
})

const emit = defineEmits(['close', 'add-to-cart'])

const quantity = ref(1)
const selectedOptions = ref({}) // Estructura: { groupId: [optionId, optionId] }
const specialInstructions = ref('') // Added special instructions

// Reiniciar estado al abrir
watch(() => props.product, () => {
  quantity.value = 1
  selectedOptions.value = {}
  specialInstructions.value = ''
  // Pre-seleccionar defaults si fuera necesario (no implementado en DB aún)
})

const totalPrice = computed(() => {
  if (!props.product) return 0
  let total = parseFloat(props.product.price)
  
  // Sumar opciones
  if (props.product.modifier_groups) {
    props.product.modifier_groups.forEach(group => {
      const selectedIds = selectedOptions.value[group.id] || []
      selectedIds.forEach(optId => {
        const opt = group.options.find(o => o.id === optId)
        if (opt) {
          total += parseFloat(opt.price_adjustment)
        }
      })
    })
  }
  
  return total * quantity.value
})

const isValid = computed(() => {
  if (!props.product || !props.product.modifier_groups) return true
  
  // Validar min_selection
  for (const group of props.product.modifier_groups) {
    const selectedCount = (selectedOptions.value[group.id] || []).length
    if (selectedCount < group.min_selection) {
      return false
    }
  }
  return true
})

const toggleOption = (group, option) => {
  const currentSelected = selectedOptions.value[group.id] || []
  const isSelected = currentSelected.includes(option.id)

  if (group.max_selection === 1) {
    // Radio behavior
    selectedOptions.value[group.id] = [option.id]
  } else {
    // Checkbox behavior
    if (isSelected) {
      selectedOptions.value[group.id] = currentSelected.filter(id => id !== option.id)
    } else {
      if (currentSelected.length < group.max_selection) {
        selectedOptions.value[group.id] = [...currentSelected, option.id]
      }
    }
  }
}

const addToCart = () => {
  if (!isValid.value) return

  // Recopilar objetos completos de modificadores
  const modifiers = []
  if (props.product.modifier_groups) {
    props.product.modifier_groups.forEach(group => {
      const selectedIds = selectedOptions.value[group.id] || []
      selectedIds.forEach(optId => {
        const opt = group.options.find(o => o.id === optId)
        if (opt) {
          modifiers.push(opt)
        }
      })
    })
  }

  emit('add-to-cart', {
    product: props.product,
    quantity: quantity.value,
    modifiers,
    special_instructions: specialInstructions.value // Included in emit
  })
  emit('close')
}
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center pointer-events-none">
    <!-- Backdrop -->
    <div @click="$emit('close')" class="absolute inset-0 bg-black/50 pointer-events-auto transition-opacity"></div>
    
    <!-- Modal Panel -->
    <div class="bg-white w-full max-w-lg sm:rounded-xl shadow-2xl pointer-events-auto flex flex-col max-h-[90vh] overflow-hidden transform transition-all">
      
      <!-- Header with Image -->
      <div class="relative h-48 bg-gray-200 shrink-0">
        <img v-if="product?.image_url" :src="product.image_url" class="absolute inset-0 w-full h-full object-cover" />
        <div v-else class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-4">
          <!-- Fallback image logic if needed -->
        </div>
        <button @click="$emit('close')" class="absolute top-4 right-4 bg-white/90 p-2 rounded-full hover:bg-white transition-colors">
          <X class="w-5 h-5 text-gray-800" />
        </button>
      </div>

      <!-- Content Scrollable -->
      <div class="p-6 overflow-y-auto">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ product?.name }}</h2>
        <p class="text-gray-500 mb-6">{{ product?.description }}</p>

        <!-- Modifier Groups -->
        <div v-for="group in product?.modifier_groups" :key="group.id" class="mb-8">
          <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-gray-800">{{ group.name }}</h3>
            <span v-if="group.min_selection > 0" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
              Obligatorio {{ group.min_selection > 1 ? `(Mín ${group.min_selection})` : '' }}
            </span>
            <span v-else class="text-xs text-gray-500">Opcional (Máx {{ group.max_selection }})</span>
          </div>

          <div class="space-y-3">
            <label 
              v-for="option in group.options" 
              :key="option.id"
              class="flex items-center justify-between p-3 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors"
              :class="{ 'border-orange-500 bg-orange-50/50': (selectedOptions[group.id] || []).includes(option.id) }"
            >
              <div class="flex items-center gap-3">
                <div class="relative flex items-center">
                   <!-- Radio vs Checkbox Visuals -->
                   <div 
                     v-if="group.max_selection === 1"
                     class="w-5 h-5 rounded-full border border-gray-300 flex items-center justify-center"
                     :class="{ 'border-orange-500': (selectedOptions[group.id] || []).includes(option.id) }"
                   >
                      <div v-if="(selectedOptions[group.id] || []).includes(option.id)" class="w-2.5 h-2.5 rounded-full bg-orange-500"></div>
                   </div>
                   <div 
                     v-else
                     class="w-5 h-5 rounded border border-gray-300 flex items-center justify-center"
                     :class="{ 'bg-orange-500 border-orange-500': (selectedOptions[group.id] || []).includes(option.id) }"
                   >
                     <svg v-if="(selectedOptions[group.id] || []).includes(option.id)" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                     </svg>
                   </div>
                   
                   <input 
                     type="checkbox" 
                     class="hidden" 
                     :checked="(selectedOptions[group.id] || []).includes(option.id)"
                     @change="toggleOption(group, option)"
                   >
                </div>
                <span class="text-gray-900 font-medium">{{ option.name }}</span>
              </div>
              <span v-if="parseFloat(option.price_adjustment) > 0" class="text-gray-500 text-sm">
                +${{ parseFloat(option.price_adjustment) }}
              </span>
            </label>
          </div>
        </div>

        <!-- Special Instructions -->
        <div class="mt-6 border-t pt-4">
            <h3 class="font-bold text-gray-800 mb-2">Instrucciones Especiales</h3>
            <textarea 
               v-model="specialInstructions" 
               rows="2" 
               placeholder="Ej. Sin cebolla, extra servilletas..." 
               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none text-sm text-gray-700 resize-none"
            ></textarea>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="p-6 border-t border-gray-100 bg-white shrink-0 pb-8 sm:pb-6">
        <div class="flex items-center justify-between gap-4">
          <!-- Quantity Stepper -->
          <div class="flex items-center gap-4 bg-gray-100 rounded-full px-4 py-3">
             <button @click="quantity > 1 ? quantity-- : null" class="opacity-75 hover:opacity-100" :disabled="quantity <= 1">
               <Minus class="w-5 h-5" />
             </button>
             <span class="font-bold text-lg min-w-[1.5rem] text-center">{{ quantity }}</span>
             <button @click="quantity++" class="opacity-75 hover:opacity-100">
               <Plus class="w-5 h-5" />
             </button>
          </div>

          <!-- Add Button -->
          <button 
            @click="addToCart"
            :disabled="!isValid"
            class="flex-1 bg-black text-white py-3.5 rounded-full font-bold text-lg shadow-lg hover:bg-gray-900 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex justify-between px-6"
          >
            <span>Agregar</span>
            <span>${{ totalPrice.toFixed(2) }}</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>
