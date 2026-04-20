<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../composables/useToast'
import { Save, MapPin, Image, Store, Clock, ArrowLeft, Printer } from 'lucide-vue-next'

const auth = useAuthStore()
const toast = useToast()
const loading = ref(true)
const saving = ref(false)
const company = ref({})

const timezones = {
    'America/Mexico_City': '(GMT-06:00) CDMX, Guadalajara, Monterrey',
    'America/Tijuana': '(GMT-08:00) Tijuana, Baja California',
    'America/Hermosillo': '(GMT-07:00) Hermosillo, Sonora',
    'America/Mazatlan': '(GMT-07:00) Mazatlán, Chihuahua',
    'America/Cancun': '(GMT-05:00) Cancún, Quintana Roo',
    'America/Bogota': '(GMT-05:00) Bogotá, Colombia',
    'America/Santiago': '(GMT-03:00) Santiago, Chile',
    'America/Argentina/Buenos_Aires': '(GMT-03:00) Buenos Aires, Argentina',
}

const scheduleDays = [
    { key: 'mon', name: 'Lunes' },
    { key: 'tue', name: 'Martes' },
    { key: 'wed', name: 'Miércoles' },
    { key: 'thu', name: 'Jueves' },
    { key: 'fri', name: 'Viernes' },
    { key: 'sat', name: 'Sábado' },
    { key: 'sun', name: 'Domingo' },
]

// Cargar datos actuales
const fetchCompany = async () => {
    try {
        const { data } = await axios.get(`/api.php/tenant/${auth.user.company_id}`) 
        company.value = data
        
        // Inicializar estructura de horarios si no existe
        if (!company.value.schedule_config || typeof company.value.schedule_config !== 'object') {
            company.value.schedule_config = {}
        }
        
        scheduleDays.forEach(day => {
            if (!company.value.schedule_config[day.key]) {
                company.value.schedule_config[day.key] = { open: '09:00', close: '22:00', closed: false }
            }
        });

        if (!company.value.schedule_config.timer_green) company.value.schedule_config.timer_green = 10
        if (!company.value.schedule_config.timer_yellow) company.value.schedule_config.timer_yellow = 20
        
        // Valores por defecto para nuevos campos
        if (!company.value.timezone) company.value.timezone = 'America/Mexico_City'
        if (!company.value.status_mode) company.value.status_mode = 'AUTO'
        if (!company.value.printer_width) company.value.printer_width = '80'
        
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const uploadImage = async (event, type) => {
    const file = event.target.files[0]
    if (!file) return

    // Validar extensión
    const allowedExtensions = ['jpg', 'jpeg', 'png']
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedExtensions.includes(extension)) {
        toast.error("Formato no permitido. Solo JPG, JPEG y PNG.")
        return
    }

    const formData = new FormData()
    formData.append('image', file)
    
    // Si ya existe una imagen, pasamos la URL para que el servidor la borre
    const oldUrl = type === 'banner' ? company.value.banner_url : company.value.logo_url
    if (oldUrl) {
        formData.append('old_url', oldUrl)
    }

    try {
        const { data } = await axios.post('api.php/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (type === 'banner') {
            company.value.banner_url = data.url
        } else {
            company.value.logo_url = data.url
        }
    } catch (e) {
        console.error(e)
        toast.error("Error al subir la imagen")
    }
}

const saveSettings = async () => {
    saving.value = true
    try {
        await axios.put(`/api.php/tenant/${company.value.id}`, company.value)
        toast.success("Configuración guardada exitosamente")
    } catch (e) {
        console.error(e)
        toast.error("Error al guardar")
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
    <div class="min-h-screen bg-gray-50">
        <!-- Header Estándar -->
        <header class="bg-white shadow-sm border-b border-gray-100 mb-8">
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-800">Configuración</h1>
                    <p class="text-sm text-gray-500">Ajustes generales, horarios y apariencia de tu negocio</p>
                </div>
                <router-link to="/admin/dashboard" class="flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl shadow-sm hover:bg-black transition-all font-bold w-fit">
                    <ArrowLeft class="w-5 h-5" />
                    Volver al Panel
                </router-link>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-6 pb-12">

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
                    <label class="block text-sm font-bold text-gray-700 mb-1">Categoría del negocio</label>
                    <input v-model="company.category" type="text" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Ej: Tacos, Pizza, Café, Sushi...">
                    <p class="text-[10px] text-gray-400 mt-1 italic">* Esto se mostrará como etiqueta en tu tarjeta del Marketplace.</p>
                </div>
                
            </div>

            <!-- Columna 2: Ubicación e Imágenes -->
            <div class="bg-white p-6 rounded-2xl shadow-sm space-y-6">
                <div class="flex items-center gap-2 text-xl font-bold text-gray-800 pb-4 border-b">
                    <MapPin class="w-6 h-6" /> Ubicación y Medios
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Dirección</label>
                    <input v-model="company.address" type="text" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Calle Principal #123">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Estado (Entidad Federativa)</label>
                    <input v-model="company.state" type="text" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Ej: Puebla, Sinaloa, CDMX...">
                    <p class="text-[10px] text-gray-400 mt-1 italic">* Crucial para que los clientes te encuentren por GPS.</p>
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
                     <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                        <Image class="w-4 h-4" /> Banner del Negocio (Cabecera)
                     </label>
                     <div 
                        @click="$refs.bannerInput.click()"
                        class="relative h-32 rounded-2xl border-2 border-dashed border-gray-200 hover:border-orange-500 hover:bg-orange-50 transition-all cursor-pointer overflow-hidden group"
                     >
                         <img v-if="company.banner_url" :src="company.banner_url" class="w-full h-full object-cover group-hover:opacity-75 transition-opacity">
                         <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 group-hover:text-orange-600">
                             <Image class="w-8 h-8 mb-1" />
                             <span class="text-xs font-bold uppercase tracking-wider">{{ company.banner_url ? 'Cambiar Imagen' : 'Subir Banner' }}</span>
                         </div>
                         <input @change="uploadImage($event, 'banner')" type="file" ref="bannerInput" class="hidden" accept=".jpg,.jpeg,.png">
                     </div>
                     <p class="text-[10px] text-gray-400 mt-2 italic">* Recomendado: 1200x400px. JPG o PNG.</p>
                </div>

                 <div class="pt-2">
                     <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                        <Store class="w-4 h-4" /> Logo del Negocio
                     </label>
                     <div class="relative w-32 h-32">
                        <div 
                            @click="$refs.logoInput.click()"
                            class="w-full h-full rounded-2xl border-2 border-dashed border-gray-200 hover:border-orange-500 hover:bg-orange-50 transition-all cursor-pointer overflow-hidden group flex flex-col items-center justify-center text-gray-400 group-hover:text-orange-600 bg-white"
                        >
                            <img v-if="company.logo_url" :src="company.logo_url" class="w-full h-full object-cover group-hover:opacity-75 transition-opacity">
                            <div v-else class="flex flex-col items-center">
                                <Image class="w-6 h-6 mb-1" />
                                <span class="text-[10px] font-bold uppercase">Subir</span>
                            </div>
                            <!-- Overlay en hover si ya hay imagen -->
                            <div v-if="company.logo_url" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition-opacity">
                                <span class="text-[10px] font-bold uppercase">Cambiar</span>
                            </div>
                        </div>
                        <input @change="uploadImage($event, 'logo')" type="file" ref="logoInput" class="hidden" accept=".jpg,.jpeg,.png">
                     </div>
                </div>
            </div>

            <!-- Horarios y Estado (Full Width) -->
            <div class="bg-white p-6 rounded-2xl shadow-sm space-y-6 md:col-span-2">
                <div class="flex items-center gap-2 text-xl font-bold text-gray-800 pb-4 border-b">
                    <Clock class="w-6 h-6 text-orange-500" /> Operación y Horarios
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Configuración de Modo y Zona Horaria -->
                    <div class="space-y-6">
                        <!-- Status Mode -->
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Modo de Operación Actual</span>
                            <div class="grid grid-cols-3 gap-3">
                                <button 
                                    @click="company.status_mode = 'AUTO'"
                                    :class="company.status_mode === 'AUTO' ? 'bg-orange-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
                                    class="p-3 rounded-xl text-sm font-bold transition-all border border-transparent"
                                >
                                    Automático
                                </button>
                                <button 
                                    @click="company.status_mode = 'OPEN'"
                                    :class="company.status_mode === 'OPEN' ? 'bg-green-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
                                    class="p-3 rounded-xl text-sm font-bold transition-all border border-transparent"
                                >
                                    Abierto
                                </button>
                                <button 
                                    @click="company.status_mode = 'CLOSED'"
                                    :class="company.status_mode === 'CLOSED' ? 'bg-red-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
                                    class="p-3 rounded-xl text-sm font-bold transition-all border border-transparent"
                                >
                                    Cerrado
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-4 leading-relaxed">
                                <span class="font-bold text-gray-700">Nota:</span>
                                {{ company.status_mode === 'AUTO' ? ' El negocio se abrirá según los horarios programados a la derecha.' : ' El negocio ignorará el horario programado y se mantendrá en este estado permanentemente.' }}
                            </p>
                        </div>

                        <!-- Timezone Selector -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Zona Horaria de Operación</label>
                            <select v-model="company.timezone" class="w-full p-4 border border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-orange-500 outline-none text-sm font-medium">
                                <option v-for="(label, code) in timezones" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Weekly Schedule Grid -->
                    <div class="space-y-4">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Cronograma Semanal</span>
                        <div class="grid grid-cols-1 gap-3">
                            <div 
                                v-for="day in scheduleDays" 
                                :key="day.key"
                                class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-4 bg-gray-50/50 rounded-2xl border border-gray-100 hover:border-orange-200 hover:bg-white transition-all group overflow-hidden"
                            >
                                <span class="w-full sm:w-20 text-[11px] font-black text-gray-600 uppercase border-b sm:border-b-0 pb-2 sm:pb-0 mb-1 sm:mb-0">{{ day.name }}</span>
                                
                                <div class="flex-1 w-full flex flex-wrap items-center justify-between gap-4">
                                    <!-- Toggle Cerrado -->
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" v-model="company.schedule_config[day.key].closed" class="hidden peer">
                                        <div class="w-10 h-5 bg-gray-200 peer-checked:bg-red-500 rounded-full relative transition-all duration-300">
                                            <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full shadow-sm transition-all duration-300 peer-checked:translate-x-5"></div>
                                        </div>
                                        <span class="text-[10px] font-black tracking-widest uppercase transition-colors" :class="company.schedule_config[day.key].closed ? 'text-red-600' : 'text-green-600'">
                                            {{ company.schedule_config[day.key].closed ? 'Cerrado' : 'Abierto' }}
                                        </span>
                                    </label>

                                    <!-- Inputs de Tiempo -->
                                    <div v-if="!company.schedule_config[day.key].closed" class="flex items-center gap-2 bg-white p-1 rounded-xl shadow-sm border border-gray-100">
                                        <input v-model="company.schedule_config[day.key].open" type="time" class="p-1 px-2 border-none rounded-lg text-xs bg-transparent outline-none focus:ring-0 font-bold text-gray-700">
                                        <span class="text-[10px] text-gray-300 font-bold">→</span>
                                        <input v-model="company.schedule_config[day.key].close" type="time" class="p-1 px-2 border-none rounded-lg text-xs bg-transparent outline-none focus:ring-0 font-bold text-gray-700">
                                    </div>
                                    <div v-else class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic">Pausa Semanal</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna 3: Configuración de Impresión (POS) -->
            <div class="bg-white p-6 rounded-2xl shadow-sm space-y-6 md:col-span-2">
                <div class="flex items-center gap-2 text-xl font-bold text-gray-800 pb-4 border-b">
                    <Printer class="w-6 h-6 text-blue-500" /> Configuración de Impresión (POS)
                </div>
                <div class="flex flex-col md:flex-row gap-8 items-center bg-blue-50/50 p-6 rounded-2xl border border-blue-100">
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 mb-1">Ancho del Papel Térmico</h3>
                        <p class="text-xs text-gray-500">Selecciona el tamaño de papel que utiliza tu impresora de tickets.</p>
                    </div>
                    <div class="flex bg-white p-1 rounded-xl shadow-sm border border-blue-200">
                        <button 
                            @click="company.printer_width = '58'"
                            :class="company.printer_width === '58' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50'"
                            class="px-6 py-2.5 rounded-lg text-sm font-black transition-all"
                        >
                            58mm
                        </button>
                        <button 
                            @click="company.printer_width = '80'"
                            :class="company.printer_width === '80' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50'"
                            class="px-6 py-2.5 rounded-lg text-sm font-black transition-all"
                        >
                            80mm
                        </button>
                    </div>
                </div>
            </div>

            <!-- Columna 4: Timer Semáforo -->
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
    </div>
</template>
