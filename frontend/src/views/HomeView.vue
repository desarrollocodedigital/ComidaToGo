<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { Search, MapPin, Clock } from 'lucide-vue-next'

const auth = useAuthStore()

const searchQuery = ref('')
const results = ref([])
const loading = ref(false)
const featuredCompanies = ref([])

const popularCategories = [
    { name: 'Tacos', emoji: '🌮' },
    { name: 'Pizza', emoji: '🍕' },
    { name: 'Sushi', emoji: '🍣' },
    { name: 'Burgers', emoji: '🍔' },
    { name: 'Bebidas', emoji: '🥤' },
    { name: 'Postres', emoji: '🍩' },
    { name: 'Mariscos', emoji: '🍤' },
]

// Cargar inicial para tener algo en "Destacados"
const loadFeatured = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api.php/search?q=`) // Trae todos (limit 20)
        featuredCompanies.value = data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const search = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/api.php/search?q=${searchQuery.value}`)
        results.value = response.data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadFeatured()
})
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Hero Section -->
        <div class="relative bg-gray-900 overflow-hidden">
            <!-- Auth Button Absolute -->
            <div class="absolute top-6 right-6 z-20 flex items-center gap-3">
                <!-- DEMO BUTTON -->
                <router-link to="/demo" class="animate-pulse flex items-center gap-2 text-white font-bold bg-gradient-to-r from-pink-500 to-orange-500 hover:from-pink-600 hover:to-orange-600 px-6 py-2 rounded-full shadow-lg shadow-orange-500/50 transition-all">
                    ✨ MODO DEMO
                </router-link>

                <div v-if="auth.user" class="flex items-center gap-3 ml-4">
                    <router-link v-if="auth.user.role === 'OWNER'" to="/admin/dashboard" class="text-white hover:text-orange-200 font-bold bg-white/10 px-4 py-2 rounded-full backdrop-blur-sm">
                        Panel
                    </router-link>
                    <div class="flex items-center gap-2 text-white bg-black/30 px-3 py-1.5 rounded-full backdrop-blur-sm">
                        <span class="text-sm font-medium">{{ auth.user.name }}</span>
                        <button @click="auth.logout" class="text-xs text-red-300 hover:text-red-100 ml-2">Salir</button>
                    </div>
                </div>
                <router-link v-else to="/login" class="text-white font-bold bg-white/10 hover:bg-white/20 px-6 py-2 rounded-full backdrop-blur-md border border-white/20 transition-all">
                    Ingresar
                </router-link>
            </div>

            <!-- Background Decoration (Gradient/Image) -->
            <div class="absolute inset-0 bg-gradient-to-r from-orange-600 to-red-600 opacity-90"></div>
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=2000&auto=format&fit=crop')] bg-cover bg-center opacity-20 mix-blend-overlay"></div>

            <div class="relative max-w-4xl mx-auto px-4 pt-20 pb-24 text-center">
                <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 tracking-tight leading-tight">
                    Comida local,<br/> directo a tu mesa.
                </h1>
                <p class="text-lg text-orange-100 mb-10 max-w-xl mx-auto">
                    Explora los mejores restaurantes y puestos de tu ciudad. Pide para llevar o a domicilio sin complicaciones.
                </p>
                
                <!-- Search Box -->
                <div class="bg-white p-2 rounded-2xl shadow-2xl max-w-2xl mx-auto flex flex-col md:flex-row gap-2">
                    <div class="flex-1 relative flex items-center px-4 border-b md:border-b-0 md:border-r border-gray-100">
                        <MapPin class="w-5 h-5 text-gray-400 shrink-0" />
                        <input 
                            type="text" 
                            placeholder="Ubicación actual" 
                            class="w-full p-3 bg-transparent focus:outline-none text-gray-700 truncate"
                        />
                        <button class="text-xs text-orange-600 font-bold hover:bg-orange-50 px-2 py-1 rounded whitespace-nowrap">
                            Usar GPS
                        </button>
                    </div>
                    <div class="flex-[1.5] flex items-center px-2">
                         <Search class="w-5 h-5 text-gray-400 ml-2" />
                         <input 
                            v-model="searchQuery"
                            @keyup.enter="search"
                            type="text" 
                            placeholder="¿Qué se te antoja? Tacos, Sushi..." 
                            class="w-full p-3 bg-transparent focus:outline-none text-gray-800 font-medium"
                        />
                    </div>
                    <button 
                        @click="search"
                        class="bg-black text-white px-8 py-3 rounded-xl font-bold hover:bg-gray-800 transition-colors"
                    >
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="max-w-7xl mx-auto px-4 -mt-10 relative z-10 mb-12">
            <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">Categorías Populares</h3>
                <div class="flex gap-4 overflow-x-auto pb-4 no-scrollbar justify-between min-w-full">
                    <div 
                        v-for="cat in popularCategories" 
                        :key="cat.name"
                        class="flex flex-col items-center gap-3 cursor-pointer group min-w-[80px]"
                    >
                        <div class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-50 group-hover:bg-orange-50 flex items-center justify-center text-3xl transition-colors border border-gray-100 group-hover:border-orange-200 shadow-sm">
                            {{ cat.emoji }}
                        </div>
                        <span class="text-sm font-medium text-gray-600 group-hover:text-orange-600 transition-colors">{{ cat.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Grid -->
        <div class="max-w-7xl mx-auto px-4 pb-20">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ searchQuery ? `Resultados para "${searchQuery}"` : 'Restaurantes Destacados' }}
                </h2>
                <!-- Filter Button Placeholder -->
                <button class="flex items-center gap-2 text-sm font-bold text-gray-600 bg-white px-4 py-2 rounded-full border hover:bg-gray-50">
                     <span>Filtros</span>
                </button>
            </div>

            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                 <!-- Skeletons -->
                 <div v-for="i in 3" :key="i" class="bg-white rounded-2xl h-64 animate-pulse"></div>
            </div>

            <!-- List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <router-link 
                    v-for="company in (results.length > 0 ? results : featuredCompanies)" 
                    :key="company.id"
                    :to="`/${company.slug}`"
                    class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-300 group ring-1 ring-gray-100"
                >
                    <!-- Banner Image -->
                    <div class="h-48 relative overflow-hidden">
                        <img 
                            :src="company.banner_url || 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=80&w=1000'" 
                            class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" 
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        
                        <!-- Badges -->
                        <div class="absolute top-4 right-4 flex gap-2">
                             <div 
                                class="px-3 py-1 text-xs font-bold rounded-full backdrop-blur-md"
                                :class="company.is_open ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white'"
                             >
                                 {{ company.is_open ? 'ABIERTO' : 'CERRADO' }}
                             </div>
                        </div>

                         <!-- Rating -->
                         <div class="absolute bottom-4 left-4 flex items-center gap-1 text-white font-bold text-sm bg-black/20 backdrop-blur-md px-2 py-1 rounded-lg">
                            <span>⭐</span>
                            <span>{{ company.average_rating }}</span>
                            <span class="font-normal text-white/80">(24)</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5 relative pt-12">
                        <!-- Logo (Floating) -->
                        <div class="absolute -top-10 right-6 w-20 h-20 rounded-full border-4 border-white shadow-lg bg-white overflow-hidden">
                             <img 
                                :src="company.logo_url || `https://ui-avatars.com/api/?name=${company.name}&background=random`" 
                                class="w-full h-full object-cover"
                             />
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-orange-600 transition-colors">{{ company.name }}</h3>
                        <div class="flex items-center text-gray-500 text-sm mb-4">
                            <span>Comida Mexicana</span>
                            <span class="mx-2">•</span>
                            <span class="flex items-center text-green-600 font-medium">
                                <Clock class="w-3.5 h-3.5 mr-1" />
                                20-30 min
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-2 text-xs text-gray-400 font-medium bg-gray-50 p-2 rounded-lg">
                             <MapPin class="w-3 h-3" />
                             <span class="truncate">Av. Principal 123, Centro</span>
                        </div>
                    </div>
                </router-link>
            </div>
            
             <!-- Empty State -->
            <div v-if="!loading && results.length === 0 && searchQuery" class="text-center py-20">
                <p class="text-gray-500">No encontramos resultados para "{{ searchQuery }}"</p>
                <button @click="searchQuery = ''; search()" class="text-orange-600 font-bold mt-2">Limpiar búsqueda</button>
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
