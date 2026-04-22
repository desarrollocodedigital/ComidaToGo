<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import { LayoutDashboard, ChefHat, CreditCard, BarChart3, Settings, Users, UtensilsCrossed, Utensils, ShoppingBag, LogOut, Tag } from 'lucide-vue-next'

const auth = useAuthStore()
const companyName = ref('Mi Restaurante')
const stats = ref({ pending: 0, inKitchen: 0, ready: 0, todaySales: 0 })
const loading = ref(true)

const loadData = async () => {
    const companyId = auth.user?.company_id || 1
    try {
        const [tenantRes, ordersRes, salesRes] = await Promise.all([
            axios.get(`/api.php/tenant/${companyId}`),
            axios.get(`/api.php/orders?company_id=${companyId}`),
            axios.get(`/api.php/analytics/sales?company_id=${companyId}&period=day`).catch(() => ({ data: { metrics: { gross_sales: 0 } } }))
        ])
        companyName.value = tenantRes.data.name || 'Mi Restaurante'
        const orders = ordersRes.data || []
        stats.value.pending = orders.filter(o => o.status === 'PENDING').length
        stats.value.inKitchen = orders.filter(o => o.status === 'ACCEPTED').length
        stats.value.ready = orders.filter(o => o.status === 'READY').length
        stats.value.todaySales = salesRes.data?.metrics?.gross_sales || 0
    } catch (e) { console.error(e) }
    finally { loading.value = false }
}

const logout = () => {
    auth.logout()
    window.location.href = import.meta.env.BASE_URL + 'login'
}

onMounted(loadData)
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-gray-800">{{ companyName }}</h1>
                    <p class="text-sm text-gray-500">Panel de Administración</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">{{ auth.user?.name }}</span>
                    <button @click="logout" class="p-2 text-gray-400 hover:text-red-500 rounded-full hover:bg-red-50">
                        <LogOut class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-6 py-8">
            <!-- KPIs -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Pedidos Pendientes</p>
                    <p class="text-3xl font-black text-red-600">{{ stats.pending }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">En Cocina</p>
                    <p class="text-3xl font-black text-yellow-600">{{ stats.inKitchen }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Listos</p>
                    <p class="text-3xl font-black text-green-600">{{ stats.ready }}</p>
                </div>
                <div v-if="['OWNER', 'CASHIER'].includes(auth.user?.role)" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Ventas Hoy</p>
                    <p class="text-3xl font-black text-blue-600">${{ Number(stats.todaySales).toFixed(0) }}</p>
                </div>
            </div>

            <!-- Quick Access Grid -->
            <h2 class="text-lg font-bold text-gray-700 mb-4">Acceso Rápido</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <router-link v-if="['OWNER', 'CASHIER', 'WAITER'].includes(auth.user?.role)" to="/pos" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition-all group text-center">
                    <ShoppingBag class="w-10 h-10 text-orange-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Punto de Venta</h3>
                    <p class="text-xs text-gray-400 mt-1">Cobrar pedidos</p>
                </router-link>
                <router-link v-if="['OWNER', 'CASHIER'].includes(auth.user?.role)" to="/caja" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-green-200 transition-all group text-center">
                    <CreditCard class="w-10 h-10 text-green-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Control de Caja</h3>
                    <p class="text-xs text-gray-400 mt-1">Abrir/cerrar turno</p>
                </router-link>
                <router-link v-if="['OWNER', 'KITCHEN', 'WAITER'].includes(auth.user?.role)" to="/cocina" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-yellow-200 transition-all group text-center">
                    <ChefHat class="w-10 h-10 text-yellow-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Cocina</h3>
                    <p class="text-xs text-gray-400 mt-1">Ver pantalla cocina</p>
                </router-link>
                <router-link v-if="auth.user?.role === 'OWNER'" to="/demo/analytics" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all group text-center">
                    <BarChart3 class="w-10 h-10 text-blue-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Analytics</h3>
                    <p class="text-xs text-gray-400 mt-1">Reportes y datos</p>
                </router-link>
                <router-link v-if="['OWNER', 'CASHIER'].includes(auth.user?.role)" to="/admin/expense-categories" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-red-200 transition-all group text-center">
                    <Tag class="w-10 h-10 text-red-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Mis Gastos</h3>
                    <p class="text-xs text-gray-400 mt-1">Configura conceptos de salida</p>
                </router-link>
                <router-link v-if="['OWNER', 'KITCHEN'].includes(auth.user?.role)" to="/admin/menu" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition-all group text-center">
                    <UtensilsCrossed class="w-10 h-10 text-red-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Mi Menú</h3>
                    <p class="text-xs text-gray-400 mt-1">Platillos y extras</p>
                </router-link>
                <router-link v-if="auth.user?.role === 'OWNER'" to="/admin/team" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-purple-200 transition-all group text-center">
                    <Users class="w-10 h-10 text-purple-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Mi Equipo</h3>
                    <p class="text-xs text-gray-400 mt-1">Empleados y roles</p>
                </router-link>
                <router-link v-if="auth.user?.role === 'OWNER'" to="/admin/tables" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition-all group text-center">
                    <Utensils class="w-10 h-10 text-orange-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Mis Mesas</h3>
                    <p class="text-xs text-gray-400 mt-1">Distribución y capacidad</p>
                </router-link>
                <router-link v-if="auth.user?.role === 'OWNER'" to="/admin/settings" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-300 transition-all group text-center">
                    <Settings class="w-10 h-10 text-gray-500 mx-auto mb-3 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-gray-800">Configuración</h3>
                    <p class="text-xs text-gray-400 mt-1">Horarios, timers</p>
                </router-link>
            </div>
        </main>
    </div>
</template>
