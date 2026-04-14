<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useCustomerStore } from '../stores/customer'
import axios from 'axios'
import { ArrowLeft, Clock, MapPin, ShoppingBag, Trash2 } from 'lucide-vue-next'

const router = useRouter()
const cartStore = useCartStore()
const customerStore = useCustomerStore()

const loading = ref(false)
const orderType = ref('PICKUP') // PICKUP | DELIVERY

// Cargar datos locales del comensal
const name = ref(customerStore.profile.name || '')
const phone = ref(customerStore.profile.phone || '')
const address = ref('') // La dirección que se enviará en el payload
const newAddress = ref('') // Valor temporal del textArea
const useNewAddress = ref(false)

onMounted(() => {
    if (customerStore.profile.addresses.length > 0) {
        address.value = customerStore.profile.addresses[0] // Set default
        useNewAddress.value = false
    } else {
        useNewAddress.value = true
    }
})

const submitOrder = async () => {
    if (!name.value || !phone.value) {
        alert("Por favor completa tus datos de contacto")
        return
    }

    // Auto-guardar en el perfil local:
    customerStore.saveProfile(name.value, phone.value)
    if (orderType.value === 'DELIVERY' && useNewAddress.value && newAddress.value.trim() !== '') {
        customerStore.addAddress(newAddress.value)
        address.value = newAddress.value // Update actual payload var
    }

    loading.value = true
    try {
        const payload = {
            company_id: cartStore.companyId,
            customer_name: name.value,
            customer_phone: phone.value,
            customer_address: orderType.value === 'DELIVERY' ? address.value : '',
            order_type: orderType.value,
            items: cartStore.items.map(item => ({
                product_id: item.productId,
                quantity: item.quantity,
                modifiers: item.modifiers.map(m => m.id),
                special_instructions: item.specialInstructions || ''
            }))
        }

        const { data } = await axios.post('/api.php/orders', payload)
        
        // Success
        cartStore.clearCart()
        router.push(`/pedido/${data.order_id}`)

    } catch (e) {
        console.error(e)
        alert(e.response?.data?.message || "Error al crear pedido :(")
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 pb-20">
        <header class="bg-white p-4 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-4">
                <router-link to="/" class="p-2 hover:bg-gray-100 rounded-full">
                    <ArrowLeft class="w-6 h-6" />
                </router-link>
                <h1 class="font-bold text-lg">Finalizar Pedido</h1>
            </div>
            <button @click="cartStore.clearCart(); router.push('/')" class="text-sm text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg border border-red-200 transition-colors font-medium flex items-center gap-1">
                <Trash2 class="w-4 h-4" />
                Vaciar
            </button>
        </header>

        <main class="max-w-2xl mx-auto p-4 space-y-6 mt-4">
            
            <!-- Empty Cart -->
            <div v-if="cartStore.items.length === 0" class="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-lg">Tu carrito está vacío.</p>
                <router-link to="/" class="mt-4 inline-block bg-orange-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-orange-600 transition-colors">
                    Ver Menú
                </router-link>
            </div>

            <!-- Items Summary -->
            <template v-else>
                <div class="bg-white p-5 rounded-xl shadow-sm">
                    <h2 class="font-bold text-gray-800 mb-4">Resumen</h2>
                    <div v-for="item in cartStore.items" :key="item.key" class="flex gap-4 py-3 border-b border-gray-50 last:border-0 items-start">
                        <img v-if="item.imageUrl" :src="item.imageUrl" class="w-16 h-16 rounded-lg object-cover bg-gray-100 shrink-0 border border-gray-50">
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <span><span class="font-bold text-gray-900">{{ item.quantity }}x</span> <span class="font-bold text-gray-800">{{ item.name }}</span></span>
                                <span class="font-medium">${{ (item.unitPrice * item.quantity).toFixed(2) }}</span>
                            </div>
                            <div v-if="item.modifiers && item.modifiers.length > 0" class="text-xs text-gray-500 mt-1">
                                {{ item.modifiers.map(m => m.name).join(', ') }}
                            </div>
                            <p v-if="item.specialInstructions" class="text-xs text-slate-500 italic mt-1">
                               "{{ item.specialInstructions }}"
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-between mt-4 pt-4 border-t border-gray-100 font-bold text-xl">
                        <span>Total</span>
                        <span>${{ cartStore.cartTotal.toFixed(2) }}</span>
                    </div>
                </div>

                <!-- Datos de Entrega -->
                <div class="bg-white p-5 rounded-xl shadow-sm space-y-4">
                    <h2 class="font-bold text-gray-800">Tipo de Entrega</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <label 
                            class="border rounded-xl p-4 flex flex-col items-center gap-2 cursor-pointer transition-colors"
                            :class="orderType === 'PICKUP' ? 'border-orange-500 bg-orange-50' : 'hover:bg-gray-50'"
                        >
                            <input type="radio" value="PICKUP" v-model="orderType" class="hidden">
                            <ShoppingBag class="w-6 h-6" />
                            <span class="font-medium">Para llevar</span>
                        </label>
                        <label 
                            class="border rounded-xl p-4 flex flex-col items-center gap-2 cursor-pointer transition-colors"
                            :class="orderType === 'DELIVERY' ? 'border-orange-500 bg-orange-50' : 'hover:bg-gray-50'"
                        >
                            <input type="radio" value="DELIVERY" v-model="orderType" class="hidden">
                            <MapPin class="w-6 h-6" />
                            <span class="font-medium">Domicilio</span>
                        </label>
                    </div>

                    <div v-if="orderType === 'DELIVERY'" class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Dirección de Entrega</label>
                        
                        <!-- Direcciones Guardadas -->
                        <div v-if="customerStore.profile.addresses.length > 0" class="space-y-2 mb-3">
                            <label v-for="(addr, idx) in customerStore.profile.addresses" :key="idx" class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-orange-500 bg-orange-50': address === addr && !useNewAddress }">
                                <input type="radio" v-model="address" :value="addr" @change="useNewAddress = false" class="w-4 h-4 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm text-gray-800">{{ addr }}</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-orange-500 bg-orange-50': useNewAddress }">
                                <input type="radio" v-model="useNewAddress" :value="true" @change="address = newAddress" class="w-4 h-4 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm font-medium text-gray-800">Otra dirección...</span>
                            </label>
                        </div>

                        <!-- Nueva Dirección -->
                        <textarea 
                            v-if="customerStore.profile.addresses.length === 0 || useNewAddress"
                            v-model="newAddress"
                            @input="address = newAddress; useNewAddress = true"
                            rows="2"
                            class="w-full border border-gray-300 rounded-lg p-3 shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none"
                            placeholder="Escribe tu calle, número, colonia, referencias..."
                        ></textarea>
                    </div>
                </div>

                <!-- Datos Personales -->
                <div class="bg-white p-5 rounded-xl shadow-sm space-y-4">
                     <h2 class="font-bold text-gray-800">Tus Datos</h2>
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" v-model="name" class="w-full border border-gray-200 rounded-lg p-3 outline-none focus:ring-2 focus:ring-orange-500" placeholder="Juan Pérez">
                     </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono (WhatsApp)</label>
                        <input type="tel" v-model="phone" class="w-full border border-gray-200 rounded-lg p-3 outline-none focus:ring-2 focus:ring-orange-500" placeholder="662 123 4567">
                     </div>
                </div>

                <!-- Actions -->
                <button 
                    @click="submitOrder"
                    :disabled="loading"
                    class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg shadow-xl hover:bg-green-700 transition-all flex justify-center items-center gap-2"
                >
                    <div v-if="loading" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span v-else>Confirmar Pedido - ${{ cartStore.cartTotal.toFixed(2) }}</span>
                </button>
            </template>

        </main>
    </div>
</template>
