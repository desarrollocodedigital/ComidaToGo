<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { Clock, ChefHat, CheckCircle, MapPin } from 'lucide-vue-next'

const route = useRoute()
const order = ref(null)
const loading = ref(true)
let pollInterval = null

const fetchOrder = async () => {
    try {
        const { data } = await axios.get(`/api.php/orders/${route.params.id}`)
        order.value = data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchOrder()
    pollInterval = setInterval(fetchOrder, 15000) // Poll cada 15s para actualizar estado
})

onUnmounted(() => {
    clearInterval(pollInterval)
})

const getStatusColor = (status) => {
    switch(status) {
        case 'PENDING': return 'bg-yellow-100 text-yellow-800'
        case 'ACCEPTED': return 'bg-blue-100 text-blue-800'
        case 'READY': return 'bg-green-100 text-green-800'
        case 'REJECTED': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
    }
}

const getStatusText = (status) => {
    switch(status) {
        case 'PENDING': return 'Esperando confirmación...'
        case 'ACCEPTED': return 'En preparación'
        case 'READY': return '¡Listo para retirar!'
        case 'COMPLETED': return 'Entregado'
        case 'REJECTED': return 'Cancelado por el restaurante'
        default: return status
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-6 flex flex-col items-center">
        <div v-if="loading" class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mt-20"></div>
        
        <div v-else-if="!order" class="text-center mt-20">
            <h1 class="text-2xl font-bold text-gray-800">Pedido no encontrado</h1>
        </div>

        <div v-else class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden mt-10">
            <!-- Header Status -->
            <div class="p-8 text-center" :class="order.status === 'READY' ? 'bg-green-500 text-white' : 'bg-white border-b'">
                <div v-if="order.status === 'PENDING'" class="animate-pulse">
                    <Clock class="w-16 h-16 mx-auto mb-4 text-yellow-500" />
                    <h1 class="text-2xl font-bold text-gray-800">Recibimos tu pedido</h1>
                    <p class="text-gray-500">Esperando que el restaurante lo acepte</p>
                </div>

                <div v-else-if="order.status === 'ACCEPTED'">
                    <ChefHat class="w-16 h-16 mx-auto mb-4 text-blue-500" />
                    <h1 class="text-2xl font-bold text-gray-800">Cocinando...</h1>
                    <p class="text-gray-500">Tu comida se está preparando con amor</p>
                    
                    <div v-if="order.estimated_completion_time" class="mt-6 bg-blue-50 p-4 rounded-xl inline-block">
                        <p class="text-sm text-blue-800 font-bold uppercase tracking-wider mb-1">Hora estimada de entrega</p>
                        <p class="text-3xl font-black text-blue-900">
                            {{ new Date(order.estimated_completion_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                        </p>
                    </div>
                </div>

                <div v-else-if="order.status === 'READY'">
                    <CheckCircle class="w-16 h-16 mx-auto mb-4 text-white" />
                    <h1 class="text-3xl font-bold">¡Está Listo!</h1>
                    <p class="opacity-90">Ya puedes pasar por tu pedido</p>
                </div>
                 <div v-else>
                    <h1 class="text-2xl font-bold">{{ getStatusText(order.status) }}</h1>
                </div>
            </div>

            <!-- Details -->
            <div class="p-6 bg-gray-50">
                <h3 class="font-bold text-gray-900 mb-4">Detalles del Pedido #{{ order.id }}</h3>
                <div class="space-y-3">
                    <div v-for="item in order.items" :key="item.id" class="flex justify-between text-sm">
                        <span><span class="font-bold">{{ item.quantity }}x</span> {{ item.product_name }}</span>
                        <span class="font-medium">${{ item.unit_price }}</span>
                    </div>
                </div>
                <div class="border-t border-gray-200 mt-4 pt-4 flex justify-between font-bold text-lg">
                    <span>Total</span>
                    <span>${{ order.total_amount }}</span>
                </div>
            </div>
            
            <div class="p-6 text-center">
                 <router-link to="/" class="text-orange-600 font-bold hover:underline">Volver al Inicio</router-link>
            </div>
        </div>
    </div>
</template>
