<template>
  <div class="analytics-dashboard min-h-screen bg-gray-100 p-6 font-sans">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Panel de Analíticas y Finanzas</h1>
        <router-link to="/demo" class="px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition-colors">
          Volver al Menú
        </router-link>
      </div>

      <!-- Filtro de tiempo -->
      <div class="mb-8 flex space-x-2">
         <button @click="period = 'day'" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-colors', period === 'day' ? 'bg-slate-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border']">Hoy</button>
         <button @click="period = 'week'" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-colors', period === 'week' ? 'bg-slate-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border']">Esta Semana</button>
         <button @click="period = 'month'" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-colors', period === 'month' ? 'bg-slate-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border']">Este Mes</button>
      </div>

      <div v-if="loading" class="text-center py-20">
         <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-slate-800 mx-auto"></div>
      </div>

      <div v-else>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Tarjetas de KPI (Real Data) -->
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-emerald-500">
              <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-1">Ventas Brutas</h3>
              <p class="text-3xl font-black text-gray-800">${{ Number(salesData.gross_sales).toFixed(2) }}</p>
              <span class="text-sm font-medium text-gray-400 mt-2 block">{{ salesData.total_orders }} pedidos</span>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
              <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-1">Gastos (Salidas)</h3>
              <p class="text-3xl font-black text-gray-800">${{ Number(salesData.total_expenses).toFixed(2) }}</p>
              <span class="text-sm font-medium text-gray-400 mt-2 block">Cuentas y proveedores</span>
            </div>

            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
              <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-1">Ingreso Neto (Real)</h3>
              <p class="text-3xl font-black text-blue-700">${{ Number(salesData.net_profit).toFixed(2) }}</p>
              <span class="text-sm font-medium text-gray-400 mt-2 block">Libre de gastos operativos</span>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
              <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-1">Ticket Promedio</h3>
              <p class="text-3xl font-black text-gray-800">${{ Number(salesData.average_ticket).toFixed(2) }}</p>
              <span class="text-sm font-medium text-gray-400 mt-2 block">Gasto promedio por cliente</span>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Gráfica de Ventas (Placeholder / Opcional Chart.js) -->
            <div class="bg-white rounded-xl shadow p-6 border border-gray-100 flex flex-col justify-between">
              <div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Desempeño Financiero</h3>
                <p class="text-gray-500 text-sm mb-6">Comparativa de ingresos brutos vs salidas de efectivo.</p>
              </div>
              
              <div class="h-64 flex flex-col items-center justify-center p-6 bg-slate-50 rounded-xl border-2 border-dashed border-gray-200">
                  <div class="w-full flex items-end justify-center space-x-12 h-40">
                      <div class="flex flex-col items-center w-24">
                          <span class="text-emerald-600 font-bold mb-2">${{ Number(salesData.gross_sales).toFixed(0) }}</span>
                          <!-- Barra verde (altura según monto) -->
                          <div class="w-full bg-emerald-400 rounded-t-lg transition-all duration-1000" :style="{ height: getBarHeight(salesData.gross_sales) + '%' }"></div>
                          <span class="text-xs font-bold text-gray-500 mt-2">INGRESO</span>
                      </div>
                      <div class="flex flex-col items-center w-24">
                          <span class="text-blue-600 font-bold mb-2">${{ Number(salesData.net_profit).toFixed(0) }}</span>
                          <!-- Barra azul -->
                          <div class="w-full bg-blue-500 rounded-t-lg transition-all duration-1000 delay-100" :style="{ height: getBarHeight(salesData.net_profit) + '%' }"></div>
                          <span class="text-xs font-bold text-gray-500 mt-2">UTILIDAD</span>
                      </div>
                       <div class="flex flex-col items-center w-24">
                          <span class="text-red-500 font-bold mb-2">${{ Number(salesData.total_expenses).toFixed(0) }}</span>
                          <!-- Barra roja -->
                          <div class="w-full bg-red-400 rounded-t-lg transition-all duration-1000 delay-200" :style="{ height: getBarHeight(salesData.total_expenses) + '%' }"></div>
                          <span class="text-xs font-bold text-gray-500 mt-2">GASTOS</span>
                      </div>
                  </div>
              </div>
            </div>

            <!-- Top Platillos Reales -->
            <div class="bg-white rounded-xl shadow p-6 border border-gray-100 flex flex-col">
              <h3 class="text-lg font-bold text-gray-800 mb-6">Tus Platillos Estrella (Top 5 Histórico)</h3>
              
              <div v-if="topProducts.length === 0" class="flex-1 flex items-center justify-center text-gray-400 font-medium bg-gray-50 rounded-xl">
                  Aún no hay suficientes datos de ventas.
              </div>

              <ul v-else class="space-y-4 flex-1">
                <li v-for="(product, index) in topProducts" :key="product.id" class="flex items-center justify-between p-4 bg-slate-50 hover:bg-orange-50 transition-colors rounded-xl border border-transparent hover:border-orange-100 cursor-default">
                  <div class="flex items-center">
                    <div :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center font-bold mr-4 text-white shadow-sm',
                        index === 0 ? 'bg-yellow-400' : (index === 1 ? 'bg-gray-300' : (index === 2 ? 'bg-amber-600' : 'bg-slate-800'))
                    ]">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <p class="font-bold text-gray-800 leading-tight">{{ product.name }}</p>
                      <p class="text-sm font-medium text-gray-500 mt-0.5">{{ product.total_sold }} unidades vendidas</p>
                    </div>
                  </div>
                  <div class="text-right">
                      <span class="font-black text-gray-700 block">${{ Number(product.revenue).toFixed(2) }}</span>
                      <span class="text-xs text-green-500 font-bold">Ingreso bruto</span>
                  </div>
                </li>
              </ul>
            </div>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'

const auth = useAuthStore()
const companyId = auth.user?.company_id || 1

const loading = ref(true)
const period = ref('day') // day, week, month
const salesData = ref({
    gross_sales: 0,
    total_expenses: 0,
    net_profit: 0,
    total_orders: 0,
    average_ticket: 0
})
const topProducts = ref([])

const loadData = async () => {
    loading.value = true
    try {
        const [salesRes, productsRes] = await Promise.all([
            axios.get(`/api.php/analytics/sales?company_id=${companyId}&period=${period.value}`),
            axios.get(`/api.php/analytics/top-products?company_id=${companyId}`) // Historico
        ])

        if (salesRes.data.metrics) {
            salesData.value = salesRes.data.metrics
        }
        
        if (productsRes.data.top_products) {
            topProducts.value = productsRes.data.top_products
        }
    } catch (e) {
        console.error("Error cargando analíticas:", e)
    } finally {
        loading.value = false
    }
}

// Watcher para recargar si cambian el filtro de tiempo
watch(period, () => {
    loadData()
})

const getBarHeight = (value) => {
    const max = Math.max(salesData.value.gross_sales, salesData.value.total_expenses, 1) // Avoid div by 0
    let pct = (value / max) * 100
    return Math.max(5, pct) // Minimo 5% visual
}

onMounted(() => {
    loadData()
})
</script>
