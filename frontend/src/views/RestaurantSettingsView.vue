<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { Save, MapPin, Image, Store } from 'lucide-vue-next'

const auth = useAuthStore()
const loading = ref(true)
const saving = ref(false)
const company = ref({})

// Cargar datos actuales
const fetchCompany = async () => {
    // Necesitamos endpoint para getById o usar el slug del user
    // Por simplicidad, el auth store tiene company_id, pero no slug. 
    // endpoint GET /tenant debería soportar ID también o creamos uno nuevo GET /companies/{id}
    // MVP: Vamos a asumir que GET /tenant/{slug} funciona, pero necesitamos el slug.
    // O mejor, implementamos `getById` en backend?
    // Hack: Usamos el search o asumimos que el usuario sabe su slug? No.
    // Mejor: GET /api.php/tenant/id/{id} -> Ajustaremos el backend o usamos el slug si lo guardamos en login.
    
    // Auth Login response returns: user.company_id.
    // Let's create a Helper in CompanyController: getById.
    // For now, let's try to fetch by ID if I modify Backend, or iterate.
    
    // I will modify CompanyController to accept ID if numeric in getTenant method logic or similar.
    // Actually, let's just make getTenant handle ID too if param is numeric?
    
    try {
        const { data } = await axios.get(`/api.php/tenant/${auth.user.company_id}`) 
        company.value = data
        // Ensure schedule_config has timer defaults
        if (!company.value.schedule_config || typeof company.value.schedule_config !== 'object') {
            company.value.schedule_config = {}
        }
        if (!company.value.schedule_config.timer_green) company.value.schedule_config.timer_green = 10
        if (!company.value.schedule_config.timer_yellow) company.value.schedule_config.timer_yellow = 20
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const saveSettings = async () => {
    saving.value = true
    try {
        await axios.put(`/api.php/tenant/${company.value.id}`, company.value)
        alert("Configuración guardada exitosamente")
    } catch (e) {
        alert("Error al guardar")
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    if (auth.user?.company_id) {
        fetchCompany()
    }
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-6">
        <header class="max-w-4xl mx-auto flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Configuración del Restaurante</h1>
            <router-link to="/admin/dashboard" class="text-orange-600 font-bold hover:underline">
                Volver al Panel
            </router-link>
        </header>

        <div v-if="loading" class="text-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto"></div>
        </div>

        <div v-else class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Columna 1: Info Básica -->
            <div class="bg-white p-6 rounded-2xl shadow-sm space-y-6">
                <div class="flex items-center gap-2 text-xl font-bold text-gray-800 pb-4 border-b">
                    <Store class="w-6 h-6" /> Información General
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Negocio</label>
                    <input v-model="company.name" type="text" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                    <textarea v-model="company.description" rows="3" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="El mejor sabor de la ciudad..."></textarea>
                </div>
                
                <div>
                     <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                     <div class="flex items-center gap-4">
                         <label class="flex items-center gap-2 cursor-pointer">
                             <input type="radio" v-model="company.is_open" :value="1" class="text-orange-600 focus:ring-orange-500">
                             <span class="font-medium text-green-600">Abierto</span>
                         </label>
                         <label class="flex items-center gap-2 cursor-pointer">
                             <input type="radio" v-model="company.is_open" :value="0" class="text-orange-600 focus:ring-orange-500">
                             <span class="font-medium text-red-600">Cerrado</span>
                         </label>
                     </div>
                </div>
            </div>

            <!-- Columna 2: Ubicación e Imágenes -->
            <div class="bg-white p-6 rounded-2xl shadow-sm space-y-6">
                <div class="flex items-center gap-2 text-xl font-bold text-gray-800 pb-4 border-b">
                    <MapPin class="w-6 h-6" /> Ubicación y Medios
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Dirección</label>
                    <input v-model="company.address" type="text" class="w-full p-3 border rounded-lg" placeholder="Calle Principal #123">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Latitud</label>
                        <input v-model="company.latitude" type="text" class="w-full p-3 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Longitud</label>
                        <input v-model="company.longitude" type="text" class="w-full p-3 border rounded-lg">
                    </div>
                </div>

                <div class="border-t pt-4 mt-4">
                     <label class="block text-sm font-bold text-gray-700 mb-1">URL del Banner (Cabecera)</label>
                     <input v-model="company.banner_url" type="text" class="w-full p-3 border rounded-lg text-sm" placeholder="https://example.com/banner.jpg">
                     <div v-if="company.banner_url" class="mt-2 h-24 rounded-lg overflow-hidden bg-gray-100">
                         <img :src="company.banner_url" class="w-full h-full object-cover">
                     </div>
                </div>

                 <div>
                     <label class="block text-sm font-bold text-gray-700 mb-1">URL del Logo</label>
                     <input v-model="company.logo_url" type="text" class="w-full p-3 border rounded-lg text-sm" placeholder="https://example.com/logo.jpg">
                </div>
            </div>

            <!-- Columna 3: Timer Semáforo -->
            <div class="bg-white p-6 rounded-2xl shadow-sm space-y-6 md:col-span-2">
                <div class="flex items-center gap-2 text-xl font-bold text-gray-800 pb-4 border-b">
                    ⏱ Semáforo de Tiempos (Cocina)
                </div>
                <p class="text-sm text-gray-500">Configura cuántos minutos deben pasar para que el indicador de cada pedido cambie de color.</p>
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-full mx-auto mb-2"></div>
                        <p class="text-sm font-bold text-gray-700 mb-1">🟢 Verde</p>
                        <p class="text-xs text-gray-400 mb-2">De 0 a X minutos</p>
                        <p class="text-lg font-bold text-green-600">0 min</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-yellow-400 rounded-full mx-auto mb-2"></div>
                        <p class="text-sm font-bold text-gray-700 mb-1">🟡 Amarillo</p>
                        <p class="text-xs text-gray-400 mb-2">Después de:</p>
                        <input v-model.number="company.schedule_config.timer_green" type="number" min="1" max="120" class="w-full p-3 border rounded-lg text-center text-lg font-bold focus:ring-2 focus:ring-yellow-400 outline-none">
                        <p class="text-xs text-gray-400 mt-1">minutos</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-red-500 rounded-full mx-auto mb-2"></div>
                        <p class="text-sm font-bold text-gray-700 mb-1">🔴 Rojo</p>
                        <p class="text-xs text-gray-400 mb-2">Después de:</p>
                        <input v-model.number="company.schedule_config.timer_yellow" type="number" min="1" max="120" class="w-full p-3 border rounded-lg text-center text-lg font-bold focus:ring-2 focus:ring-red-400 outline-none">
                        <p class="text-xs text-gray-400 mt-1">minutos</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto mt-8 flex justify-end">
             <button 
                @click="saveSettings" 
                :disabled="saving"
                class="bg-black text-white px-8 py-3 rounded-xl font-bold shadow-lg hover:bg-gray-800 transition-all flex items-center gap-2"
            >
                <Save class="w-5 h-5" />
                {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
        </div>
    </div>
</template>
