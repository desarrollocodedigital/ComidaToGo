<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { ChevronLeft, ShoppingBag, Clock, MapPin, ArrowRight } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const orders = ref([])
const loading = ref(true)

const fetchOrders = async () => {
    if (!auth.user.phone) {
        loading.value = false
        return
    }
    try {
        const { data } = await axios.get(`/api.php/orders/customer?phone=${auth.user.phone}`)
        orders.value = data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    if (!auth.isAuthenticated) {
        router.push('/')
        return
    }
    fetchOrders()
})

const getStatusColor = (status) => {
    switch(status) {
        case 'PENDING': return 'bg-yellow-100 text-yellow-700 border-yellow-200'
        case 'ACCEPTED': return 'bg-blue-100 text-blue-700 border-blue-200'
        case 'READY': return 'bg-green-100 text-green-700 border-green-200'
        case 'COMPLETED': return 'bg-slate-100 text-slate-700 border-slate-200'
        case 'REJECTED': 
        case 'CANCELLED': return 'bg-red-100 text-red-700 border-red-200'
        default: return 'bg-gray-100 text-gray-700 border-gray-200'
    }
}

const getStatusText = (status) => {
    switch(status) {
        case 'PENDING': return 'Pendiente'
        case 'ACCEPTED': return 'En preparación'
        case 'READY': return 'Listo para entrega'
        case 'COMPLETED': return 'Entregado'
        case 'REJECTED': return 'Rechazado'
        case 'CANCELLED': return 'Cancelado'
        default: return status
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 pb-10">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-4 py-4 sticky top-0 z-10 shadow-sm">
            <div class="max-w-2xl mx-auto flex items-center gap-4">
                <button @click="router.push('/')" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                    <ChevronLeft class="w-6 h-6 text-slate-700" />
                </button>
                <h1 class="text-xl font-black text-slate-900">Mis Pedidos</h1>
            </div>
        </header>

        <main class="max-w-2xl mx-auto p-4 space-y-6 mt-4">
            
            <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mb-4"></div>
                <p class="text-slate-500 font-medium">Cargando tu historial...</p>
            </div>

            <div v-else-if="orders.length === 0" class="text-center py-20 bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6">
                    <ShoppingBag class="w-10 h-10" />
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Aún no tienes pedidos</h2>
                <p class="text-slate-500 mb-8">Parece que no has pedido nada todavía. ¡Explora los mejores lugares de tu ciudad!</p>
                <button @click="router.push('/')" class="bg-orange-500 text-white px-8 py-4 rounded-2xl font-bold shadow-lg shadow-orange-100 hover:bg-orange-600 transition-all flex items-center gap-2 mx-auto">
                    Ir a buscar comida <ArrowRight class="w-5 h-5" />
                </button>
            </div>

            <div v-else class="space-y-4">
                <div 
                    v-for="order in orders" 
                    :key="order.id"
                    class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-shadow group"
                >
                    <div class="p-5">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 overflow-hidden shrink-0 border border-slate-50">
                                <img v-if="order.company_logo" :src="order.company_logo" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center text-2xl">🏪</div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-black text-lg text-slate-900 group-hover:text-orange-600 transition-colors">{{ order.company_name }}</h3>
                                <div class="flex items-center gap-3 text-xs text-slate-500 font-bold mt-1">
                                    <span class="flex items-center gap-1"><Clock class="w-3.5 h-3.5" /> {{ new Date(order.created_at).toLocaleDateString() }}</span>
                                    <span>#{{ order.id }}</span>
                                </div>
                            </div>
                            <div :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border', getStatusColor(order.status)]">
                                {{ getStatusText(order.status) }}
                            </div>
                        </div>

                        <div class="bg-slate-50 rounded-2xl p-4 flex items-center justify-between mb-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Total del pedido</p>
                                <p class="text-xl font-black text-slate-800">${{ Number(order.total_amount).toFixed(2) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Tipo</p>
                                <p class="text-xs font-bold text-slate-600">{{ order.order_type === 'PICKUP' ? 'Para Recoger' : 'A Domicilio' }}</p>
                            </div>
                        </div>

                        <button 
                            @click="router.push(`/pedido/${order.id}`)"
                            class="w-full py-3 bg-slate-100 text-slate-800 font-bold rounded-xl hover:bg-orange-500 hover:text-white transition-all flex items-center justify-center gap-2"
                        >
                            Rastrear Pedido <ArrowRight class="w-4 h-4 ml-1" />
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>
</template>
