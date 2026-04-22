<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { ShoppingBag, ChevronLeft, User, X, LogIn, UserPlus, Star, Plus } from 'lucide-vue-next'
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

// Auth Modal State
const showAuthModal = ref(false)

const featuredProducts = computed(() => {
    if (!company.value || !company.value.menu) return []
    const featured = []
    company.value.menu.forEach(cat => {
        cat.products.forEach(prod => {
            if (Number(prod.is_featured)) {
                featured.push(prod)
            }
        })
    })
    return featured
})

onMounted(async () => {
    // Sincronizar con sesión activa si existe
    if (authStore.isAuthenticated && !authStore.isConfigured) {
        authStore.setGuestProfile(authStore.user.name, '')
        guestName.value = authStore.user.name
    }

    try {
        const { data } = await axios.get(`/api.php/tenant/${route.params.slug}`)
        
        // Normalizar URLs de imágenes en el menú
        if (data.menu) {
            data.menu.forEach(cat => {
                cat.products.forEach(p => {
                    if (p.image_url && !p.image_url.startsWith('http')) {
                        const cleanPath = p.image_url.startsWith('/') ? p.image_url.slice(1) : p.image_url;
                        p.image_url = import.meta.env.BASE_URL + cleanPath;
                    }
                })
            })
        }
        company.value = data

        // Lógica de Deep Linking: Abrir platillo si viene prod_id en URL
        const prodId = route.query.prod_id
        if (prodId) {
            let found = false
            for (const cat of company.value.menu) {
                const product = cat.products.find(p => p.id == prodId)
                if (product) {
                    openProductModal(product)
                    found = true
                    break
                }
            }
        }
    } catch (e) {
        error.value = "No pudimos cargar el menú de este negocio."
    } finally {
        loading.value = false
    }
})


const openProductModal = (product) => {
    if (!authStore.isAuthenticated) {
        showAuthModal.value = true
        return
    }
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
    if (!authStore.isAuthenticated) {
        showAuthModal.value = true
    } else {
        router.push('/checkout')
    }
}

// Bloquear scroll del body cuando algún modal o el carrito están abiertos
watch([isModalOpen, isCartOpen, showAuthModal], ([modal, cart, auth]) => {
    if (modal || cart || auth) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

// Limpieza para asegurar que el scroll se reactive al salir de la vista (ej. al ir a Checkout)
onUnmounted(() => {
    document.body.style.overflow = ''
})
</script>

<template>
    <!-- Global Menu Skeleton (Initial Loading) -->
    <div v-if="loading" class="bg-white min-h-screen">
        <!-- Header Skeleton -->
        <div class="bg-white border-b border-gray-100 px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4 animate-pulse">
                <div class="w-10 h-10 bg-gray-100 rounded-full"></div>
                <div class="space-y-2">
                    <div class="w-32 h-4 bg-gray-100 rounded"></div>
                    <div class="w-20 h-3 bg-gray-100 rounded"></div>
                </div>
            </div>
            <div class="flex gap-2">
                <div class="w-10 h-10 bg-gray-100 rounded-full animate-pulse"></div>
                <div class="w-10 h-10 bg-gray-100 rounded-full animate-pulse"></div>
            </div>
        </div>

        <!-- Categories Skeleton -->
        <div class="border-b border-gray-50 bg-white py-3">
            <div class="max-w-3xl mx-auto px-4 flex gap-4 overflow-hidden">
                <div v-for="i in 5" :key="i" class="w-24 h-8 bg-gray-100 rounded-full animate-pulse"></div>
            </div>
        </div>

        <main class="max-w-3xl mx-auto px-4 py-10 space-y-12">
            <!-- Featured Skeleton -->
            <div class="space-y-6">
                <div class="w-48 h-6 bg-gray-100 rounded animate-pulse"></div>
                <div class="flex gap-4 overflow-hidden">
                    <div v-for="i in 2" :key="i" class="min-w-[280px] h-32 bg-gray-50 rounded-3xl border border-gray-100 animate-pulse"></div>
                </div>
            </div>

            <!-- Products List Skeleton -->
            <div class="space-y-8">
                <div v-for="i in 3" :key="i" class="space-y-4">
                    <div class="w-40 h-5 bg-gray-100 rounded animate-pulse"></div>
                    <div v-for="j in 2" :key="j" class="flex gap-4 p-4 rounded-2xl border border-gray-50">
                        <div class="flex-1 space-y-3 animate-pulse">
                            <div class="w-3/4 h-5 bg-gray-100 rounded"></div>
                            <div class="w-full h-3 bg-gray-100 rounded"></div>
                            <div class="w-1/2 h-6 bg-gray-100 rounded mt-4"></div>
                        </div>
                        <div class="w-24 h-24 bg-gray-100 rounded-xl animate-pulse"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div v-else-if="error" class="text-center py-20">
        <h2 class="text-2xl font-bold text-gray-700">{{ error }}</h2>
        <router-link to="/" class="text-orange-500 mt-4 inline-block hover:underline">Volver al inicio</router-link>
    </div>

    <div v-else class="bg-gray-50 min-h-screen pb-20">
        <!-- Header Sticky -->
        <div class="sticky top-0 bg-white z-40 shadow-sm border-b border-gray-100">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <router-link to="/" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <ChevronLeft class="w-6 h-6 text-gray-600" />
                    </router-link>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 leading-tight">{{ company.name }}</h1>
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-medium" :class="company.is_open ? 'text-green-600' : 'text-red-500'">
                                {{ company.is_open ? 'Abierto ahora' : 'Cerrado ahora' }}
                            </p>
                            <span v-if="Number(company.average_rating) > 0" class="text-gray-300">|</span>
                            <div v-if="Number(company.average_rating) > 0" class="flex items-center gap-1">
                                <Star class="w-3 h-3 text-yellow-500 fill-yellow-500" />
                                <span class="text-xs font-black text-gray-700">{{ Number(company.average_rating).toFixed(1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        v-if="!authStore.isAuthenticated"
                        @click="router.push('/login')"
                        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        title="Iniciar Sesión"
                    >
                        <User class="w-6 h-6 text-gray-800" />
                    </button>
                    <div v-else class="p-2 text-gray-800 font-bold text-sm bg-gray-50 rounded-full px-4">
                        {{ authStore.user.name.split(' ')[0] }}
                    </div>
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
                <div class="max-w-3xl mx-auto flex overflow-x-auto px-4 py-2 gap-4 no-scrollbar justify-start lg:justify-center">
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
            <!-- Featured Products Section -->
            <div v-if="featuredProducts.length > 0" class="mb-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="flex items-center gap-2 mb-4">
                    <div class="p-1.5 bg-amber-100 text-amber-600 rounded-lg">
                        <Star class="w-5 h-5 fill-current" />
                    </div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Nuestros Preferidos</h2>
                </div>

                <div class="flex gap-4 overflow-x-auto pb-4 no-scrollbar -mx-4 px-4">
                    <div 
                        v-for="product in featuredProducts" 
                        :key="`feat-${product.id}`"
                        @click="openProductModal(product)"
                        class="min-w-[280px] bg-white rounded-3xl border border-amber-100 shadow-sm hover:shadow-xl transition-all p-4 cursor-pointer group relative overflow-hidden"
                    >
                        <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-[100px] -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                        
                        <div class="flex gap-4 relative z-10">
                            <div v-if="product.image_url" class="w-20 h-20 rounded-2xl overflow-hidden shadow-md flex-shrink-0 bg-amber-50 animate-pulse">
                                <img 
                                    :src="product.image_url" 
                                    @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                    class="w-full h-full object-cover transition-all duration-700 opacity-0" 
                                />
                            </div>
                            <div class="flex-1">
                                <h3 class="font-black text-gray-900 mb-1 group-hover:text-amber-600 transition-colors line-clamp-1">{{ product.name }}</h3>
                                <p class="text-[11px] text-gray-500 line-clamp-2 mb-2 leading-tight">{{ product.description }}</p>
                                <div class="flex items-center justify-between mt-auto">
                                    <span class="font-black text-amber-600">${{ parseFloat(product.price).toFixed(2) }}</span>
                                    <div class="bg-amber-500 text-white p-1.5 rounded-xl shadow-lg shadow-amber-100 group-hover:bg-black transition-colors">
                                        <Plus class="w-4 h-4" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                        <div v-if="product.image_url" class="w-24 h-24 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden animate-pulse">
                             <img 
                                :src="product.image_url" 
                                @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                class="w-full h-full object-cover transition-all duration-700 opacity-0" 
                            />
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

        <!-- Auth Required Modal -->
        <div v-if="showAuthModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-all duration-300">
            <div class="bg-white rounded-3xl max-w-sm w-full p-8 shadow-2xl relative animate-in fade-in zoom-in duration-300">
                <button @click="showAuthModal = false" class="absolute right-6 top-6 text-gray-400 hover:text-gray-600 transition-colors">
                    <X class="w-6 h-6" />
                </button>
                
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-orange-100 text-orange-600 rounded-3xl flex items-center justify-center mx-auto mb-4 rotate-3 shadow-inner">
                        <ShoppingBag class="w-10 h-10" />
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 leading-tight mb-2">¡Casi listo!</h2>
                    <p class="text-sm text-gray-500">Para poder procesar tu pedido y brindarte un mejor servicio, necesitamos que inicies sesión o crees una cuenta.</p>
                </div>

                <div class="space-y-3">
                    <router-link 
                        to="/login"
                        class="w-full bg-orange-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-100 flex items-center justify-center gap-2 hover:bg-orange-600 transition-all hover:scale-[1.02] active:scale-95"
                    >
                        Iniciar Sesión
                    </router-link>
                    <router-link 
                        to="/registro"
                        class="w-full bg-slate-800 text-white font-black py-4 rounded-2xl shadow-lg shadow-slate-200 flex items-center justify-center gap-2 hover:bg-slate-900 transition-all hover:scale-[1.02] active:scale-95"
                    >
                        Crear una Cuenta
                    </router-link>
                    <button 
                        @click="showAuthModal = false" 
                        class="w-full py-2 text-sm text-slate-400 font-bold hover:text-slate-600 transition-colors uppercase tracking-widest mt-2"
                    >
                        Continuar explorando
                    </button>
                </div>
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
