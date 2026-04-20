<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { ChevronLeft, ShoppingBag, Clock, MapPin, ArrowRight, Star, X } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const orders = ref([])
const loading = ref(true)

// Rating Modal State
const showRatingModal = ref(false)
const selectedOrder = ref(null)
const ratingValue = ref(0)
const ratingComment = ref('')
const isSubmitting = ref(false)

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

const openRatingModal = (order) => {
    selectedOrder.value = order
    ratingValue.value = 0
    ratingComment.value = ''
    showRatingModal.value = true
}

const submitRating = async () => {
    if (ratingValue.value === 0) return
    
    isSubmitting.value = true
    try {
        await axios.post('/api.php/reviews', {
            order_id: selectedOrder.value.id,
            rating: ratingValue.value,
            comment: ratingComment.value
        })
        
        // Actualizar el estado local de la orden
        const order = orders.value.find(o => o.id === selectedOrder.value.id)
        if (order) order.has_review = true
        
        showRatingModal.value = false
    } catch (e) {
        console.error(e)
        alert(e.response?.data?.message || "Error al enviar la calificación")
    } finally {
        isSubmitting.value = false
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
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 overflow-hidden shrink-0 border border-slate-50 animate-pulse relative">
                                <img 
                                    v-if="order.company_logo" 
                                    :src="order.company_logo" 
                                    @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse'); $event.target.parentElement.classList.add('bg-white')"
                                    class="w-full h-full object-cover opacity-0 transition-opacity duration-700"
                                >
                                <div v-else class="w-full h-full flex items-center justify-center text-2xl">🏪</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-1">
                                    <h3 class="font-black text-lg text-slate-900 group-hover:text-orange-600 transition-colors truncate">{{ order.company_name }}</h3>
                                    <div :class="['px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border shrink-0 whitespace-nowrap self-start sm:self-center', getStatusColor(order.status)]">
                                        {{ getStatusText(order.status) }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500 font-bold">
                                    <span class="flex items-center gap-1"><Clock class="w-3.5 h-3.5" /> {{ new Date(order.created_at).toLocaleDateString() }}</span>
                                    <span>#{{ order.id }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Motivo de rechazo -->
                        <div v-if="order.status === 'REJECTED' && order.rejection_reason" class="mb-4 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                <X class="w-4 h-4 text-red-600" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-red-400 uppercase tracking-widest leading-none mb-1">Motivo del rechazo</p>
                                <p class="text-sm font-bold text-red-700 leading-tight">{{ order.rejection_reason }}</p>
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

                        <div class="flex gap-2">
                             <button 
                                @click="router.push(`/pedido/${order.id}`)"
                                :class="['py-3 font-bold rounded-xl transition-all flex items-center justify-center gap-2', 
                                         order.status === 'COMPLETED' ? 'bg-slate-100 text-slate-800 w-1/2' : 'bg-slate-100 text-slate-800 w-full']"
                            >
                                Detalle <ArrowRight class="w-4 h-4 ml-1" />
                            </button>

                            <button 
                                v-if="order.status === 'COMPLETED' && !order.has_review"
                                @click="openRatingModal(order)"
                                class="w-1/2 py-3 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition-all flex items-center justify-center gap-2"
                            >
                                <Star class="w-4 h-4 fill-white" /> Calificar
                            </button>

                            <div 
                                v-else-if="order.status === 'COMPLETED' && order.has_review"
                                class="w-1/2 py-3 bg-green-50 text-green-600 font-bold rounded-xl border border-green-100 flex items-center justify-center gap-2 text-xs"
                            >
                                <Star class="w-4 h-4 fill-green-600" /> Calificado
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- Rating Modal -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showRatingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="showRatingModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
                
                <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <Star class="w-10 h-10 text-orange-500 fill-orange-500" />
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 mb-2">¿Qué te pareció el pedido de {{ selectedOrder.company_name }}?</h2>
                        <p class="text-slate-500 text-sm mb-8 px-4">Tu opinión es muy importante para nosotros y para el negocio.</p>
                        
                        <!-- Stars -->
                        <div class="flex justify-center gap-2 mb-8">
                            <button 
                                v-for="star in 5" 
                                :key="star"
                                @click="ratingValue = star"
                                class="p-1 transition-transform active:scale-90"
                            >
                                <Star 
                                    :class="['w-10 h-10 transition-colors', 
                                            star <= ratingValue ? 'text-yellow-400 fill-yellow-400' : 'text-slate-200']"
                                />
                            </button>
                        </div>
                        
                        <!-- Comment -->
                        <textarea 
                            v-model="ratingComment"
                            placeholder="Cuéntanos más (opcional)..."
                            class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 min-h-[100px] mb-6"
                        ></textarea>
                        
                        <div class="flex gap-3">
                            <button 
                                @click="showRatingModal = false"
                                class="flex-1 py-4 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all"
                            >
                                Cancelar
                            </button>
                            <button 
                                @click="submitRating"
                                :disabled="ratingValue === 0 || isSubmitting"
                                class="flex-[2] py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-lg shadow-orange-100 hover:bg-orange-600 disabled:opacity-50 disabled:shadow-none transition-all flex items-center justify-center gap-2"
                            >
                                <span v-if="isSubmitting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span v-else>Enviar Calificación</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
