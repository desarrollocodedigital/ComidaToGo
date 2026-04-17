<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header Estándar -->
        <header class="bg-white shadow-sm border-b border-gray-100 mb-8">
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-800">Reportes y Analíticas</h1>
                    <p class="text-sm text-gray-500">Inteligencia de negocio en tiempo real</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Filtros de periodo -->
                    <div class="flex bg-gray-100 rounded-xl p-1 gap-1">
                        <button v-for="p in periods" :key="p.value" @click="period = p.value"
                            :class="['px-4 py-2 rounded-lg text-sm font-bold transition-all', period === p.value ? 'bg-white shadow text-gray-800' : 'text-gray-400 hover:text-gray-600']">
                            {{ p.label }}
                        </button>
                    </div>
                    <button @click="loadAll" :disabled="loading" class="p-2.5 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition-all disabled:opacity-50">
                        <RefreshCw :class="['w-5 h-5', loading ? 'animate-spin' : '']" />
                    </button>
                    <router-link to="/admin/dashboard" class="flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl shadow-sm hover:bg-black transition-all font-bold w-fit">
                        <ArrowLeft class="w-5 h-5" />
                        Volver al Panel
                    </router-link>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-6 pb-12 space-y-8">
            <!-- KPI Cards -->
            <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="i in 4" :key="i" class="bg-white rounded-2xl h-28 animate-pulse"></div>
            </div>
            <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-emerald-500 overflow-hidden relative">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Ventas Brutas</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-3xl font-black text-gray-800">${{ fmt(salesData.gross_sales) }}</p>
                        <!-- Indicador de tendencia -->
                        <div v-if="salesData.sales_trend !== 0" 
                             :class="['flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full', 
                                      salesData.sales_trend > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600']">
                            <component :is="salesData.sales_trend > 0 ? TrendingUp : TrendingDown" class="w-3 h-3 mr-0.5" />
                            {{ Math.abs(salesData.sales_trend) }}%
                        </div>
                    </div>
                    <span class="text-xs text-gray-400 mt-1 block">
                        {{ salesData.total_orders }} pedidos 
                        <span v-if="salesData.sales_trend !== 0" class="opacity-60">
                            • {{ salesData.sales_trend > 0 ? 'Más' : 'Menos' }} que {{ trendLabel }}
                        </span>
                    </span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-red-400">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Gastos</p>
                    <p class="text-3xl font-black text-gray-800">${{ fmt(salesData.total_expenses) }}</p>
                    <span class="text-xs text-gray-400 mt-1 block">Salidas de efectivo</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-blue-500 overflow-hidden relative">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Utilidad Neta</p>
                    <div class="flex items-baseline gap-2">
                        <p :class="['text-3xl font-black', salesData.net_profit >= 0 ? 'text-blue-700' : 'text-red-600']">${{ fmt(salesData.net_profit) }}</p>
                        <!-- Indicador de tendencia basado en el cambio de utilidad -->
                        <div v-if="salesData.profit_trend !== 0" 
                             :class="['flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full', 
                                      salesData.profit_trend > 0 ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600']">
                            {{ salesData.profit_trend > 0 ? '↑' : '↓' }}
                        </div>
                    </div>
                    <span class="text-xs text-gray-400 mt-1 block">Ventas menos gastos</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-orange-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Eficiencia Cocina</p>
                    <p class="text-3xl font-black text-gray-800">{{ kitchenEfficiency }} min</p>
                    <span class="text-xs text-gray-400 mt-1 block">Prep. promedio</span>
                </div>
            </div>

            <!-- Fila 2: Horas Pico + Distribución por Tipo -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Mapa de Horas Pico -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center">
                            <Clock class="w-5 h-5 text-orange-500" />
                        </div>
                        <div>
                            <h3 class="font-black text-gray-800">Horas Pico</h3>
                            <p class="text-xs text-gray-400">Volumen de pedidos por hora del día</p>
                        </div>
                    </div>
                    
                    <div v-if="loadingExtra" class="h-32 bg-gray-50 rounded-xl animate-pulse"></div>
                    
                    <div v-else class="flex justify-between gap-1 h-32 w-full pt-4">
                        <div v-for="(count, hour) in peakHours" :key="hour" 
                             class="flex-1 h-full flex flex-col justify-end items-center group relative"
                             :title="`${hour}:00 — ${count} pedidos`">
                            
                            <!-- Barra -->
                            <div class="w-full rounded-t-sm transition-all duration-500"
                                 :class="[
                                     count > 0 ? (count === maxHourOrders ? 'bg-orange-500' : 'bg-orange-300') : 'bg-gray-100',
                                     'hover:bg-orange-400'
                                 ]"
                                 :style="{ height: getHourHeight(count) + '%' }">
                            </div>

                            <!-- Etiqueta de hora (solo algunas para no saturar) -->
                            <span v-if="hour % 4 === 0" class="absolute -bottom-5 left-0 right-0 text-center text-[9px] font-bold text-gray-400">
                                {{ hour }}h
                            </span>
                        </div>
                    </div>
                    <div class="mt-6"></div> <!-- Espacio para las etiquetas de abajo -->
                </div>

                <!-- Distribución por Tipo de Pedido -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                            <PieChart class="w-5 h-5 text-purple-500" />
                        </div>
                        <div>
                            <h3 class="font-black text-gray-800">Mix de Canales</h3>
                            <p class="text-xs text-gray-400">Distribución por tipo de servicio</p>
                        </div>
                    </div>
                    <div v-if="loadingExtra" class="space-y-3">
                        <div v-for="i in 3" :key="i" class="h-10 bg-gray-50 rounded-xl animate-pulse"></div>
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="type in orderTypes" :key="type.order_type" class="group">
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-gray-500 flex items-center gap-2">
                                    <span>{{ orderTypeInfo[type.order_type]?.icon || '❓' }}</span>
                                    {{ orderTypeInfo[type.order_type]?.label || type.order_type }}
                                </span>
                                <span class="font-bold text-gray-800">{{ getTypePercent(type.total) }}%</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full transition-all duration-1000"
                                     :class="orderTypeInfo[type.order_type]?.color || 'bg-gray-400'"
                                     :style="{ width: getTypePercent(type.total) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila 3: Top Platillos + Lealtad + Gastos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Lealtad de Clientes -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                            <Star class="w-5 h-5 text-indigo-500" />
                        </div>
                        <div>
                            <h3 class="font-black text-gray-800">Lealtad de Clientes</h3>
                            <p class="text-xs text-gray-400">Nuevos vs Recurrentes</p>
                        </div>
                    </div>

                    <div v-if="loadingExtra" class="flex flex-col gap-4">
                        <div class="h-20 bg-gray-50 rounded-xl animate-pulse"></div>
                        <div class="h-20 bg-gray-50 rounded-xl animate-pulse"></div>
                    </div>
                    <div v-else class="space-y-6">
                        <!-- Comparativa Visual -->
                        <div class="flex h-12 rounded-2xl overflow-hidden border border-gray-50">
                            <div class="bg-indigo-500 h-full flex items-center justify-center text-white text-[10px] font-bold transition-all duration-1000"
                                 :style="{ width: getRetentionPercent('recurring') + '%' }"
                                 v-if="retentionData.recurring > 0">
                                {{ getRetentionPercent('recurring') }}%
                            </div>
                            <div class="bg-indigo-200 h-full flex items-center justify-center text-indigo-700 text-[10px] font-bold transition-all duration-1000"
                                 :style="{ width: getRetentionPercent('new') + '%' }">
                                {{ getRetentionPercent('new') }}%
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Recurrentes</p>
                                <p class="text-xl font-black text-indigo-600">{{ retentionData.recurring }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Nuevos</p>
                                <p class="text-xl font-black text-gray-700">{{ retentionData.new }}</p>
                            </div>
                        </div>
                        
                        <p class="text-center text-xs text-gray-400 font-medium">
                            <span v-if="getRetentionPercent('recurring') > 30" class="text-emerald-500 font-bold">¡Alta lealtad!</span>
                            {{ getRetentionPercent('recurring') }}% de tus clientes han regresado.
                        </p>
                    </div>
                </div>

                <!-- Top Platillos -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-yellow-50 rounded-xl flex items-center justify-center">
                            <Star class="w-5 h-5 text-yellow-500" />
                        </div>
                        <div>
                            <h3 class="font-black text-gray-800">Platillos Estrella</h3>
                            <p class="text-xs text-gray-400">Top 5 histórico por unidades vendidas</p>
                        </div>
                    </div>
                    <div v-if="topProducts.length === 0" class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                        Sin ventas registradas aún
                    </div>
                    <ul v-else class="space-y-3 flex-1">
                        <li v-for="(product, index) in topProducts" :key="product.id"
                            class="flex items-center gap-4 p-3 bg-gray-50 hover:bg-orange-50 rounded-xl transition-colors">
                            <div :class="['w-9 h-9 rounded-full flex items-center justify-center font-black text-sm text-white flex-shrink-0 shadow-sm',
                                index === 0 ? 'bg-yellow-400' : index === 1 ? 'bg-gray-300' : index === 2 ? 'bg-amber-600' : 'bg-gray-200 text-gray-500']">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-800 truncate">{{ product.name }}</p>
                                <p class="text-xs text-gray-400">{{ product.total_sold }} unidades</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-black text-gray-700 block text-sm">${{ fmt(product.revenue) }}</span>
                                <span class="text-xs text-emerald-500 font-bold">Ingreso bruto</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Desglose de Gastos -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                            <Wallet class="w-5 h-5 text-red-500" />
                        </div>
                        <div>
                            <h3 class="font-black text-gray-800">Desglose de Gastos</h3>
                            <p class="text-xs text-gray-400">Por categoría de salida de efectivo</p>
                        </div>
                    </div>
                    <div v-if="loadingExtra" class="space-y-3">
                        <div v-for="i in 4" :key="i" class="h-12 bg-gray-50 rounded-xl animate-pulse"></div>
                    </div>
                    <div v-else-if="expenseBreakdown.length === 0" class="flex-1 flex items-center justify-center flex-col gap-2 text-gray-400 text-sm">
                        <p>Sin gastos registrados en este periodo.</p>
                    </div>
                    <ul v-else class="space-y-3 flex-1">
                        <li v-for="item in expenseBreakdown" :key="item.category" class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-700 text-sm">{{ item.category }}</span>
                                <div class="text-right">
                                    <span class="font-black text-gray-800 text-sm">${{ fmt(item.total) }}</span>
                                    <span class="text-xs text-gray-400 ml-2">{{ getExpensePercent(item.total) }}%</span>
                                </div>
                            </div>
                            <div class="h-2.5 bg-red-50 rounded-full overflow-hidden">
                                <div class="h-2.5 bg-red-400 rounded-full transition-all duration-700"
                                    :style="{ width: getExpensePercent(item.total) + '%' }"></div>
                            </div>
                            <p class="text-xs text-gray-400">{{ item.count }} registros</p>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Gráfico Financiero General -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <BarChart2 class="w-5 h-5 text-blue-500" />
                    </div>
                    <div>
                        <h3 class="font-black text-gray-800">Desempeño Financiero</h3>
                        <p class="text-xs text-gray-400">Comparativa de ingresos brutos vs gastos vs utilidad</p>
                    </div>
                </div>
                <div class="flex items-end justify-center gap-12 h-40">
                    <div v-for="bar in financialBars" :key="bar.label" class="flex flex-col items-center gap-2">
                        <span :class="['text-sm font-black', bar.textColor]">${{ fmt(bar.value) }}</span>
                        <div :class="['w-20 rounded-t-xl transition-all duration-1000', bar.color]"
                            :style="{ height: maxBarVal > 0 ? Math.max((bar.value / maxBarVal) * 140, bar.value > 0 ? 10 : 2) + 'px' : '2px' }">
                        </div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ bar.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import { ArrowLeft, Clock, PieChart, Star, Wallet, BarChart2, RefreshCw, TrendingUp, TrendingDown } from 'lucide-vue-next'

const auth = useAuthStore()
const companyId = computed(() => auth.user?.company_id || 1)

const loading = ref(true)
const loadingExtra = ref(true)
const period = ref('month')

const periods = [
    { value: 'day', label: 'Hoy' },
    { value: 'week', label: 'Semana' },
    { value: 'month', label: 'Mes' },
    { value: 'year', label: 'Año' }
]

const salesData = ref({ 
    gross_sales: 0, 
    total_expenses: 0, 
    net_profit: 0, 
    total_orders: 0, 
    average_ticket: 0,
    sales_trend: 0,
    profit_trend: 0,
    prev_sales: 0
})
const topProducts = ref([])
const peakHours = ref(Array(24).fill(0))
const orderTypes = ref([])
const expenseBreakdown = ref([])
const kitchenEfficiency = ref(0)
const retentionData = ref({ new: 0, recurring: 0 })

const orderTypeInfo = {
    DINE_IN:  { label: 'Comedor', icon: '🍽️', color: 'bg-purple-500' },
    TAKEAWAY: { label: 'Para Llevar', icon: '🥡', color: 'bg-blue-500' },
    DELIVERY: { label: 'Domicilio', icon: '🛵', color: 'bg-orange-500' },
    PICKUP:   { label: 'Pasar a recoger', icon: '🛍️', color: 'bg-indigo-500' },
}

const fmt = (val) => Number(val || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const trendLabel = computed(() => {
    const labels = {
        day: 'ayer',
        week: 'la semana pasada',
        month: 'el mes pasado',
        year: 'el año pasado'
    }
    return labels[period.value] || 'el periodo anterior'
})

const maxHourOrders = computed(() => {
    if (!peakHours.value.length) return 0
    return Math.max(...peakHours.value)
})

const getHourHeight = (count) => {
    if (maxHourOrders.value === 0) return 5 // Altura base mínima
    const pct = (count / maxHourOrders.value) * 100
    return Math.max(pct, count > 0 ? 10 : 5)
}

const totalOrders = computed(() => orderTypes.value.reduce((sum, t) => sum + Number(t.total), 0))
const getTypePercent = (val) => totalOrders.value > 0 ? Math.round((val / totalOrders.value) * 100) : 0

const totalExpenses = computed(() => expenseBreakdown.value.reduce((sum, e) => sum + Number(e.total), 0))
const getExpensePercent = (val) => totalExpenses.value > 0 ? Math.round((val / totalExpenses.value) * 100) : 0

const getRetentionPercent = (type) => {
    const total = retentionData.value.new + retentionData.value.recurring
    if (total === 0) return type === 'new' ? 100 : 0
    return Math.round((retentionData.value[type] / total) * 100)
}

const financialBars = computed(() => [
    { label: 'Ingreso', value: salesData.value.gross_sales, color: 'bg-emerald-400', textColor: 'text-emerald-600' },
    { label: 'Utilidad', value: Math.max(salesData.value.net_profit, 0), color: 'bg-blue-500', textColor: 'text-blue-600' },
    { label: 'Gastos', value: salesData.value.total_expenses, color: 'bg-red-400', textColor: 'text-red-500' },
])
const maxBarVal = computed(() => Math.max(...financialBars.value.map(b => b.value), 1))

const loadAll = async () => {
    loading.value = true
    loadingExtra.value = true
    try {
        const [salesRes, productsRes, peakRes, typesRes, expensesRes, kitchenRes, retentionRes] = await Promise.all([
            axios.get(`/api.php/analytics/sales?company_id=${companyId.value}&period=${period.value}`),
            axios.get(`/api.php/analytics/top-products?company_id=${companyId.value}`),
            axios.get(`/api.php/analytics/peak-hours?company_id=${companyId.value}&period=${period.value}`),
            axios.get(`/api.php/analytics/order-types?company_id=${companyId.value}&period=${period.value}`),
            axios.get(`/api.php/analytics/expense-breakdown?company_id=${companyId.value}&period=${period.value}`),
            axios.get(`/api.php/analytics/kitchen-efficiency?company_id=${companyId.value}&period=${period.value}`),
            axios.get(`/api.php/analytics/customer-retention?company_id=${companyId.value}&period=${period.value}`),
        ])
        salesData.value = salesRes.data.metrics
        topProducts.value = productsRes.data.top_products ?? []
        kitchenEfficiency.value = kitchenRes.data.avg_minutes ?? 0
        retentionData.value = retentionRes.data ?? { new: 0, recurring: 0 }
        
        // Blindaje para peakHours: Asegurar que siempre sea un array de 24 números
        let hoursRaw = peakRes.data.hours || []
        if (typeof hoursRaw === 'object' && !Array.isArray(hoursRaw)) {
            hoursRaw = Object.values(hoursRaw) // Convertir objeto a array si es necesario
        }
        peakHours.value = Array.from({ length: 24 }, (_, i) => Number(hoursRaw[i] || 0))

        orderTypes.value = typesRes.data.breakdown ?? []
        expenseBreakdown.value = expensesRes.data.breakdown ?? []
    } catch (e) {
        console.error('Error cargando analíticas:', e)
    } finally {
        loading.value = false
        loadingExtra.value = false
    }
}

watch(companyId, () => {
    loadAll()
})

watch(period, loadAll)
onMounted(loadAll)
</script>
