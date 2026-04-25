<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'
import { 
  Clock, CheckCircle, AlertCircle, RefreshCw, Volume2, 
  Truck, ShoppingBag, Utensils, MapPin, Phone 
} from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const orders = ref([])
const loading = ref(true)
const auth = useAuthStore()
let pollingInterval = null
let timerInterval = null
const now = ref(Date.now())
const soundEnabled = ref(true)
const isFirstLoad = ref(true)
const seenOrderIds = ref(new Set())

// Timer thresholds (in minutes) - will load from company config
const timerGreen = ref(10)
const timerYellow = ref(20)

// Alert sound using Web Audio API
const playSound = (type = 'new') => {
    if (!soundEnabled.value) return
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.connect(gain)
        gain.connect(ctx.destination)
        
        if (type === 'new') {
            const now = ctx.currentTime
            // Multi-oscillador para un sonido más "premium"
            const osc2 = ctx.createOscillator()
            osc2.connect(gain)
            
            osc.type = 'sine'
            osc2.type = 'square'
            osc2.frequency.value = 440
            
            osc.frequency.setValueAtTime(880, now)
            osc.frequency.exponentialRampToValueAtTime(1320, now + 0.1)
            
            gain.gain.setValueAtTime(0, now)
            gain.gain.linearRampToValueAtTime(0.3, now + 0.05)
            gain.gain.linearRampToValueAtTime(0, now + 0.4)
            
            osc.start(now)
            osc2.start(now)
            osc.stop(now + 0.4)
            osc2.stop(now + 0.4)
            
            setTimeout(() => ctx.close(), 500)
        }
    } catch (e) { console.warn('Audio not available') }
}

const fetchOrders = async () => {
    try {
        const companyId = auth.user?.company_id || 1
        const { data } = await axios.get(`/api.php/orders?company_id=${companyId}`)
        
        // Determinar si hay nuevos pedidos para sonar
        const activeOrders = data.filter(o => o.status === 'ACCEPTED' || o.status === 'PREPARING')
        let hasNew = false
        
        activeOrders.forEach(order => {
            if (!seenOrderIds.value.has(order.id)) {
                seenOrderIds.value.add(order.id)
                hasNew = true
            }
        })

        if (hasNew && !isFirstLoad.value) {
            playSound('new')
        }
        
        isFirstLoad.value = false
        orders.value = data
    } catch (e) {
        console.error("Error fetching orders", e)
    } finally {
        loading.value = false
    }
}

const loadConfig = async () => {
    try {
        const companyId = auth.user?.company_id || 1
        const { data } = await axios.get(`/api.php/tenant/${companyId}`)
        if (data.schedule_config) {
            const config = typeof data.schedule_config === 'string' ? JSON.parse(data.schedule_config) : data.schedule_config
            if (config.timer_green) timerGreen.value = config.timer_green
            if (config.timer_yellow) timerYellow.value = config.timer_yellow
        }
    } catch(e) { /* use defaults */ }
}

const updateStatus = async (orderId, newStatus) => {
    try {
        await axios.put(`/api.php/orders/${orderId}`, { status: newStatus })
        await fetchOrders()
    } catch (e) {
        alert("Error actualizando estado")
    }
}

const getElapsedMinutes = (dateStr) => {
    if (!dateStr) return 0
    return (now.value - new Date(dateStr).getTime()) / 60000
}

const formatElapsed = (dateStr) => {
    if (!dateStr) return '00:00'
    const totalSec = Math.max(0, Math.floor((now.value - new Date(dateStr).getTime()) / 1000))
    const h = Math.floor(totalSec / 3600)
    const m = Math.floor((totalSec % 3600) / 60)
    const s = totalSec % 60
    
    if (h > 0) return `${h}h ${String(m).padStart(2, '0')}m`
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

const getTimerColor = (dateStr) => {
    const mins = getElapsedMinutes(dateStr)
    if (mins >= timerYellow.value) return 'text-red-400 bg-red-400/10'
    if (mins >= timerGreen.value) return 'text-yellow-400 bg-yellow-400/10'
    return 'text-green-400 bg-green-400/10'
}

onMounted(async () => {
    await loadConfig()
    await fetchOrders()
    pollingInterval = setInterval(fetchOrders, 8000)
    timerInterval = setInterval(() => { now.value = Date.now() }, 1000)
})

onUnmounted(() => {
    clearInterval(pollingInterval)
    clearInterval(timerInterval)
})

const parseItemDetails = (item) => {
    let modifiersText = item.modifiers || ''
    let instructions = item.special_instructions || ''

    // Caso 1: Los datos vienen como JSON string
    if (typeof item.modifiers === 'string' && item.modifiers.trim().startsWith('{')) {
        try {
            const parsed = JSON.parse(item.modifiers)
            if (parsed.options && Array.isArray(parsed.options)) {
                modifiersText = parsed.options.join(', ')
            }
            if (parsed.instructions) {
                instructions = parsed.instructions
            }
        } catch (e) { /* fallback */ }
    } 
    // Caso 2: Los datos vienen ya formateados con un emoji separador (📝)
    else if (typeof item.modifiers === 'string' && item.modifiers.includes('📝')) {
        const parts = item.modifiers.split('📝')
        modifiersText = parts[0].trim().replace(/,$/, '') // Quitar coma final si existe
        instructions = parts[1].trim()
    }

    return {
        modifiers: modifiersText,
        instructions: instructions
    }
}

const inKitchenOrders = computed(() => {
    return [...orders.value]
        .filter(o => o.status === 'ACCEPTED' || o.status === 'PREPARING')
        .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const readyOrders = computed(() => {
    return [...orders.value]
        .filter(o => o.status === 'READY')
        .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

const showFullOrder = ref({}) // { orderId: boolean }

const isItemNew = (item) => {
    return parseInt(item.is_addition) === 1
}

const hasNewItems = (order) => {
    return order.items && order.items.some(item => isItemNew(item))
}

const getDisplayedItems = (order) => {
    if (showFullOrder.value[order.id]) return order.items
    if (!hasNewItems(order)) return order.items
    return order.items.filter(item => isItemNew(item))
}

const toggleFullOrder = (orderId) => {
    showFullOrder.value[orderId] = !showFullOrder.value[orderId]
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 p-6 font-sans">
        <header class="flex justify-between items-center mb-8 bg-slate-900/50 p-4 rounded-3xl border border-slate-800 backdrop-blur-md">
            <div class="flex items-center gap-6">
                <div class="w-12 h-12 bg-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <Utensils class="text-white w-7 h-7" />
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">KITCHEN DASHBOARD</h1>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest mt-0.5">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Terminal de Cocina Activa
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="soundEnabled = !soundEnabled" 
                        :class="['p-3 rounded-2xl transition-all duration-300 border flex items-center gap-2 font-black text-xs uppercase tracking-widest', 
                                 soundEnabled ? 'bg-green-500/10 border-green-500/50 text-green-400' : 'bg-slate-800 border-slate-700 text-slate-500']">
                    <Volume2 class="w-5 h-5" /> 
                    {{ soundEnabled ? 'Sonido ON' : 'Silencio' }}
                </button>
                <button @click="fetchOrders" class="p-3 bg-slate-800 border border-slate-700 rounded-2xl text-slate-400 hover:text-white transition-colors">
                    <RefreshCw class="w-5 h-5" />
                </button>
                <router-link to="/admin/dashboard" class="bg-white text-slate-900 px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-100 transition-colors shadow-xl">
                    Panel Admin
                </router-link>
            </div>
        </header>

        <div v-if="loading" class="flex flex-col items-center justify-center py-40 gap-4">
            <div class="w-12 h-12 border-4 border-orange-500/20 border-t-orange-500 rounded-full animate-spin"></div>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">Cargando pedidos...</p>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-[calc(100vh-160px)]">
            
            <!-- Columna Principal: En Preparación -->
            <div class="lg:col-span-8 bg-slate-900/40 rounded-[2rem] border border-slate-800/50 flex flex-col h-full overflow-hidden shadow-2xl backdrop-blur-sm">
                <div class="px-8 py-6 bg-slate-900 border-b border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-8 bg-orange-500 rounded-full"></div>
                        <h2 class="font-black text-white text-xl uppercase tracking-tight">En Preparación</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 text-xs font-black uppercase tracking-widest">Órdenes Activas</span>
                        <span class="bg-orange-500 text-white px-4 py-1.5 rounded-full text-sm font-black shadow-lg shadow-orange-500/20">{{ inKitchenOrders.length }}</span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                    <div class="flex flex-col gap-4">
                        <div v-for="order in inKitchenOrders" :key="order.id" 
                             :class="[
                                'bg-slate-900 rounded-[2rem] border-l-[12px] shadow-2xl border border-slate-800/50 transition-all hover:border-slate-700 flex flex-col xl:flex-row items-stretch overflow-hidden group',
                                order.order_type === 'DELIVERY' ? 'border-l-purple-500' : 
                                order.order_type === 'PICKUP' ? 'border-l-blue-500' : 'border-l-green-500'
                             ]">
                             
                            <!-- Sección Izquierda: Info del Pedido -->
                            <div class="w-full xl:w-64 p-6 bg-slate-900/80 border-b xl:border-b-0 xl:border-r border-slate-800 flex flex-col justify-between shrink-0">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="font-black text-4xl text-white tracking-tighter">#{{ order.id }}</div>
                                        <div :class="['px-3 py-1 rounded-xl text-xs font-black flex items-center gap-1 shadow-sm border border-white/5', getTimerColor(order.created_at)]">
                                            ⏱ {{ formatElapsed(order.created_at) }}
                                        </div>
                                    </div>
                                    <h3 class="font-black text-xl text-slate-300 leading-tight">{{ order.customer_name }}</h3>
                                </div>
                                <div class="mt-4">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/50 text-slate-400 border border-slate-700">
                                        <component :is="order.order_type === 'DELIVERY' ? Truck : (order.order_type === 'DINE_IN' ? Utensils : ShoppingBag)" class="w-4 h-4" />
                                        <span class="text-xs font-black uppercase tracking-widest">
                                            {{ order.order_type === 'DELIVERY' ? 'Envío' : (order.order_type === 'DINE_IN' ? 'Mesa ' + (order.table_number || order.table_name || '?') : 'En Local') }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="hasNewItems(order)" class="mt-4">
                                    <button @click="toggleFullOrder(order.id)" 
                                            class="w-full p-2 bg-orange-500/20 border border-orange-500/50 rounded-xl hover:bg-orange-500/30 transition-colors">
                                        <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest text-center">
                                            {{ showFullOrder[order.id] ? 'Ver solo nuevos' : '¡Productos Añadidos!' }}
                                        </p>
                                        <p v-if="!showFullOrder[order.id]" class="text-[8px] font-bold text-orange-400/70 uppercase text-center mt-0.5">Click para ver pedido completo</p>
                                    </button>
                                </div>
                            </div>

                            <!-- Sección Central: Productos (Lectura Rápida) -->
                            <div class="flex-1 p-6 bg-slate-900">
                                <ul class="flex flex-col gap-3">
                                    <li v-for="item in getDisplayedItems(order)" :key="item.id" 
                                        :class="[
                                            'flex items-start gap-4 p-4 bg-slate-950/50 rounded-2xl border border-slate-800/50 border-l-[4px] relative overflow-hidden',
                                            isItemNew(item) ? 'border-l-orange-500 bg-orange-500/5 ring-1 ring-orange-500/20' : 'border-l-slate-700'
                                        ]">
                                        
                                        <div v-if="isItemNew(item)" class="absolute top-0 right-0 bg-orange-500 text-white text-[8px] font-black px-2 py-1 rounded-bl-lg uppercase tracking-widest shadow-lg z-10">
                                            NUEVO
                                        </div>

                                        <div :class="['min-w-[3.5rem] text-center px-3 py-1.5 rounded-xl font-black text-2xl shadow-lg border transition-colors', isItemNew(item) ? 'bg-orange-500 text-white border-orange-400' : 'bg-slate-800 text-slate-300 border-slate-700']">
                                            {{ item.quantity }}x
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <span class="font-black text-white text-2xl tracking-tight leading-none">{{ item.product_name }}</span>
                                            
                                            <!-- Ingredientes Extras (desde JSON o base) -->
                                            <div v-if="parseItemDetails(item).modifiers" class="text-sm text-slate-400 font-bold mt-2 flex items-start gap-2 bg-slate-900/50 p-2.5 rounded-lg border border-slate-800/50">
                                                <span class="text-orange-500 mt-0.5">↳</span> 
                                                <div>
                                                    <span class="text-[10px] text-slate-500 uppercase block mb-0.5">Ingredientes extras:</span>
                                                    {{ parseItemDetails(item).modifiers }}
                                                </div>
                                            </div>

                                            <!-- Detalles / Instrucciones Especiales -->
                                            <div v-if="parseItemDetails(item).instructions" class="text-sm text-yellow-400 font-bold mt-2 flex items-start gap-2 bg-orange-500/10 p-2.5 rounded-lg border border-orange-500/20">
                                                <span class="text-orange-500 mt-0.5 text-lg">📝</span> 
                                                <div class="italic">
                                                    <span class="text-[10px] text-orange-400 uppercase block mb-0.5 not-italic">Detalles (Instrucciones):</span>
                                                    {{ parseItemDetails(item).instructions }}
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <!-- Sección Derecha: Acción -->
                            <div v-if="auth.user?.role !== 'WAITER'" class="w-full xl:w-48 p-6 bg-slate-900/80 border-t xl:border-t-0 xl:border-l border-slate-800 flex items-center justify-center shrink-0">
                                <button @click="updateStatus(order.id, 'READY')" class="w-full h-full min-h-[5rem] xl:min-h-[10rem] bg-green-500 text-white p-4 rounded-2xl font-black text-xl hover:bg-green-600 shadow-xl shadow-green-500/10 transition-transform active:scale-95 flex flex-col items-center justify-center gap-2">
                                    <CheckCircle class="w-10 h-10" />
                                    <span>LISTO</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="inKitchenOrders.length === 0" class="flex flex-col items-center justify-center py-32 text-slate-600">
                        <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center mb-4">
                            <Clock class="w-10 h-10 opacity-20" />
                        </div>
                        <p class="text-lg font-black uppercase tracking-widest opacity-30">Cocina Despejada</p>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral: Listos -->
            <div class="lg:col-span-4 bg-slate-900/40 rounded-[2rem] border border-slate-800/50 flex flex-col h-full overflow-hidden shadow-2xl backdrop-blur-sm">
                <div class="px-8 py-6 bg-slate-900 border-b border-slate-800 flex justify-between items-center text-green-400">
                    <div class="flex items-center gap-3">
                        <CheckCircle class="w-6 h-6" />
                        <h2 class="font-black text-xl uppercase tracking-tight">Listos</h2>
                    </div>
                    <span class="bg-green-500 text-white px-4 py-1.5 rounded-full text-sm font-black shadow-lg shadow-green-500/20">{{ readyOrders.length }}</span>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                    <div v-for="order in readyOrders" :key="order.id" 
                         class="bg-slate-900/60 p-5 rounded-3xl border border-green-500/20 flex items-center justify-between group hover:border-green-500/40 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center font-black text-green-400 border border-green-500/20">
                                #{{ order.id }}
                            </div>
                            <div>
                                <h3 class="font-black text-white text-lg leading-none tracking-tight">{{ order.customer_name }}</h3>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Esperando entrega</p>
                            </div>
                        </div>
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse shadow-lg shadow-green-500/50"></div>
                    </div>

                    <div v-if="readyOrders.length === 0" class="text-center py-20 text-slate-700">
                        <p class="font-black uppercase tracking-widest text-sm opacity-20">Ningún pedido listo</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1e293b;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #334155;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

