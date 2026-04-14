<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'
import { Clock, CheckCircle, AlertCircle, RefreshCw, Volume2 } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const orders = ref([])
const loading = ref(true)
const auth = useAuthStore()
let pollingInterval = null
let timerInterval = null
const now = ref(Date.now())
const lastKnownCount = ref(0)
const soundEnabled = ref(true)

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
            // Three ascending beeps for new order
            osc.frequency.value = 880
            gain.gain.value = 0.3
            osc.start()
            setTimeout(() => { osc.frequency.value = 1100 }, 200)
            setTimeout(() => { osc.frequency.value = 1320 }, 400)
            setTimeout(() => { osc.stop(); ctx.close() }, 600)
        }
    } catch (e) { console.warn('Audio not available') }
}

const fetchOrders = async () => {
    try {
        const companyId = auth.user?.company_id || 1
        const { data } = await axios.get(`/api.php/orders?company_id=${companyId}`)
        
        // Check for new ACCEPTED orders (new to kitchen)
        const newAcceptedCount = data.filter(o => o.status === 'ACCEPTED').length
        if (newAcceptedCount > lastKnownCount.value && lastKnownCount.value > 0) {
            playSound('new')
        }
        lastKnownCount.value = newAcceptedCount
        
        orders.value = data
    } catch (e) {
        console.error("Error fetching orders", e)
    } finally {
        loading.value = false
    }
}

// Load timer config from company settings
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

// Timer helpers
const getElapsedMinutes = (dateStr) => {
    if (!dateStr) return 0
    return (now.value - new Date(dateStr).getTime()) / 60000
}

const formatElapsed = (dateStr) => {
    if (!dateStr) return '00:00'
    const totalSec = Math.max(0, Math.floor((now.value - new Date(dateStr).getTime()) / 1000))
    const m = Math.floor(totalSec / 60)
    const s = totalSec % 60
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

const getTimerColor = (dateStr) => {
    const mins = getElapsedMinutes(dateStr)
    if (mins >= timerYellow.value) return 'text-red-600 bg-red-100'
    if (mins >= timerGreen.value) return 'text-yellow-600 bg-yellow-100'
    return 'text-green-600 bg-green-100'
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

// Cocina solo ve ACCEPTED (en preparación) y READY (listos)
const inKitchenOrders = computed(() => orders.value.filter(o => o.status === 'ACCEPTED'))
const readyOrders = computed(() => orders.value.filter(o => o.status === 'READY'))
</script>

<template>
    <div class="min-h-screen bg-gray-900 p-6">
        <header class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <h1 class="text-3xl font-bold text-white">🍳 Pantalla de Cocina</h1>
                <button @click="soundEnabled = !soundEnabled" :class="['p-2 rounded-lg', soundEnabled ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-400']" title="Sonido">
                    <Volume2 class="w-5 h-5" />
                </button>
            </div>
            <div class="flex gap-2">
                <router-link to="/admin/dashboard" class="bg-gray-700 text-gray-300 px-4 py-2 rounded-lg font-bold hover:bg-gray-600 text-sm">
                    Panel Admin
                </router-link>
                <button @click="fetchOrders" class="flex items-center gap-2 bg-gray-700 px-4 py-2 rounded-lg text-gray-300 hover:text-white">
                    <RefreshCw class="w-4 h-4" />
                </button>
            </div>
        </header>

        <div v-if="loading" class="text-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6 h-[calc(100vh-120px)]">
            
            <!-- Columna: En Preparación -->
            <div class="bg-gray-800 rounded-2xl flex flex-col h-full overflow-hidden">
                <div class="p-4 bg-yellow-500/20 border-b border-yellow-500/30 flex justify-between items-center">
                    <h2 class="font-bold text-yellow-400 flex items-center gap-2 text-lg">
                        <Clock class="w-5 h-5" /> En Preparación
                    </h2>
                    <span class="bg-yellow-500 text-black px-3 py-1 rounded-full text-sm font-black">{{ inKitchenOrders.length }}</span>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div v-for="order in inKitchenOrders" :key="order.id" class="bg-gray-700/60 p-5 rounded-xl border-l-4 border-yellow-500">
                        <div class="flex justify-between items-start mb-3">
                            <span class="font-black text-white text-2xl">#{{ order.id }}</span>
                            <div :class="['px-3 py-1 rounded-full text-sm font-black', getTimerColor(order.created_at)]">
                                ⏱ {{ formatElapsed(order.created_at) }}
                            </div>
                        </div>
                        <div class="mb-1 text-gray-400 text-sm">
                            {{ order.customer_name }} · 
                            <span v-if="order.order_type === 'DELIVERY'">🛵 Domicilio</span>
                            <span v-else-if="order.order_type === 'PICKUP'">🥡 Recoger</span>
                            <span v-else-if="order.order_type === 'DINE_IN'">🪑 Mesa</span>
                            <span v-else>🥡 Para llevar</span>
                        </div>
                        
                        <div class="space-y-2 my-3 border-t border-b border-gray-600 py-3">
                            <div v-for="item in order.items" :key="item.id" class="text-white">
                                <span class="font-black text-lg text-orange-400">{{ item.quantity }}x</span> 
                                <span class="font-bold">{{ item.product_name }}</span>
                                <div v-if="item.modifiers" class="text-xs text-gray-400 pl-6 mt-0.5">
                                    {{ item.modifiers }}
                                </div>
                            </div>
                        </div>

                        <button @click="updateStatus(order.id, 'READY')" class="w-full bg-green-500 text-white py-3 rounded-xl font-black text-lg hover:bg-green-600 shadow-lg transition-all hover:scale-[1.02]">
                            ✅ LISTO
                        </button>
                    </div>
                    <div v-if="inKitchenOrders.length === 0" class="text-center py-12 text-gray-500">
                        <p class="text-lg">Sin pedidos en cocina</p>
                    </div>
                </div>
            </div>

            <!-- Columna: Listos para Entregar -->
            <div class="bg-gray-800 rounded-2xl flex flex-col h-full overflow-hidden">
                <div class="p-4 bg-green-500/20 border-b border-green-500/30 flex justify-between items-center">
                    <h2 class="font-bold text-green-400 flex items-center gap-2 text-lg">
                        <CheckCircle class="w-5 h-5" /> Listos para Entregar
                    </h2>
                    <span class="bg-green-500 text-black px-3 py-1 rounded-full text-sm font-black">{{ readyOrders.length }}</span>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div v-for="order in readyOrders" :key="order.id" class="bg-gray-700/40 p-4 rounded-xl border border-green-500/30 flex items-center justify-between">
                        <div>
                            <span class="font-black text-white text-xl">#{{ order.id }}</span>
                            <p class="text-gray-400 text-sm">{{ order.customer_name }}</p>
                        </div>
                        <span class="bg-green-500/20 text-green-400 px-4 py-2 rounded-full text-sm font-bold animate-pulse">
                            🔔 Listo
                        </span>
                    </div>
                    <div v-if="readyOrders.length === 0" class="text-center py-12 text-gray-500">
                        <p class="text-lg">Nada listo aún</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
@keyframes pulse-border {
  0% { border-color: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
  70% { border-color: #f87171; box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
  100% { border-color: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}
.animate-pulse-border {
  animation: pulse-border 2s infinite;
}
</style>
