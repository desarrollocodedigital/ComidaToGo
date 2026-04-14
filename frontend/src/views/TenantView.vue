<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { ShoppingBag, ChevronLeft, User, X } from 'lucide-vue-next'
import ProductModal from '../components/ProductModal.vue'
import CartDrawer from '../components/CartDrawer.vue'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()

const company = ref(null)
const loading = ref(true)
const error = ref(null)

// Modal State
const isModalOpen = ref(false)
const selectedProduct = ref(null)

// Drawer State
const isCartOpen = ref(false)

// Guest Profile State
const showWelcomeModal = ref(false)
const guestName = ref(authStore.user.name || '')
const guestPhone = ref(authStore.user.phone || '')

onMounted(async () => {
    // Sincronizar con sesión activa si existe
    if (authStore.isAuthenticated && !authStore.isConfigured) {
        authStore.setGuestProfile(authStore.user.name, '')
        guestName.value = authStore.user.name
    }

    try {
        const { data } = await axios.get(`/api.php/tenant/${route.params.slug}`)
        company.value = data
    } catch (e) {
        error.value = "No pudimos cargar el menú de este negocio."
    } finally {
        loading.value = false
    }
})

const saveGuestProfile = () => {
    authStore.setGuestProfile(guestName.value, guestPhone.value)
    showWelcomeModal.value = false
    
    // Si tiene productos en el carrito, después de configurar el perfil lo llevamos al checkout
    if (cartStore.cartCount > 0) {
        router.push('/checkout')
    }
}

const openProductModal = (product) => {
    selectedProduct.value = product
    isModalOpen.value = true
}

const handleAddToCart = async ({ product, quantity, modifiers, special_instructions }) => {
    const success = await cartStore.addItem(
        product, 
        quantity, 
        modifiers, 
        company.value.id, 
        company.value.name, 
        special_instructions
    )
    if (success) {
        // Opcional: Mostrar toast o abrir drawer automáticamente
        // isCartOpen.value = true
    }
}

const goToCheckout = () => {
    if (!authStore.isConfigured) {
        showWelcomeModal.value = true
    } else {
        router.push('/checkout')
    }
}
</script>

<template>
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
    </div>

    <div v-else-if="error" class="text-center py-20">
        <h2 class="text-2xl font-bold text-gray-700">{{ error }}</h2>
        <router-link to="/" class="text-orange-500 mt-4 inline-block hover:underline">Volver al inicio</router-link>
    </div>

    <div v-else class="bg-gray-50 min-h-screen pb-20">
        <!-- Header Sticky -->
        <div class="sticky top-0 bg-white z-10 shadow-sm border-b border-gray-100">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <router-link to="/" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <ChevronLeft class="w-6 h-6 text-gray-600" />
                    </router-link>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 leading-tight">{{ company.name }}</h1>
                        <p class="text-xs text-green-600 font-medium" v-if="company.is_open">Abierto ahora</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="showWelcomeModal = true"
                        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        title="Mi Perfil"
                    >
                        <User class="w-6 h-6 text-gray-800" />
                    </button>
                    <button 
                    @click="isCartOpen = true"
                    class="relative p-2 hover:bg-gray-100 rounded-full"
                    >
                        <ShoppingBag class="w-6 h-6 text-gray-800" />
                        <span v-if="cartStore.cartCount > 0" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border border-white px-1">
                            {{ cartStore.cartCount }}
                        </span>
                    </button>
                </div>
            </div>
            
            <!-- Category Scroll -->
            <div class="border-t border-gray-50">
                <div class="max-w-3xl mx-auto flex overflow-x-auto px-4 py-2 gap-4 no-scrollbar justify-center sm:justify-start lg:justify-center">
                    <a 
                        v-for="cat in company.menu" 
                        :key="cat.id" 
                        :href="`#cat-${cat.id}`"
                        class="whitespace-nowrap px-4 py-1.5 rounded-full bg-gray-100 text-gray-600 text-sm font-medium hover:bg-gray-200 transition-colors"
                    >
                        {{ cat.name }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Menu Content -->
        <main class="max-w-3xl mx-auto px-4 py-6">
            <div v-for="category in company.menu" :key="category.id" :id="`cat-${category.id}`" class="mb-8 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">{{ category.name }}</h2>
                
                <div class="grid gap-4">
                    <div 
                        v-for="product in category.products" 
                        :key="product.id"
                        @click="openProductModal(product)"
                        class="bg-white p-4 rounded-xl border border-gray-100 flex gap-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                    >
                        <!-- Info -->
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 mb-1">{{ product.name }}</h3>
                            <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ product.description }}</p>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-lg text-gray-900">${{ parseFloat(product.price).toFixed(2) }}</span>
                                <button class="bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg text-sm font-bold hover:bg-orange-100 transition-colors flex items-center gap-1">
                                    Agregar
                                </button>
                            </div>
                        </div>
                        <!-- Imagen -->
                        <div v-if="product.image_url" class="w-24 h-24 bg-gray-200 rounded-lg flex-shrink-0 overflow-hidden">
                             <img :src="product.image_url" class="w-full h-full object-cover" />
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Floating Cart Button (Mobile) -->
        <div v-if="cartStore.cartCount > 0" class="fixed bottom-6 left-0 right-0 px-4 z-20 flex justify-center">
            <button 
                @click="isCartOpen = true"
                class="bg-black text-white px-6 py-3 rounded-full shadow-2xl flex items-center gap-3 w-full max-w-sm hover:scale-105 transition-transform"
            >
                <div class="bg-white text-black text-xs font-bold h-6 w-6 rounded-full flex items-center justify-center">
                    {{ cartStore.cartCount }}
                </div>
                <span class="font-medium flex-1 text-left">Ver tu pedido</span>
                <span class="font-bold">${{ cartStore.cartTotal.toFixed(2) }}</span>
            </button>
        </div>

        <!-- Components -->
        <ProductModal 
            :is-open="isModalOpen"
            :product="selectedProduct"
            @close="isModalOpen = false"
            @add-to-cart="handleAddToCart"
        />

        <CartDrawer
            :is-open="isCartOpen"
            @close="isCartOpen = false"
            @checkout="goToCheckout"
        />

        <!-- Welcome / Guest Profile Modal -->
        <div v-if="showWelcomeModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl relative">
                <button v-if="authStore.isConfigured" @click="showWelcomeModal = false" class="absolute right-4 top-4 text-gray-400 hover:text-gray-600">
                    <X class="w-6 h-6" />
                </button>

                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <User class="w-8 h-8" />
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800" v-if="!authStore.isConfigured">¡Bienvenido!</h2>
                    <h2 class="text-2xl font-bold text-gray-800" v-else>Tu Perfil</h2>
                    <p class="text-sm text-gray-500 mt-1" v-if="!authStore.isConfigured">Para que pedir sea más rápido, ¿Cómo te llamas?</p>
                </div>

                <form @submit.prevent="saveGuestProfile" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tu Nombre</label>
                        <input type="text" v-model="guestName" required class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-orange-500" placeholder="Ej. Juan Pérez">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono (WhatsApp)</label>
                        <input type="tel" v-model="guestPhone" required class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-orange-500" placeholder="662 123 4567">
                    </div>

                    <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 mt-2 rounded-xl shadow-lg hover:bg-orange-600 transition-colors">
                        {{ authStore.isConfigured ? 'Guardar Cambios' : 'Empezar a pedir' }}
                    </button>
                    <!-- Skip only allowed if not configured yet (first time prompt) -->
                    <button type="button" v-if="!authStore.isConfigured" @click="showWelcomeModal = false" class="w-full mt-3 text-sm text-gray-500 hover:underline">
                        Omitir por ahora
                    </button>
                </form>
            </div>
        </div>

    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
