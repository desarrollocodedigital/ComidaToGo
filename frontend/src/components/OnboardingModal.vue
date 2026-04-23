<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { MapPin, Phone, Navigation, Loader2, CheckCircle2 } from 'lucide-vue-next'
import axios from 'axios'

const auth = useAuthStore()
const phone = ref('')
const address = ref('')
const references = ref('')
const loading = ref(false)
const isLocating = ref(false)
const success = ref(false)
const detectedState = ref('')
const detectedCoords = ref(null)

const isValid = computed(() => {
  return phone.value.trim().length >= 8 && address.value.trim().length >= 10
})

const detectLocation = () => {
  if (!navigator.geolocation) return
  
  isLocating.value = true
  navigator.geolocation.getCurrentPosition(
    async function (position) {
      const lat = position.coords.latitude
      const lng = position.coords.longitude
      detectedCoords.value = { lat, lng }
      
      try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
        address.value = response.data.display_name || ""
        
        // Extraer estado para el filtrado del marketplace
        const addr = response.data.address
        const state = addr.state || addr.province || addr.region || ''
        if (state) {
            detectedState.value = state
        }
      } catch (e) {
        console.error("Error in reverse geocoding", e)
      } finally {
        isLocating.value = false
      }
    },
    (error) => {
      console.error("Error getting location", error)
      isLocating.value = false
    }
  )
}

const handleSubmit = async () => {
  if (!isValid.value) return
  
  loading.value = true
  try {
    // Si no se usó el GPS, intentamos geocodificar el texto para obtener el estado regional
    if (!detectedState.value && address.value.trim()) {
        try {
            const geoRes = await axios.get(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address.value.trim())}&limit=1&addressdetails=1`)
            if (geoRes.data && geoRes.data[0]) {
                const addr = geoRes.data[0].address
                detectedState.value = addr.state || addr.province || addr.region || ''
                detectedCoords.value = { lat: geoRes.data[0].lat, lng: geoRes.data[0].lon }
            }
        } catch (e) {
            console.warn("Could not geocode manual address for state", e)
        }
    }

    const res = await auth.completeOnboarding({
        phone: phone.value.trim(),
        address: address.value.trim(),
        references: references.value.trim(),
        state: detectedState.value,
        lat: detectedCoords.value?.lat,
        lng: detectedCoords.value?.lng
    })
    if (res) {
        success.value = true
        setTimeout(() => {
            auth.showOnboardingModal = false
        }, 1500)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Transition name="fade">
    <div v-if="auth.showOnboardingModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/80 backdrop-blur-md"></div>
      
      <!-- Modal Panel -->
      <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-scale-in">
        <div v-if="!success" class="p-8">
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-orange-100 rounded-3xl flex items-center justify-center mx-auto mb-6 rotate-3 shadow-lg shadow-orange-100">
                <MapPin class="w-10 h-10 text-orange-600" />
            </div>
            <h2 class="text-2xl font-black text-gray-900 mb-2 tracking-tight">¡Casi listo!</h2>
            <p class="text-sm text-gray-500 leading-relaxed">
                Necesitamos un par de detalles para que tu experiencia en <span class="font-bold text-orange-600">ComidaToGo</span> sea perfecta.
            </p>
          </div>

          <div class="space-y-5">
            <!-- Phone Field -->
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Teléfono (WhatsApp)</label>
              <div class="relative flex items-center">
                <Phone class="absolute left-4 w-5 h-5 text-gray-400" />
                <input 
                  v-model="phone" 
                  type="tel" 
                  placeholder="Tu número de 10 dígitos"
                  class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none font-bold text-gray-800 transition-all"
                >
              </div>
            </div>

            <!-- Address Field -->
            <div>
              <div class="flex justify-between items-center mb-2 px-1">
                <label class="text-xs font-black text-gray-400 uppercase tracking-widest">Dirección de entrega</label>
                <button 
                    @click="detectLocation" 
                    type="button"
                    class="flex items-center gap-1.5 text-[10px] font-black text-orange-600 uppercase tracking-wider hover:text-orange-700 transition-colors"
                >
                    <Loader2 v-if="isLocating" class="w-3 h-3 animate-spin" />
                    <Navigation v-else class="w-3 h-3" />
                    {{ isLocating ? 'Detectando...' : 'Usar GPS' }}
                </button>
              </div>
              <div class="relative">
                <textarea 
                  v-model="address" 
                  rows="3"
                  placeholder="Calle, número, colonia..."
                  class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none font-medium text-gray-700 text-sm transition-all resize-none"
                ></textarea>
              </div>
            </div>

            <!-- References Field -->
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Referencias (Opcional)</label>
                <input 
                  v-model="references" 
                  type="text" 
                  placeholder="Ej. Portón blanco, frente al parque"
                  class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none text-sm text-gray-600 transition-all"
                >
            </div>
          </div>

          <button 
            @click="handleSubmit"
            :disabled="!isValid || loading"
            class="w-full bg-black text-white py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-orange-600 disabled:opacity-30 disabled:grayscale transition-all mt-8 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
            <span>{{ loading ? 'Guardando...' : 'Comenzar a pedir' }}</span>
          </button>
        </div>

        <!-- Success View -->
        <div v-else class="p-12 text-center animate-in fade-in zoom-in duration-500">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-100">
                <CheckCircle2 class="w-12 h-12 text-green-600" />
            </div>
            <h2 class="text-3xl font-black text-gray-900 mb-2">¡Todo listo!</h2>
            <p class="text-gray-500">Bienvenido a la comunidad de ComidaToGo.</p>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

@keyframes scale-in {
  from { transform: scale(0.9) translateY(20px); opacity: 0; }
  to { transform: scale(1) translateY(0); opacity: 1; }
}
.animate-scale-in {
  animation: scale-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
