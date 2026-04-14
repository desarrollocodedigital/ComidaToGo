<template>
  <div class="web-pos flex h-screen bg-slate-50 overflow-hidden font-sans">
    
    <!-- Sidebar de Navegación -->
    <div class="w-24 bg-white border-r border-gray-200 flex flex-col items-center py-6 shadow-sm z-10">
      <router-link to="/admin/dashboard" class="mb-8 p-3 rounded-xl bg-orange-100 text-orange-600 hover:bg-orange-200" title="Volver al Panel">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      </router-link>
      
      <div class="space-y-6 flex-1 w-full px-4">
        <!-- Modo Venta -->
        <button 
            @click="activeView = 'MENU'"
            :class="['w-full h-16 rounded-2xl flex flex-col items-center justify-center shadow-sm transition-colors', activeView === 'MENU' ? 'bg-slate-800 text-white shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border border-gray-200']">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
          <span class="text-xs font-bold">Menú</span>
        </button>
        <!-- Modo Pedidos Web -->
        <button 
            @click="activeView = 'WEB'"
            :class="['w-full h-16 rounded-2xl flex flex-col items-center justify-center shadow-sm transition-colors relative', activeView === 'WEB' ? 'bg-orange-500 text-white shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border border-gray-200']">
          <div v-if="pendingWebOrders.length > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-white text-xs font-black flex items-center justify-center animate-pulse">
              {{ pendingWebOrders.length }}
          </div>
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          <span class="text-xs font-bold text-center leading-tight mt-1">Pedidos<br>Web</span>
        </button>
      </div>

      <!-- Indicador Caja Abierta -->
      <div class="mt-auto px-2">
         <div v-if="activeShiftId" class="w-full text-center">
             <div class="w-3 h-3 bg-green-500 rounded-full mx-auto animate-pulse mb-1"></div>
             <span class="text-[10px] font-bold text-green-600 block leading-tight">Caja<br>Abierta</span>
         </div>
         <div v-else class="w-full text-center cursor-pointer" @click="$router.push('/caja')">
             <div class="w-3 h-3 bg-red-500 rounded-full mx-auto mb-1"></div>
             <span class="text-[10px] font-bold text-red-600 block leading-tight">Abrir<br>Caja</span>
         </div>
      </div>
    </div>

    <!-- MAIN AREA: MENÚ DE PRODUCTOS -->
    <div v-if="activeView === 'MENU'" class="flex-1 flex flex-col h-full bg-slate-50">
      <div class="h-20 bg-white border-b border-gray-200 px-8 flex items-center shadow-sm z-0">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight min-w-max mr-8">Punto de Venta</h2>
        <div class="relative flex-1 max-w-xl">
          <input v-model="searchQuery" type="text" placeholder="Buscar platillo por nombre..." class="w-full bg-slate-100 border-none rounded-full py-3 pl-12 pr-4 text-slate-700 focus:ring-2 focus:ring-orange-500 focus:outline-none">
          <svg class="w-5 h-5 text-gray-400 absolute left-4 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
      </div>

      <div v-if="loading" class="flex-1 flex items-center justify-center">
         <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
      </div>
      <div v-else-if="error" class="flex-1 flex items-center justify-center p-8 text-center text-red-500">{{ error }}</div>

      <div v-else class="flex-1 overflow-y-auto p-8">
        <div class="mb-6 flex gap-3 overflow-x-auto pb-2 no-scrollbar">
          <span @click="selectedCategory = null" :class="['px-5 py-2 rounded-full text-sm font-semibold cursor-pointer shadow-sm whitespace-nowrap', selectedCategory === null ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']">Todos</span>
          <span v-for="cat in categories" :key="cat.id" @click="selectedCategory = cat.id" :class="['px-5 py-2 rounded-full text-sm font-semibold cursor-pointer shadow-sm whitespace-nowrap', selectedCategory === cat.id ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']">{{ cat.name }}</span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div v-for="product in filteredProducts" :key="product.id" @click="addToCart(product)" class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-200 cursor-pointer transition-all transform hover:-translate-y-1 relative overflow-hidden">
            <div class="w-full h-32 bg-slate-100 rounded-xl mb-4 flex items-center justify-center text-4xl overflow-hidden">
                <img v-if="product.image_url" :src="product.image_url" class="w-full h-full object-cover">
                <span v-else>🍽️</span>
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight line-clamp-2">{{ product.name }}</h3>
            <p class="text-orange-600 font-bold mt-2 text-xl">${{ Number(product.price).toFixed(2) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN AREA: PEDIDOS WEB -->
    <div v-if="activeView === 'WEB'" class="flex-1 flex flex-col h-full bg-slate-50 overflow-y-auto">
        <!-- Pedidos Pendientes (Cajera acepta) -->
        <div class="p-6 border-b border-slate-200 bg-red-50/50">
            <h2 class="text-xl font-bold text-red-700 mb-4 flex items-center gap-2">
                🔔 Nuevos Pedidos Web
                <span class="bg-red-500 text-white px-2 py-0.5 rounded-full text-sm">{{ pendingWebOrders.length }}</span>
            </h2>
            <div class="grid gap-3">
                <div v-for="order in pendingWebOrders" :key="order.id" class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-red-500">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-black text-xl text-slate-800">#{{ order.id }} · {{ order.customer_name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ order.customer_phone || '' }} · 
                                <span v-if="order.order_type === 'DELIVERY'">🛵 Domicilio</span>
                                <span v-else>🥡 Recoger</span>
                            </p>
                            <p v-if="order.customer_address" class="text-xs text-gray-400 mt-1">📍 {{ order.customer_address }}</p>
                        </div>
                        <div :class="['px-3 py-1 rounded-full text-sm font-black', getTimerColor(order.created_at)]">
                            ⏱ {{ formatElapsed(order.created_at) }}
                        </div>
                    </div>
                    <div class="space-y-1 my-3 py-2 border-t border-b border-gray-100 text-sm">
                        <div v-for="item in order.items" :key="item.id">
                            <span class="font-black text-orange-600">{{ item.quantity }}x</span> {{ item.product_name }}
                            <span v-if="item.modifiers" class="text-xs text-gray-400 ml-2">({{ item.modifiers }})</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-black text-slate-800">${{ Number(order.total_amount).toFixed(2) }}</span>
                        <div class="flex gap-2">
                            <button @click="rejectOrder(order.id)" class="px-4 py-2 bg-red-100 text-red-700 font-bold rounded-lg hover:bg-red-200 text-sm">Rechazar</button>
                            <button @click="acceptOrder(order.id)" class="px-6 py-2 bg-green-500 text-white font-bold rounded-lg hover:bg-green-600 shadow-md text-sm">✅ Aceptar → Cocina</button>
                        </div>
                    </div>
                </div>
                <p v-if="pendingWebOrders.length === 0" class="text-center text-gray-400 py-6">No hay pedidos nuevos</p>
            </div>
        </div>

        <!-- Pedidos Listos para Cobrar -->
        <div class="p-6">
            <h2 class="text-xl font-bold text-green-700 mb-4 flex items-center gap-2">
                💵 Listos para Cobrar
                <span class="bg-green-500 text-white px-2 py-0.5 rounded-full text-sm">{{ readyWebOrders.length }}</span>
            </h2>
            <div class="grid gap-3">
                <div v-for="order in readyWebOrders" :key="order.id" class="bg-white p-5 rounded-2xl shadow-sm border border-green-200 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">#{{ order.id }} · {{ order.customer_name }}</h3>
                        <p class="text-green-600 font-bold">${{ Number(order.total_amount).toFixed(2) }}</p>
                        <p class="text-sm text-slate-400 capitalize">{{ order.order_type }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="chargeWebOrder(order, 'CASH')" class="bg-green-500 text-white font-bold py-3 px-5 rounded-xl hover:bg-green-600 shadow-md text-sm">💵 Efectivo</button>
                        <button @click="chargeWebOrder(order, 'CARD')" class="bg-blue-500 text-white font-bold py-3 px-5 rounded-xl hover:bg-blue-600 shadow-md text-sm">💳 Tarjeta</button>
                        <button @click="chargeWebOrder(order, 'TRANSFER')" class="bg-slate-600 text-white font-bold py-3 px-5 rounded-xl hover:bg-slate-700 shadow-md text-sm">📲 Transfer</button>
                    </div>
                </div>
                <p v-if="readyWebOrders.length === 0" class="text-center text-gray-400 py-6">No hay pedidos listos para cobrar</p>
            </div>
        </div>
    </div>


    <!-- TICKET PANEL LATERAL -->
    <div class="w-96 bg-white border-l border-gray-200 shadow-xl flex flex-col z-20">
      
      <div class="p-6 border-b border-slate-100 bg-slate-50/50">
        <div class="flex bg-slate-200 rounded-lg p-1 relative">
          <div class="absolute inset-y-1 w-1/2 bg-white rounded-md shadow-sm transition-transform duration-300 ease-out" :class="cartType === 'DINE_IN' ? 'translate-x-0' : 'translate-x-full'"></div>
          <button @click="cartType = 'DINE_IN'" class="flex-1 py-2 text-sm font-bold text-slate-800 relative z-10">Para Mesa</button>
          <button @click="cartType = 'TAKEAWAY'" class="flex-1 py-2 text-sm font-bold text-slate-800 relative z-10">Para Llevar</button>
        </div>
        
        <div v-if="cartType === 'DINE_IN'" class="mt-4">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Asignar Mesa</label>
            <select v-model="selectedTableId" class="w-full bg-white border border-slate-200 rounded-lg p-3 text-slate-700 font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none">
                <option :value="null">-- Seleccionar Mesa --</option>
                <option v-for="table in tables" :key="table.id" :value="table.id">
                    {{ table.name }} (Capacidad: {{ table.capacity }}) 
                    {{ table.status !== 'AVAILABLE' ? '🔴 Ocupada' : '🟢 Libre' }}
                </option>
            </select>
        </div>
         <div v-if="cartType === 'TAKEAWAY'" class="mt-4">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Nombre del Cliente</label>
            <input v-model="customerName" type="text" placeholder="Ej. Juan Pérez" class="w-full bg-white border border-slate-200 rounded-lg p-3 text-slate-700 font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none">
        </div>
      </div>

      <!-- Ítems del Ticket -->
      <div class="flex-1 overflow-y-auto p-0">
        <div v-if="cart.length === 0" class="p-8 text-center text-slate-400 font-medium flex flex-col items-center justify-center h-full">
            <svg class="w-16 h-16 mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            El ticket está vacío.<br>Agrega productos del menú.
        </div>
        
        <ul class="divide-y divide-slate-100">
            <li v-for="(item, index) in cart" :key="index" class="p-4 hover:bg-slate-50 group">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1 pr-4">
                        <h4 class="font-bold text-slate-800 text-sm">{{ item.quantity }}x {{ item.name }}</h4>
                    </div>
                    <span class="font-bold text-slate-800 whitespace-nowrap">${{ (item.price * item.quantity).toFixed(2) }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="updateQuantity(index, -1)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-200 font-bold">-</button>
                    <span class="font-bold text-sm min-w-[20px] text-center">{{ item.quantity }}</span>
                    <button @click="updateQuantity(index, 1)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-200 font-bold">+</button>
                    <button @click="cart.splice(index, 1)" class="ml-auto text-xs text-red-500 opacity-0 group-hover:opacity-100 transition-opacity font-bold hover:underline">Eliminar</button>
                </div>
            </li>
        </ul>
      </div>

      <!-- Totales y Pagar -->
      <div class="p-6 bg-slate-800 shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.3)] z-30">
        <div class="flex justify-between items-center mb-6">
           <span class="text-slate-300 font-medium text-lg">Total a cobrar</span>
           <span class="text-white font-black text-4xl">${{ cartTotal.toFixed(2) }}</span>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mb-3">
          <button @click="processPayment('CASH')" :disabled="!canCheckout" :class="['font-bold py-4 rounded-xl shadow-lg transition-transform flex flex-col items-center justify-center', canCheckout ? 'bg-green-500 hover:bg-green-600 text-white transform hover:-translate-y-1' : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
            <span class="text-xl mb-1">💵</span>
            Efectivo
          </button>
          <button @click="processPayment('CARD')" :disabled="!canCheckout" :class="['font-bold py-4 rounded-xl shadow-lg transition-transform flex flex-col items-center justify-center', canCheckout ? 'bg-blue-500 hover:bg-blue-600 text-white transform hover:-translate-y-1' : 'bg-slate-700 text-slate-500 cursor-not-allowed']">
            <span class="text-xl mb-1">💳</span>
            Tarjeta
          </button>
        </div>
        <button @click="processPayment('TRANSFER')" :disabled="!canCheckout" :class="['w-full font-bold py-3 rounded-xl transition-colors', canCheckout ? 'bg-slate-700 hover:bg-slate-600 text-white' : 'bg-slate-700 text-slate-500 cursor-not-allowed opacity-50']">
            📲 Transferencia
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'

const auth = useAuthStore()
const companyId = auth.user?.company_id || 1

// Estado de la Vista
const activeView = ref('MENU')
const loading = ref(true)
const error = ref(null)
const now = ref(Date.now())

// Datos del Servidor
const categories = ref([])
const products = ref([])
const tables = ref([])
const webOrders = ref([])
const activeShiftId = ref(null)

// Timer config
const timerGreen = ref(10)
const timerYellow = ref(20)

// Sound tracking
const lastPendingCount = ref(0)
const lastReadyCount = ref(0)

// Controles de Búsqueda y Filtros
const searchQuery = ref('')
const selectedCategory = ref(null)

// El Ticket (Cart) Local del POS
const cart = ref([])
const cartType = ref('TAKEAWAY')
const selectedTableId = ref(null)
const customerName = ref('')

// Polling and timers
let pollInterval = null
let timerInterval = null

// Alert sounds
const playSound = (type = 'new') => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.connect(gain)
        gain.connect(ctx.destination)
        gain.gain.value = 0.25
        
        if (type === 'new') {
            osc.frequency.value = 800
            osc.start()
            setTimeout(() => { osc.frequency.value = 1000 }, 150)
            setTimeout(() => { osc.frequency.value = 1200 }, 300)
            setTimeout(() => { osc.stop(); ctx.close() }, 500)
        } else if (type === 'ready') {
            osc.frequency.value = 1200
            osc.start()
            setTimeout(() => { osc.frequency.value = 1400 }, 100)
            setTimeout(() => { osc.stop(); ctx.close() }, 300)
        }
    } catch (e) { console.warn('Audio not available') }
}

// Timer helpers
const formatElapsed = (dateStr) => {
    if (!dateStr) return '00:00'
    const totalSec = Math.max(0, Math.floor((now.value - new Date(dateStr).getTime()) / 1000))
    const m = Math.floor(totalSec / 60)
    const s = totalSec % 60
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

const getTimerColor = (dateStr) => {
    if (!dateStr) return 'text-green-600 bg-green-100'
    const mins = (now.value - new Date(dateStr).getTime()) / 60000
    if (mins >= timerYellow.value) return 'text-red-600 bg-red-100'
    if (mins >= timerGreen.value) return 'text-yellow-600 bg-yellow-100'
    return 'text-green-600 bg-green-100'
}

// Computed
const filteredProducts = computed(() => {
    let result = products.value
    if (selectedCategory.value) result = result.filter(p => p.category_id === selectedCategory.value)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(p => p.name.toLowerCase().includes(query))
    }
    return result
})

const cartTotal = computed(() => cart.value.reduce((total, item) => total + (item.price * item.quantity), 0))

const canCheckout = computed(() => {
    if (cart.value.length === 0) return false
    if (cartType.value === 'DINE_IN' && !selectedTableId.value) return false
    if (cartType.value === 'TAKEAWAY' && !customerName.value.trim()) return false
    return true
})

const pendingWebOrders = computed(() => webOrders.value.filter(o => o.status === 'PENDING'))
const readyWebOrders = computed(() => webOrders.value.filter(o => o.status === 'READY'))

// --- METHODS ---
const loadInitialData = async () => {
    loading.value = true
    try {
        const resTenant = await axios.get(`/api.php/tenant/${companyId}`)
        if(resTenant.data.menu) {
            categories.value = resTenant.data.menu
            let allProds = []
            categories.value.forEach(cat => {
                if(cat.products) allProds.push(...cat.products)
            })
            products.value = allProds
        }
        // Load timer config
        if (resTenant.data.schedule_config) {
            const config = typeof resTenant.data.schedule_config === 'string' ? JSON.parse(resTenant.data.schedule_config) : resTenant.data.schedule_config
            if (config.timer_green) timerGreen.value = config.timer_green
            if (config.timer_yellow) timerYellow.value = config.timer_yellow
        }

        const resCaja = await axios.get(`/api.php/cash-register/status?company_id=${companyId}`)
        if (resCaja.data.has_active_shift) {
            activeShiftId.value = resCaja.data.shift.id
        }

        const resTables = await axios.get(`/api.php/tables?company_id=${companyId}`)
        tables.value = resTables.data

    } catch (err) {
        console.error(err)
        error.value = "Error al cargar datos del POS."
    } finally {
        loading.value = false
    }
}

const refreshOrders = async () => {
    try {
        const res = await axios.get(`/api.php/orders?company_id=${companyId}`)
        if (Array.isArray(res.data)) {
            // Check for new PENDING orders
            const newPending = res.data.filter(o => o.status === 'PENDING').length
            if (newPending > lastPendingCount.value && lastPendingCount.value >= 0) {
                playSound('new')
                // Auto-switch to WEB tab when new order arrives
                if (activeView.value === 'MENU') activeView.value = 'WEB'
            }
            lastPendingCount.value = newPending

            // Check for new READY orders
            const newReady = res.data.filter(o => o.status === 'READY').length
            if (newReady > lastReadyCount.value && lastReadyCount.value >= 0) {
                playSound('ready')
            }
            lastReadyCount.value = newReady

            webOrders.value = res.data
        }
    } catch (e) { console.error(e) }
}

const addToCart = (product) => {
    const existing = cart.value.find(i => i.id === product.id)
    if (existing) { existing.quantity++ }
    else { cart.value.push({ ...product, quantity: 1 }) }
}

const updateQuantity = (index, delta) => {
    const item = cart.value[index]
    item.quantity += delta
    if (item.quantity <= 0) cart.value.splice(index, 1)
}

// CAJERA acepta pedido → va a cocina
const acceptOrder = async (orderId) => {
    try {
        await axios.put(`/api.php/orders/${orderId}`, { status: 'ACCEPTED' })
        await refreshOrders()
    } catch (e) { alert("Error al aceptar pedido") }
}

const rejectOrder = async (orderId) => {
    if (!confirm('¿Rechazar este pedido?')) return
    try {
        await axios.put(`/api.php/orders/${orderId}`, { status: 'REJECTED' })
        await refreshOrders()
    } catch (e) { alert("Error al rechazar pedido") }
}

// Cobrar pedido web listo
const chargeWebOrder = async (order, method) => {
    if (method === 'CASH' && !activeShiftId.value) {
        alert("¡Abre la caja primero para cobrar en efectivo!")
        return
    }
    if (!confirm(`¿Cobrar pedido #${order.id} por $${Number(order.total_amount).toFixed(2)} con ${method}?`)) return
    
    try {
        await axios.put(`/api.php/orders/${order.id}`, {
            status: 'COMPLETED',
            payment_method: method,
            cash_register_shift_id: method === 'CASH' ? activeShiftId.value : null
        })
        alert(`✅ Pedido #${order.id} cobrado ($${Number(order.total_amount).toFixed(2)} - ${method})`)
        await refreshOrders()
    } catch (e) { alert("Error al cobrar pedido") }
}

// Procesar pago del ticket local del POS
const processPayment = async (method) => {
    if (!canCheckout.value) return

    if (method === 'CASH' && !activeShiftId.value) {
        alert("¡No puedes cobrar en Efectivo sin turno de caja abierto! Ve a Control de Caja.")
        return
    }

    const orderPayload = {
        company_id: companyId,
        customer_name: cartType.value === 'DINE_IN' ? `Mesa ${tables.value.find(t => t.id === selectedTableId.value)?.name}` : customerName.value,
        order_type: cartType.value,
        table_id: selectedTableId.value,
        payment_method: method,
        cash_register_shift_id: method === 'CASH' ? activeShiftId.value : null,
        total_amount: cartTotal.value,
        items: cart.value.map(i => ({
            product_id: i.id,
            quantity: i.quantity,
            price: i.price,
            modifiers: [] 
        }))
    }

    try {
        await axios.post('/api.php/orders', orderPayload)
        
        if (cartType.value === 'DINE_IN' && selectedTableId.value) {
             await axios.put(`/api.php/tables/${selectedTableId.value}/status`, { status: 'OCCUPIED' })
        }

        alert(`✅ Cobro de $${cartTotal.value.toFixed(2)} registrado (${method}).`)
        
        cart.value = []
        customerName.value = ''
        selectedTableId.value = null
        loadInitialData()

    } catch (err) {
        alert("Error al procesar pago: " + (err.response?.data?.message || err.message))
    }
}

onMounted(async () => {
    await loadInitialData()
    await refreshOrders()
    pollInterval = setInterval(refreshOrders, 6000)
    timerInterval = setInterval(() => { now.value = Date.now() }, 1000)
})

onUnmounted(() => {
    clearInterval(pollInterval)
    clearInterval(timerInterval)
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
