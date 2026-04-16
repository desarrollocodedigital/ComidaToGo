<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { Search, MapPin, Clock, ShoppingBag, LogOut, LayoutDashboard, ChevronRight, X, UtensilsCrossed, Store } from 'lucide-vue-next'

const auth = useAuthStore()

const searchQuery = ref('')
const tempQuery = ref('')
const results = ref([])
const loading = ref(false)
const featuredCompanies = ref([])
const activeCategory = ref(null)
const searchType = ref('negocios') // 'negocios' | 'platillos'
const userCoords = ref(null)
const userState = ref(null)
const locationAddress = ref('')
const isSearching = ref(false) // Para saber si estamos filtrando
const locationStatus = ref('idle') // 'idle' | 'loading' | 'success' | 'error'
const isLocating = ref(false) // Nuevo: Indica si estamos esperando al GPS

const popularCategories = [
    { name: 'Tacos', emoji: '🌮' },
    { name: 'Pizza', emoji: '🍕' },
    { name: 'Sushi', emoji: '🍣' },
    { name: 'Burgers', emoji: '🍔' },
    { name: 'Bebidas', emoji: '🥤' },
    { name: 'Postres', emoji: '🍩' },
    { name: 'Mariscos', emoji: '🍤' },
]

const showModal = ref(false)
const selectedCompany = ref(null)

const scheduleDays = [
    { key: 'mon', name: 'Lunes' },
    { key: 'tue', name: 'Martes' },
    { key: 'wed', name: 'Miércoles' },
    { key: 'thu', name: 'Jueves' },
    { key: 'fri', name: 'Viernes' },
    { key: 'sat', name: 'Sábado' },
    { key: 'sun', name: 'Domingo' },
]

const getStatus = (company) => {
    if (company.status_mode === 'OPEN') return { text: 'Abierto ahora', color: 'text-green-600', bg: 'bg-green-100', dot: 'bg-green-500' };
    if (company.status_mode === 'CLOSED') return { text: 'Cerrado temporalmente', color: 'text-red-600', bg: 'bg-red-100', dot: 'bg-red-500' };

    try {
        const tz = company.timezone || 'America/Mexico_City';
        const now = new Date();
        const options = { timeZone: tz, hour: '2-digit', minute: '2-digit', hour12: false, weekday: 'short' };
        const formatter = new Intl.DateTimeFormat('en-US', options);
        const parts = formatter.formatToParts(now);
        
        const day = parts.find(p => p.type === 'weekday').value.toLowerCase(); 
        const hour = parts.find(p => p.type === 'hour').value;
        const minute = parts.find(p => p.type === 'minute').value;
        const currentTime = `${hour}:${minute}`;

        const schedule = company.schedule_config;
        if (!schedule || !schedule[day] || schedule[day].closed) {
            return { text: 'Cerrado hoy', color: 'text-gray-500', bg: 'bg-gray-100', dot: 'bg-gray-400' };
        }

        const { open, close } = schedule[day];
        if (currentTime >= open && currentTime <= close) {
            return { text: 'Abierto ahora', color: 'text-green-600', bg: 'bg-green-100', dot: 'bg-green-500' };
        } else if (currentTime < open) {
            return { text: `Abre a las ${open}`, color: 'text-orange-600', bg: 'bg-orange-50', dot: 'bg-orange-500' };
        } else {
            return { text: 'Cerrado por hoy', color: 'text-red-500', bg: 'bg-red-50', dot: 'bg-red-500' };
        }
    } catch (e) {
        return { text: 'Consultar horario', color: 'text-gray-500', bg: 'bg-gray-100', dot: 'bg-gray-400' };
    }
}

const openDetails = (company) => {
    selectedCompany.value = company
    showModal.value = true
}

// Cargar inicial para tener algo en "Destacados"
const loadFeatured = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api.php/search?q=`) 
        featuredCompanies.value = data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const search = async (type = 'negocios') => {
    loading.value = true
    
    // Si la búsqueda está vacía, forzamos a mostrar Negocios en lugar de Platillos
    const effectiveType = !tempQuery.value ? 'negocios' : type
    searchType.value = effectiveType
    searchQuery.value = tempQuery.value
    isSearching.value = true
    
    try {
        let url = `/api.php/search?q=${tempQuery.value}&type=${effectiveType}`
        if (userCoords.value) {
            url += `&lat=${userCoords.value.latitude}&lng=${userCoords.value.longitude}`
            if (userState.value) {
                url += `&state=${userState.value}`
            }
            console.log(`📍 Buscando en ${userState.value || 'coordenadas'}:`, userCoords.value)
        }
        const response = await axios.get(url)
        results.value = response.data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const clearFilters = () => {
    // Solo limpiamos la búsqueda y categoría, manteniendo la ubicación si existe
    activeCategory.value = null
    results.value = []
    searchQuery.value = ''
    tempQuery.value = ''
    
    if (userCoords.value) {
        search() // Re-buscar solo por ubicación
    } else {
        isSearching.value = false
        loadFeatured()
    }
}

const resetToGlobal = () => {
    // Limpieza total para volver al estado inicial absoluto
    userCoords.value = null
    userState.value = null
    locationAddress.value = ''
    locationStatus.value = 'idle'
    isSearching.value = false
    results.value = []
    searchQuery.value = ''
    tempQuery.value = ''
    activeCategory.value = null
    loadFeatured()
}

const getUserLocation = () => {
    if (!navigator.geolocation) {
        alert("Tu navegador no soporta geolocalización")
        return
    }

    locationStatus.value = 'loading'
    isLocating.value = true // Bloqueamos vista hasta tener ubicación
    
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude
            const lng = position.coords.longitude
            userCoords.value = { latitude: lat, longitude: lng }
            
            // Reverse Geocoding con Nominatim (Free)
            try {
                const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                locationAddress.value = response.data.display_name || "Ubicación detectada"
                
                // Extraer Estado para el filtro potente
                if (response.data.address && response.data.address.state) {
                    userState.value = response.data.address.state
                }
            } catch (e) {
                locationAddress.value = "Ubicación detectada"
            }

            locationStatus.value = 'success'
            // Re-buscar con ubicación
            await search(searchType.value)
            isLocating.value = false
        },
        (error) => {
            console.warn("Geolocalización automatica rechazada u omitida:", error)
            locationStatus.value = 'error'
            isLocating.value = false
            loadFeatured()
        }
    )
}

const formatDistance = (km) => {
    if (km === undefined || km === null) return null
    const dist = parseFloat(km)
    if (dist < 1) {
        return `${(dist * 1000).toFixed(0)}m`
    }
    return `${dist.toFixed(1)}km`
}

const getEstimatedTime = (km) => {
    if (km === undefined || km === null) return "20-30 min"
    const dist = parseFloat(km)
    
    // Ajuste para Moto: 10 min prep/espera + 2 min por km (aprox 30-35 km/h)
    const baseTime = 10
    const travelTime = Math.round(dist * 2)
    
    const minTime = baseTime + travelTime
    const maxTime = minTime + 5 // Rango de 5 min
    
    return `${minTime}-${maxTime} min`
}

const filterByCategory = async (catName, type) => {
    tempQuery.value = catName
    await search(type)
    
    // Scroll a resultados
    const el = document.getElementById('results-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
}

// Resetear cuando el buscador se limpia
watch(tempQuery, (newVal) => {
    if (newVal === '') {
        results.value = []
        searchQuery.value = ''
        activeCategory.value = null
        searchType.value = 'negocios'
        // Si no hay coordenadas ni query, ya no estamos "buscando" estrictamente para el empty state
        if (!userCoords.value) isSearching.value = false
    }
})

// Bloquear scroll del body cuando el modal está abierto
watch(showModal, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

const handleCategoryClick = (catName) => {
    if (activeCategory.value === catName) {
        // Al deseleccionar: Usar nuestra lógica de limpieza inteligente que respeta la ubicación
        clearFilters()
    } else {
        activeCategory.value = catName
    }
}

onMounted(() => {
    loadFeatured()
    // Autodetectar ubicación al iniciar para filtrado por estado inmediato
    getUserLocation()
})
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Hero Section -->
        <div class="relative bg-gray-900 overflow-hidden">
            <!-- Auth Button Absolute -->
            <div class="absolute top-6 right-6 z-20 flex items-center gap-3">

                <div v-if="auth.isAuthenticated" class="flex items-center gap-2 p-1.5 bg-black/20 backdrop-blur-md rounded-2xl border border-white/10 shadow-lg">
                    <!-- Mis Pedidos -->
                    <router-link to="/mis-pedidos" class="flex items-center gap-2 text-white hover:bg-white/20 px-4 py-2 rounded-xl transition-all font-bold text-sm">
                        <ShoppingBag class="w-4 h-4 text-orange-400" />
                        <span class="hidden md:inline">Mis Pedidos</span>
                    </router-link>

                    <!-- Panel (solo si es Owner) -->
                    <router-link v-if="auth.user.role === 'OWNER'" to="/admin/dashboard" class="flex items-center gap-2 text-white hover:bg-white/20 px-4 py-2 rounded-xl transition-all font-bold text-sm border-l border-white/10">
                        <LayoutDashboard class="w-4 h-4 text-blue-400" />
                        <span class="hidden md:inline">Panel</span>
                    </router-link>

                    <!-- Perfil y Salir -->
                    <div class="flex items-center gap-3 pl-3 pr-2 border-l border-white/10 ml-1">
                        <div class="flex flex-col items-end leading-none">
                            <span class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-0.5">Hola,</span>
                            <span class="text-sm font-bold text-white">{{ auth.user.name.split(' ')[0] }}</span>
                        </div>
                        <button @click="auth.logout" class="p-2 bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white rounded-xl transition-all group" title="Cerrar Sesión">
                            <LogOut class="w-4 h-4 transition-transform group-hover:scale-110" />
                        </button>
                    </div>
                </div>
                <div v-else class="flex items-center gap-2">
                    <router-link to="/login" class="text-white font-bold bg-white/10 hover:bg-white/20 px-5 py-2 rounded-full backdrop-blur-md border border-white/20 transition-all">
                        Iniciar Sesión
                    </router-link>
                    <router-link to="/registro" class="text-white font-bold bg-orange-600 hover:bg-orange-700 px-5 py-2 rounded-full shadow-lg transition-all">
                        Registrarse
                    </router-link>
                </div>
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
                            v-model="locationAddress"
                            type="text" 
                            placeholder="Ubicación actual" 
                            class="w-full p-3 bg-transparent focus:outline-none text-gray-700 truncate font-medium text-sm"
                        />
                        <button 
                            @click="getUserLocation"
                            class="text-xs font-bold px-2 py-1 rounded whitespace-nowrap transition-all"
                            :class="locationStatus === 'success' ? 'bg-green-50 text-green-600' : 'text-orange-600 hover:bg-orange-50'"
                        >
                            {{ locationStatus === 'loading' ? 'Localizando...' : (locationStatus === 'success' ? 'Ubicación Activa' : 'Usar GPS') }}
                        </button>
                    </div>
                    <div class="flex-[1.5] flex items-center px-2">
                         <Search class="w-5 h-5 text-gray-400 ml-2" />
                         <input 
                            v-model="tempQuery"
                            @keyup.enter="search('platillos')"
                            type="text" 
                            placeholder="¿Qué se te antoja? Tacos, Sushi..." 
                            class="w-full p-3 bg-transparent focus:outline-none text-gray-800 font-medium"
                        />
                    </div>
                    <button 
                        @click="search('platillos')"
                        class="bg-black text-white px-8 py-3 rounded-xl font-bold hover:bg-gray-800 transition-colors"
                    >
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="max-w-7xl mx-auto px-4 -mt-10 relative z-10 mb-12">
            <div class="bg-white rounded-3xl shadow-xl p-6 md:p-8 border border-gray-100">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">Categorías Populares</h3>
                <div class="flex gap-6 overflow-x-auto pt-4 pb-6 no-scrollbar min-w-full">
                    <div 
                        v-for="cat in popularCategories" 
                        :key="cat.name"
                        class="flex flex-col items-center gap-3 min-w-[100px] transition-all duration-300"
                    >
                        <!-- Burbuja de Categoría -->
                        <div 
                            @click="handleCategoryClick(cat.name)"
                            class="w-16 h-16 md:w-20 md:h-20 rounded-3xl flex items-center justify-center text-3xl cursor-pointer transition-all duration-300 relative group"
                            :class="activeCategory === cat.name ? 'bg-orange-600 text-white shadow-lg shadow-orange-200 -translate-y-1' : 'bg-gray-50 text-gray-400 grayscale md:hover:grayscale-0 md:hover:bg-white md:hover:shadow-md border border-gray-100'"
                        >
                            {{ cat.emoji }}
                            <div v-if="activeCategory === cat.name" class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-orange-600 rotate-45"></div>
                        </div>
                        <span class="text-xs font-black uppercase tracking-tighter transition-colors" :class="activeCategory === cat.name ? 'text-orange-600' : 'text-gray-400'">
                            {{ cat.name }}
                        </span>

                        <!-- Botones de Acción (Condicionales) -->
                        <div 
                            v-if="activeCategory === cat.name" 
                            class="flex gap-1 animate-in fade-in zoom-in duration-300"
                        >
                            <button 
                                @click="filterByCategory(cat.name, 'platillos')"
                                class="bg-black text-white p-2 rounded-xl shadow-lg md:hover:scale-110 active:scale-95 transition-all group"
                                title="Buscar Platillos"
                            >
                                <UtensilsCrossed class="w-4 h-4" />
                            </button>
                            <button 
                                @click="filterByCategory(cat.name, 'negocios')"
                                class="bg-orange-500 text-white p-2 rounded-xl shadow-lg md:hover:scale-110 active:scale-95 transition-all"
                                title="Buscar Negocios"
                            >
                                <Store class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Grid -->
        <div id="results-section" class="max-w-7xl mx-auto px-4 pb-20">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ searchQuery ? `Resultados para "${searchQuery}"` : (userCoords ? `Negocios en ${userState || 'tu zona'}` : 'Restaurantes Destacados') }}
                </h2>
                <!-- Reset Filter Button (Solo si estamos buscando algo específico) -->
                <button 
                    v-if="searchQuery || activeCategory"
                    @click="clearFilters" 
                    class="flex items-center gap-2 text-xs font-bold text-red-600 bg-red-50 px-4 py-2 rounded-full border border-red-100 hover:bg-red-100 transition-all font-black uppercase tracking-widest"
                >
                     <X class="w-3.5 h-3.5" />
                     <span>Quitar Filtros</span>
                </button>
            </div>

            <div v-if="loading || isLocating" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                 <!-- Premium Skeletons -->
                 <div v-for="i in 3" :key="i" class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 flex flex-col p-0 animate-pulse">
                    <div class="h-44 bg-gray-100 mb-5"></div>
                    <div class="px-5 pb-8">
                        <div class="h-6 bg-gray-100 w-3/4 mb-4 rounded-lg"></div>
                        <div class="flex gap-2 mb-4">
                            <div class="h-4 bg-gray-100 w-20 rounded"></div>
                            <div class="h-4 bg-gray-100 w-24 rounded"></div>
                        </div>
                        <div class="flex gap-2">
                            <div class="h-4 bg-gray-100 w-32 rounded"></div>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- CASO A: Hay Resultados -->
            <div v-else-if="results.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- CASO 1: Resultados de Negocios -->
                <template v-if="searchType === 'negocios'">
                    <div 
                        v-for="company in results" 
                        :key="company.id"
                        class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col border border-gray-100"
                    >
                        <!-- Clickable Area (Banner & Name) -->
                        <router-link :to="`/${company.slug}`" class="block flex-1 group">
                            <div class="h-44 relative overflow-hidden bg-gray-100 animate-pulse">
                                <img 
                                    :src="company.banner_url || 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=80&w=1000'" 
                                    @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                    class="w-full h-full object-cover transform group-hover:scale-105 transition-all duration-700 opacity-0" 
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-60"></div>
                                
                                <!-- Smart Status Badge -->
                                <div class="absolute top-4 left-4">
                                    <div 
                                        class="px-3 py-1.5 text-[10px] font-black rounded-full backdrop-blur-md flex items-center gap-2 shadow-lg"
                                        :class="getStatus(company).bg + ' ' + getStatus(company).color"
                                    >
                                        <div class="w-1.5 h-1.5 rounded-full animate-pulse" :class="getStatus(company).dot"></div>
                                        <span class="uppercase tracking-widest">{{ getStatus(company).text }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 relative">
                                <!-- Logo Floating -->
                                <div class="absolute -top-12 right-6 w-16 h-16 rounded-2xl bg-white p-1 shadow-xl border border-gray-50 overflow-hidden transform group-hover:-translate-y-1 transition-transform bg-gray-50 animate-pulse">
                                    <img 
                                        :src="company.logo_url || `https://ui-avatars.com/api/?name=${company.name}&background=random`" 
                                        @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                        class="w-full h-full object-cover rounded-xl opacity-0 transition-opacity duration-500"
                                    />
                                </div>

                                <h3 class="text-xl font-black text-gray-900 mb-1 group-hover:text-orange-600 transition-colors tracking-tight">{{ company.name }}</h3>
                                
                                <!-- Badge Rows -->
                                <div class="space-y-2 mb-4">
                                    <!-- Row 1: Category & Delivery -->
                                    <div class="flex items-center gap-2">
                                        <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-gray-200/50">
                                            {{ company.category || 'Restaurante' }}
                                        </span>
                                        <span class="flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest"
                                              :class="company.is_open ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100'"
                                        >
                                            <div class="w-1.5 h-1.5 rounded-full" :class="company.is_open ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></div>
                                            {{ company.is_open ? 'Entrega inmediata' : 'Programación' }}
                                        </span>
                                    </div>
                                    <!-- Row 2: Distance & ETA (Only if GPS active) -->
                                    <div v-if="company.distance !== undefined" class="flex items-center gap-2">
                                        <span class="flex items-center text-orange-600 bg-orange-50 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-orange-100">
                                            <MapPin class="w-3 h-3 mr-1" />
                                            {{ formatDistance(company.distance) }}
                                        </span>
                                        <span class="flex items-center text-blue-600 bg-blue-50 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                            <Clock class="w-3 h-3 mr-1" />
                                            {{ getEstimatedTime(company.distance) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 text-xs text-gray-400 font-medium mb-4">
                                    <MapPin class="w-3.5 h-3.5 shrink-0 text-orange-400" />
                                    <span class="line-clamp-1 text-gray-500 font-bold uppercase tracking-widest text-[9px]">{{ company.address || 'Ubicación próximamente' }}</span>
                                </div>
                            </div>
                        </router-link>

                        <!-- Interaction Footer (Botón descripción) -->
                        <div class="px-5 pb-5 pt-0 mt-auto">
                            <button 
                                @click="openDetails(company)"
                                class="w-full py-2.5 bg-gray-50 hover:bg-orange-50 text-gray-500 hover:text-orange-600 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all border border-gray-100 flex items-center justify-center gap-2 group/btn"
                            >
                                Ver Información
                                <ChevronRight class="w-3.5 h-3.5 transition-transform group-hover/btn:translate-x-1" />
                            </button>
                        </div>
                    </div>
                </template>

                <!-- CASO 2: Resultados de Platillos -->
                <template v-else>
                    <div 
                        v-for="product in results" 
                        :key="`prod-${product.id}`"
                        @click="$router.push(`/${product.company_slug}?prod_id=${product.id}`)"
                        class="bg-white rounded-[2.5rem] p-4 border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 cursor-pointer group flex gap-4 items-center overflow-hidden relative"
                    >
                        <div class="absolute top-0 right-0 w-32 h-32 bg-orange-50 rounded-bl-full opacity-50 -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                        
                        <div class="w-24 h-24 md:w-28 md:h-28 rounded-[2rem] overflow-hidden shadow-xl flex-shrink-0 relative z-10 transition-transform group-hover:scale-110 duration-500 bg-gray-100 animate-pulse">
                            <img 
                                :src="product.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=500'" 
                                @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                class="w-full h-full object-cover transition-all duration-700 opacity-0" 
                            />
                        </div>

                        <div class="flex-1 relative z-10 flex flex-col h-full py-1">
                            <!-- Badge Rows -->
                            <div class="space-y-1.5 mb-2">
                                <!-- Row 1: Restaurant -->
                                <div class="flex items-center gap-1.5">
                                    <div class="w-4 h-4 bg-orange-100 rounded-full flex items-center justify-center p-0.5">
                                        <Store class="w-2.5 h-2.5 text-orange-600" />
                                    </div>
                                    <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest leading-none">{{ product.company_name }}</span>
                                </div>
                                <!-- Row 2: Distance / Time -->
                                <div v-if="product.distance" class="flex items-center gap-2">
                                    <span class="text-[9px] font-black text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded border border-orange-100 flex items-center gap-1 uppercase tracking-widest">
                                        <MapPin class="w-2.5 h-2.5" />
                                        {{ formatDistance(product.distance) }}
                                    </span>
                                    <span class="text-[9px] font-black text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1 uppercase tracking-widest">
                                        <Clock class="w-2.5 h-2.5" />
                                        {{ getEstimatedTime(product.distance) }}
                                    </span>
                                </div>
                            </div>
                            
                            <h3 class="font-black text-gray-900 text-lg leading-tight mb-1 group-hover:text-orange-600 transition-colors">
                                {{ product.name }}
                            </h3>
                            <p class="text-[10px] text-gray-500 line-clamp-2 mb-3 leading-tight font-medium">{{ product.description }}</p>
                            
                            <div class="mt-auto flex items-center justify-between">
                                <span class="font-black text-xl text-gray-900">${{ parseFloat(product.price).toFixed(2) }}</span>
                                <div class="bg-black text-white p-2.5 rounded-2xl shadow-lg group-hover:bg-orange-600 transition-colors scale-90 group-hover:scale-100 duration-300">
                                    <ShoppingBag class="w-4 h-4" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- CASO B: Sin resultados en la búsqueda activa -->
            <div v-else-if="isSearching" class="bg-white rounded-[3rem] border border-gray-100 p-12 md:p-20 text-center shadow-xl shadow-gray-100/50">
                <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-8 relative">
                    <!-- Icono dinámico -->
                    <UtensilsCrossed v-if="activeCategory || searchQuery" class="w-10 h-10 text-orange-200" />
                    <MapPin v-else class="w-10 h-10 text-orange-200" />
                    <div class="absolute -top-1 -right-1 w-8 h-8 bg-white rounded-full shadow-md flex items-center justify-center border border-gray-100 transition-transform hover:scale-110">
                         <Search class="w-4 h-4 text-orange-400" />
                    </div>
                </div>

                <!-- Títulos Dinámicos -->
                <h3 v-if="activeCategory" class="text-3xl font-black text-gray-900 mb-4 tracking-tighter">
                    ¡Huy, no hay {{ activeCategory }}!
                </h3>
                <h3 v-else-if="searchQuery" class="text-3xl font-black text-gray-900 mb-4 tracking-tighter px-4">
                    ¡No encontramos "{{ searchQuery }}"!
                </h3>
                <h3 v-else class="text-3xl font-black text-gray-900 mb-4 tracking-tighter">
                    ¡Aún no llegamos aquí!
                </h3>

                <!-- Descripciones Dinámicas -->
                <p class="text-gray-500 max-w-md mx-auto mb-10 leading-relaxed font-bold uppercase tracking-widest text-[10px]">
                    <template v-if="activeCategory">
                        Lo sentimos, actualmente no hay negocios de <span class="text-orange-600">{{ activeCategory }}</span> 
                        {{ userState ? `en ${userState}` : 'en esta zona' }}. 
                    </template>
                    <template v-else-if="searchQuery">
                        No hay resultados para <span class="text-orange-600">"{{ searchQuery }}"</span> 
                        {{ userState ? `en ${userState}` : '' }}. Prueba con otros términos.
                    </template>
                    <template v-else>
                        Lo sentimos, actualmente {{ userState ? `no tenemos cobertura en ${userState}` : 'no encontramos resultados para tu búsqueda' }}.
                    </template>
                    <br/>
                    <span class="opacity-50 font-medium normal-case tracking-normal">Prueba con otra búsqueda o explora el catálogo completo.</span>
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <button 
                        @click="resetToGlobal"
                        class="w-full sm:w-auto px-10 py-4 bg-black text-white rounded-2xl font-black uppercase tracking-widest shadow-2xl hover:bg-orange-600 transition-all hover:-translate-y-1"
                    >
                        Ver Catálogo Global
                    </button>
                    <button 
                        @click="searchQuery = ''; tempQuery = ''; results = []; isSearching = false; loadFeatured()"
                        class="w-full sm:w-auto px-10 py-4 bg-gray-50 text-gray-400 rounded-2xl font-black uppercase tracking-widest hover:bg-gray-100 transition-all"
                    >
                        Nueva Búsqueda
                    </button>
                </div>
            </div>

            <!-- CASO C: Estado inicial (Mostrar Destacados Globales) -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div 
                    v-for="company in featuredCompanies" 
                    :key="company.id"
                    class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col border border-gray-100"
                >
                    <router-link :to="`/${company.slug}`" class="block flex-1 group">
                        <div class="h-44 relative overflow-hidden bg-gray-100 animate-pulse">
                            <img 
                                :src="company.banner_url || 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=80&w=1000'" 
                                @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                class="w-full h-full object-cover transform group-hover:scale-105 transition-all duration-700 opacity-0" 
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-60"></div>
                            
                            <div class="absolute top-4 left-4">
                                <div 
                                    class="px-3 py-1.5 text-[10px] font-black rounded-full backdrop-blur-md flex items-center gap-2 shadow-lg"
                                    :class="getStatus(company).bg + ' ' + getStatus(company).color"
                                >
                                    <div class="w-1.5 h-1.5 rounded-full animate-pulse" :class="getStatus(company).dot"></div>
                                    <span class="uppercase tracking-widest">{{ getStatus(company).text }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 relative">
                            <div class="absolute -top-12 right-6 w-16 h-16 rounded-2xl bg-white p-1 shadow-xl border border-gray-50 overflow-hidden transform group-hover:-translate-y-1 transition-transform bg-gray-50 animate-pulse">
                                <img 
                                    :src="company.logo_url || `https://ui-avatars.com/api/?name=${company.name}&background=random`" 
                                    @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                    class="w-full h-full object-cover rounded-xl opacity-0 transition-opacity duration-500"
                                />
                            </div>
                            <h3 class="text-xl font-black text-gray-900 mb-1 group-hover:text-orange-600 transition-colors tracking-tight">{{ company.name }}</h3>
                            <!-- Badge Rows -->
                            <div class="space-y-2 mb-4">
                                <!-- Row 1: Category & Delivery -->
                                <div class="flex items-center gap-2">
                                    <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-gray-200/50">
                                        {{ company.category || 'Restaurante' }}
                                    </span>
                                    <span class="flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest"
                                          :class="company.is_open ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100'"
                                    >
                                        <div class="w-1.5 h-1.5 rounded-full" :class="company.is_open ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></div>
                                        {{ company.is_open ? 'Entrega inmediata' : 'Programación' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </router-link>
                    <div class="px-5 pb-5 pt-0 mt-auto">
                        <button @click="openDetails(company)" class="w-full py-2.5 bg-gray-50 hover:bg-orange-50 text-gray-500 hover:text-orange-600 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all border border-gray-100 flex items-center justify-center gap-2 group/btn">
                            Ver Información
                            <ChevronRight class="w-3.5 h-3.5 transition-transform group-hover/btn:translate-x-1" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal (Mobile-First Bottom Sheet style) -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal && selectedCompany" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
                <!-- Backdrop -->
                <div @click="showModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

                <!-- Content Panel -->
                <div class="relative bg-white w-full max-w-lg rounded-t-[32px] sm:rounded-[32px] shadow-2xl animate-slideUp max-h-[85vh] flex flex-col overflow-hidden">
                    <!-- Close Button -->
                    <button @click="showModal = false" class="absolute top-4 right-4 z-20 p-2 bg-black/20 hover:bg-black/40 text-white rounded-full backdrop-blur-md transition-all">
                        <X class="w-5 h-5" />
                    </button>

                    <!-- STATIC HEADER -->
                    <div class="shrink-0 border-b border-gray-50">
                        <!-- Modal Banner -->
                        <div class="h-40 relative bg-gray-100 animate-pulse">
                            <img 
                                :src="selectedCompany.banner_url || 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=80&w=1000'" 
                                @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                class="w-full h-full object-cover opacity-0 transition-opacity duration-500" 
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-white via-white/20 to-transparent"></div>
                            
                            <!-- Modal Logo -->
                            <div class="absolute -bottom-6 left-8 w-20 h-20 rounded-2xl bg-white p-1 shadow-2xl border border-gray-100 overflow-hidden bg-gray-50 animate-pulse">
                                <img 
                                    :src="selectedCompany.logo_url || `https://ui-avatars.com/api/?name=${selectedCompany.name}&background=random`" 
                                    @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                                    class="w-full h-full object-cover rounded-xl opacity-0 transition-opacity duration-500"
                                />
                            </div>
                        </div>

                        <!-- Modal Name/Address -->
                        <div class="pt-10 px-8 pb-4">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <h2 class="text-2xl font-black text-gray-900 leading-none">{{ selectedCompany.name }}</h2>
                                <div class="px-2 py-0.5 bg-black text-[9px] font-black text-white rounded uppercase tracking-widest shadow-sm">
                                    {{ selectedCompany.category || 'Restaurante' }}
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 text-xs text-orange-600 font-bold uppercase tracking-widest px-1">
                                <MapPin class="w-3.5 h-3.5" />
                                {{ selectedCompany.address || 'Ubicación Próximamente' }}
                            </div>
                        </div>
                    </div>

                    <!-- SCROLLABLE CONTENT -->
                    <div class="flex-1 overflow-y-auto p-8 pt-6 no-scrollbar">
                        <!-- Description Section -->
                        <div class="mb-8">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Sobre el Restaurante</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                {{ selectedCompany.description || 'Este restaurante aún no ha añadido una descripción.' }}
                            </p>
                        </div>

                        <!-- Schedule Section -->
                        <div class="bg-gray-50 rounded-2xl p-6">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <Clock class="w-3.5 h-3.5 text-gray-500" /> Horario Semanal
                            </h4>
                            <div class="grid grid-cols-1 gap-2.5">
                                <div 
                                    v-for="day in scheduleDays" 
                                    :key="day.key"
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span class="font-bold text-gray-500 uppercase tracking-tighter w-16">{{ day.name }}</span>
                                    <div class="flex-1 border-b border-dashed border-gray-200 mx-3 mb-1"></div>
                                    <div v-if="selectedCompany.schedule_config?.[day.key]?.closed" class="text-red-400 font-bold uppercase italic text-[9px]">Cerrado</div>
                                    <div v-else class="font-black text-gray-700">
                                        {{ selectedCompany.schedule_config?.[day.key]?.open }} - {{ selectedCompany.schedule_config?.[day.key]?.close }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Call to Action -->
                        <router-link 
                            :to="`/${selectedCompany.slug}`"
                            class="mt-8 w-full bg-black text-white py-4 rounded-2xl font-black text-center uppercase tracking-widest hover:bg-orange-600 transition-all shadow-xl shadow-gray-200 flex items-center justify-center gap-3"
                        >
                            Ir al Menú
                            <ChevronRight class="w-5 h-5" />
                        </router-link>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.animate-slideUp {
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
