<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useDialogStore } from '../stores/dialog'
import axios from 'axios'
import { ArrowLeft, Clock, MapPin, ShoppingBag, Trash2, Navigation } from 'lucide-vue-next'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const dialog = useDialogStore()

const loading = ref(false)
const isInitialLoading = ref(true)
const detectingLocation = ref(false)
const orderType = ref('PICKUP') // PICKUP | DELIVERY

// Cargar datos locales del comensal
const name = ref(authStore.user?.name || '')
const phone = ref(authStore.user?.phone || '')
const address = ref('') // Dirección activa
const references = ref('') // Referencias activas
const newAddress = ref('') // Valor temporal del textArea de dirección
const newReferences = ref('') // Valor temporal del textArea de referencias
const useNewAddress = ref(false)
const scheduledTime = ref('') // Hora para pasar a recoger
const isBusinessOpen = ref(true)
const businessStatusMessage = ref('')

// La dirección que realmente se va a enviar
const effectiveAddress = computed(() => {
    if (orderType.value !== 'DELIVERY') return ''
    if (useNewAddress.value) return newAddress.value.trim()
    
    // Si address.value es un objeto (vibración de versiones anteriores), extraer .address
    if (typeof address.value === 'object' && address.value !== null) {
        return (address.value.address || '').trim()
    }
    return String(address.value).trim()
})

const effectiveReferences = computed(() => {
    if (orderType.value !== 'DELIVERY') return ''
    if (useNewAddress.value) return newReferences.value.trim()
    
    if (typeof address.value === 'object' && address.value !== null) {
        return (address.value.references || '').trim()
    }
    return references.value.trim()
})

onMounted(() => {
    // Seguridad: Redirigir si no está autenticado
    if (!authStore.isAuthenticated) {
        router.push('/')
        return
    }

    if (authStore.user?.addresses?.length > 0) {
        address.value = authStore.user.addresses[0] // Set default
        useNewAddress.value = false
    } else {
        useNewAddress.value = true
    }

    // Validar estado del negocio
    if (cartStore.companyId) {
        checkBusinessStatus()
    }

    // Pequeño retardo para que el skeleton sea visible y la transición suave
    setTimeout(() => {
        isInitialLoading.value = false
    }, 600)
})

const checkBusinessStatus = async () => {
    try {
        const { data } = await axios.get(`/api.php/tenant/${cartStore.companyId}`)
        isBusinessOpen.value = !!data.is_open
        businessStatusMessage.value = data.status_info?.message || ''
        
        if (!data.is_open) {
            dialog.alert({
                title: 'Negocio Cerrado',
                message: businessStatusMessage.value || 'Lo sentimos, el negocio acaba de cerrar y no puede recibir más pedidos por ahora.'
            })
        }
    } catch (e) {
        console.error("Error checking business status", e)
    }
}

const deleteAddress = async (idx, addr) => {
    const isConfirmed = await dialog.confirm({
        title: 'Eliminar Dirección',
        message: '¿Estás seguro de que quieres eliminar esta dirección permanentemente?'
    })
    
    if (isConfirmed) {
        await authStore.removeAddress(idx)
        // Normalizar comparación
        const activeAddrStr = typeof address.value === 'object' ? address.value.address : address.value
        const targetAddrStr = typeof addr === 'object' ? addr.address : addr

        if (activeAddrStr === targetAddrStr) {
            if (authStore.user.addresses.length > 0) {
                const first = authStore.user.addresses[0]
                address.value = first
                references.value = typeof first === 'object' ? first.references : ''
            } else {
                address.value = ''
                references.value = ''
                useNewAddress.value = true
            }
        }
    }
}

const detectLocation = () => {
    if (!navigator.geolocation) {
        dialog.alert({ title: 'No soportado', message: 'Tu navegador no soporta geolocalización' })
        return
    }

    detectingLocation.value = true
    navigator.geolocation.getCurrentPosition(async (position) => {
        try {
            const { latitude, longitude } = position.coords
            // Reverse geocoding with Nominatim (OpenStreetMap)
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=jsonv2`, {
                headers: {
                    'Accept-Language': 'es'
                }
            })
            const data = await response.json()
            
            if (data && data.address) {
                const a = data.address;
                const parts = [];
                
                // Calle
                if (a.road) parts.push(a.road);
                
                // Localidad (village, hamlet, suburb)
                const neighborhood = a.village || a.hamlet || a.suburb || a.neighbourhood;
                if (neighborhood) parts.push(neighborhood);
                
                // Municipio / Ciudad
                const city = a.city || a.municipality || a.town || a.county;
                if (city) parts.push(city);
                
                // Estado
                if (a.state) parts.push(a.state);

                const formattedAddress = parts.join(', ');
                
                newAddress.value = formattedAddress;
                address.value = formattedAddress;
                useNewAddress.value = true
            }
        } catch (e) {
            console.error("Error reverse geocoding", e)
            dialog.alert({ title: 'Error', message: 'No pudimos obtener la dirección exacta, por favor escríbela manualmente.' })
        } finally {
            detectingLocation.value = false
        }
    }, (error) => {
        detectingLocation.value = false
        console.error("Geolocation error", error)
        let msg = 'Error al obtener ubicación.'
        if (error.code === 1) msg = 'Por favor permite el acceso a tu ubicación en el navegador.'
        dialog.alert({ title: 'Permiso denegado', message: msg })
    }, {
        enableHighAccuracy: true,
        timeout: 8000,
        maximumAge: 0
    })
}

const submitOrder = async () => {
    if (!name.value || !phone.value) {
        await dialog.alert({ 
            title: 'Datos incompletos', 
            message: 'Por favor completa tu nombre y teléfono para poder procesar tu pedido.' 
        })
        return
    }

    if (orderType.value === 'DELIVERY' && !effectiveAddress.value) {
        await dialog.alert({ 
            title: 'Dirección faltante', 
            message: 'Has seleccionado entrega a domicilio. Por favor, ingresa una dirección de entrega válida.' 
        })
        return
    }

    // Auto-guardar en el perfil local y servidor:
    await authStore.setGuestProfile(name.value, phone.value)
    if (orderType.value === 'DELIVERY' && useNewAddress.value && newAddress.value.trim() !== '') {
        await authStore.addAddress({
            address: newAddress.value,
            references: newReferences.value
        })
    }

    loading.value = true
    try {
        const payload = {
            company_id: cartStore.companyId,
            customer_name: name.value,
            customer_phone: phone.value,
            customer_address: effectiveAddress.value,
            customer_references: effectiveReferences.value,
            order_type: orderType.value,
            scheduled_at: orderType.value === 'PICKUP' && scheduledTime.value ? (() => {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                return `${year}-${month}-${day} ${scheduledTime.value}:00`;
            })() : null,
            items: cartStore.items.map(item => ({
                product_id: item.productId,
                quantity: item.quantity,
                modifiers: item.modifiers.map(m => m.id),
                special_instructions: item.specialInstructions || ''
            }))
        }

        const { data } = await axios.post('api.php/orders', payload)
        
        // Success
        cartStore.clearCart()
        router.push(`/pedido/${data.order_id}`)

    } catch (e) {
        console.error(e)
        await dialog.alert({ 
            title: 'Error al crear pedido', 
            message: e.response?.data?.message || "No pudimos procesar tu pedido en este momento. Por favor, intenta de nuevo."
        })
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 pb-20">
        <header class="bg-white p-4 shadow-sm flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <button @click="router.back()" class="p-2 hover:bg-gray-100 rounded-full">
                    <ArrowLeft class="w-6 h-6" />
                </button>
                <h1 class="font-bold text-lg">Finalizar Pedido</h1>
            </div>
        </header>

        <!-- Skeleton Loading State -->
        <main v-if="isInitialLoading" class="max-w-2xl mx-auto p-4 space-y-6 mt-4">
            <div class="bg-white p-5 rounded-xl shadow-sm animate-pulse space-y-4">
                <div class="h-6 bg-gray-100 w-1/4 rounded"></div>
                <div v-for="i in 2" :key="i" class="flex gap-4 py-3">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-100 w-1/2 rounded"></div>
                        <div class="h-3 bg-gray-100 w-3/4 rounded"></div>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm animate-pulse space-y-6">
                <div class="h-6 bg-gray-100 w-1/3 rounded"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="h-20 bg-gray-50 rounded-xl"></div>
                    <div class="h-20 bg-gray-50 rounded-xl"></div>
                </div>
                <div class="h-32 bg-gray-50 rounded-xl"></div>
            </div>
        </main>

        <main v-else class="max-w-2xl mx-auto p-4 space-y-6 mt-4 animate-in fade-in duration-500">
            
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
                    <div v-for="item in cartStore.items" :key="item.key" class="flex gap-4 py-3 border-b border-gray-50 last:border-0 items-start transition-all">
                        <div v-if="item.imageUrl" class="w-16 h-16 rounded-lg bg-gray-100 shrink-0 overflow-hidden animate-pulse border border-gray-50">
                            <img 
                                :src="item.imageUrl" 
                                @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                class="w-full h-full object-cover transition-opacity duration-700 opacity-0"
                            >
                        </div>
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
                            <span class="font-medium">Pasar a recoger</span>
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

                    <div v-if="orderType === 'PICKUP'" class="mt-2 p-4 bg-orange-50/50 rounded-xl border border-orange-100 animate-in fade-in slide-in-from-top-2 duration-300">
                        <div class="flex items-center gap-3 mb-2">
                            <Clock class="w-5 h-5 text-orange-600" />
                            <label class="block text-sm font-bold text-gray-700">¿A qué hora pasarás por tu pedido?</label>
                        </div>
                        <input 
                            type="time" 
                            v-model="scheduledTime" 
                            class="w-full bg-white border border-gray-200 rounded-lg p-3 outline-none focus:ring-2 focus:ring-orange-500 font-medium text-gray-700 shadow-sm"
                        >

                    </div>

                    <div v-if="orderType === 'DELIVERY'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700">Dirección de Entrega</label>
                            <button 
                                v-if="!authStore.user.addresses || authStore.user.addresses.length === 0 || useNewAddress"
                                @click="detectLocation"
                                :disabled="detectingLocation"
                                class="text-xs font-bold text-orange-600 flex items-center gap-1 hover:text-orange-700 disabled:opacity-50 transition-colors"
                            >
                                <Navigation :class="{ 'animate-pulse': detectingLocation }" class="w-3 h-3" />
                                {{ detectingLocation ? 'Detectando...' : 'Usar ubicación actual' }}
                            </button>
                        </div>
                        
                        <!-- Direcciones Guardadas -->
                        <div v-if="authStore.user.addresses?.length > 0" class="space-y-2 mb-3">
                            <div v-for="(addr, idx) in authStore.user.addresses" :key="idx" class="flex items-center p-3 border rounded-lg transition-colors group" :class="{ 'border-orange-500 bg-orange-50': address === addr && !useNewAddress }">
                                <label class="flex-1 flex items-center gap-3 cursor-pointer">
                                    <input type="radio" v-model="address" :value="addr" @change="useNewAddress = false; references = (typeof addr === 'object' ? addr.references : '')" class="w-4 h-4 text-orange-600 focus:ring-orange-500">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-800">{{ typeof addr === 'object' ? addr.address : addr }}</span>
                                        <span v-if="typeof addr === 'object' && addr.references" class="text-xs text-gray-500 italic">{{ addr.references }}</span>
                                    </div>
                                </label>
                                <button type="button" @click.prevent="deleteAddress(idx, addr)" class="p-2 text-gray-400 opacity-0 group-hover:opacity-100 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Eliminar dirección">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-orange-500 bg-orange-50': useNewAddress }">
                                <input type="radio" v-model="useNewAddress" :value="true" @change="address = newAddress" class="w-4 h-4 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm font-medium text-gray-800">Otra dirección...</span>
                            </label>
                        </div>

                        <!-- Nueva Dirección -->
                        <div v-if="!authStore.user.addresses || authStore.user.addresses.length === 0 || useNewAddress" class="space-y-3">
                            <textarea 
                                v-model="newAddress"
                                @input="address = newAddress; useNewAddress = true"
                                rows="2"
                                class="w-full border border-gray-300 rounded-lg p-3 shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none"
                                placeholder="Escribe tu calle, número, colonia..."
                            ></textarea>
                            <textarea 
                                v-model="newReferences"
                                rows="1"
                                class="w-full border border-gray-300 rounded-lg p-3 shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-sm"
                                placeholder="Referencias (ej: Portón rojo, junto a la tienda...)"
                            ></textarea>
                        </div>
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
                        <input 
                            type="tel" 
                            v-model="phone" 
                            inputmode="numeric"
                            @input="phone = phone.replace(/\D/g, '')"
                            class="w-full border border-gray-200 rounded-lg p-3 outline-none focus:ring-2 focus:ring-orange-500" 
                            placeholder="6621234567"
                        >
                     </div>
                </div>

                <!-- Actions -->
                <div v-if="!isBusinessOpen" class="bg-red-50 p-4 rounded-xl border border-red-100 flex items-center gap-3 mb-4">
                    <Clock class="w-5 h-5 text-red-500" />
                    <p class="text-sm font-bold text-red-700">
                        {{ businessStatusMessage || 'El negocio está cerrado' }}. No es posible procesar el pedido.
                    </p>
                </div>

                <button 
                    @click="submitOrder"
                    :disabled="loading || !isBusinessOpen"
                    class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg shadow-xl hover:bg-green-700 disabled:opacity-50 disabled:grayscale transition-all flex justify-center items-center gap-2"
                >
                    <div v-if="loading" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span v-else>Confirmar Pedido - ${{ cartStore.cartTotal.toFixed(2) }}</span>
                </button>
            </template>

        </main>
    </div>
</template>
