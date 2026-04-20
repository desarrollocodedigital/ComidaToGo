<template>
  <div class="cash-register min-h-screen bg-gray-50">
    <!-- Header Estándar -->
    <header class="bg-white shadow-sm border-b border-gray-100 mb-8">
        <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-800">Control de Caja</h1>
                <p class="text-sm text-gray-500">Gestiona aperturas, cortes y salidas de efectivo</p>
            </div>
            <router-link to="/admin/dashboard" class="flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl shadow-sm hover:bg-black transition-all font-bold w-fit">
                <ArrowLeft class="w-5 h-5" />
                Volver al Panel
            </router-link>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 pb-12">
      <div v-if="loading" class="text-center py-20">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-slate-800 mx-auto"></div>
      </div>

      <div v-else class="bg-white rounded-xl shadow-lg overflow-hidden">
        
        <!-- ESTADO: CAJA CERRADA -->
        <div v-if="!shiftData">
            <div class="bg-red-500 p-6 text-white text-center">
              <h2 class="text-2xl font-bold">Caja Cerrada</h2>
              <p class="opacity-90 mt-1">Inicia un turno para poder procesar pagos en efectivo.</p>
            </div>
            
            <form @submit.prevent="openShift" class="p-8 max-w-md mx-auto">
               <div class="mb-6">
                   <label class="block text-gray-700 font-bold mb-2">Fondo de Caja Inicial ($)</label>
                   <input v-model.number="startingCash" type="number" step="0.01" min="0" required class="w-full text-2xl font-bold p-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 text-center" placeholder="0.00">
                   <p class="text-sm text-gray-500 mt-2 text-center">Efectivo físico con el que inicia este turno.</p>
               </div>
               <button type="submit" :disabled="isSubmitting" class="w-full bg-slate-800 hover:bg-black text-white font-bold py-4 rounded-xl text-lg transition-colors">
                   {{ isSubmitting ? 'Abriendo...' : 'Abrir Caja' }}
               </button>
            </form>
        </div>

        <!-- ESTADO: CAJA ABIERTA -->
        <div v-else>
            <!-- Status Header -->
            <div class="bg-green-500 p-6 text-white text-center">
              <h2 class="text-2xl font-bold">Turno Abierto</h2>
              <div class="flex items-center justify-center gap-4 mt-1 opacity-90 text-sm">
                <span class="flex items-center gap-1 font-bold">
                    <User class="w-4 h-4" /> {{ shiftData.shift.opened_by_name || 'Cajero' }}
                </span>
                <span class="opacity-50">|</span>
                <span>Apertura: {{ formatTime(shiftData.shift.opened_at) }}</span>
              </div>
            </div>

            <div class="p-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- Totales (Live Metrics from Backend) -->
                <div class="space-y-4">
                  <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <span class="text-gray-600">Fondo Inicial:</span>
                    <span class="font-bold text-gray-800">${{ Number(shiftData.shift.starting_cash).toFixed(2) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg border border-green-100">
                    <span class="text-gray-600">Ventas en Efectivo:</span>
                    <span class="font-bold text-green-600">+${{ Number(shiftData.metrics.cash_sales).toFixed(2) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg border border-red-100">
                    <span class="text-gray-600">Salidas / Gastos:</span>
                    <span class="font-bold text-red-600">-${{ Number(shiftData.metrics.expenses).toFixed(2) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg border border-blue-200 mt-4">
                    <span class="text-gray-800 font-bold">Efectivo Esperado:</span>
                    <span class="text-2xl font-black text-blue-700">${{ Number(shiftData.metrics.expected_cash).toFixed(2) }}</span>
                  </div>
                </div>

                <!-- Acciones -->
                <div class="flex flex-col space-y-4 justify-center">
                  <!-- Botón Registrar Gasto -->
                  <button @click="showExpenseModal = true" class="w-full py-4 bg-red-500 hover:bg-red-600 text-white rounded-xl font-bold text-lg shadow-sm transition-colors flex items-center justify-center">
                     <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                     Salida de Efectivo
                  </button>
                  
                  <!-- Botón Cerrar Caja -->
                  <button @click="showCloseModal = true" class="w-full py-4 bg-slate-800 hover:bg-black text-white rounded-xl font-bold text-lg shadow-sm transition-colors mt-8">
                     Hacer Corte de Caja
                  </button>

                  <router-link to="/demo/pos" class="w-full py-3 bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-200 rounded-xl font-bold text-center mt-2">
                      Ir al Punto de Venta
                  </router-link>
                </div>
              </div>
            </div>
        </div>
      </div>
    </div>

    <!-- MODAL: REGISTRAR GASTO -->
    <div v-if="showExpenseModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Registrar Salida de Efectivo</h3>
            <form @submit.prevent="recordExpense">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Monto ($)</label>
                    <input v-model.number="expenseForm.amount" type="number" step="0.01" min="0.01" required class="w-full p-3 bg-gray-50 border rounded-lg focus:ring-2 focus:ring-red-500 font-bold">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Motivo (Descripción)</label>
                    <input v-model="expenseForm.description" type="text" required placeholder="Ej. Pago a proveedor de hielo, Escobas..." class="w-full p-3 bg-gray-50 border rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                 <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                    <select v-model="expenseForm.category" required class="w-full p-4 bg-gray-50 border-none rounded-xl font-bold text-gray-700 focus:ring-2 focus:ring-red-500 outline-none">
                        <option v-if="expenseCategories.length === 0" value="OTROS">Otros / Sin categorías definidas</option>
                        <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.name">
                            {{ cat.name }}
                        </option>
                    </select>
                    <p v-if="expenseCategories.length === 0" class="text-xs text-gray-400 mt-2">
                        Puedes crear tus propias categorías en el panel de 
                        <router-link to="/admin/expense-categories" class="text-red-500 hover:underline">Mis Gastos</router-link>.
                    </p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="showExpenseModal = false" class="flex-1 py-3 bg-gray-200 text-gray-800 font-bold rounded-lg hover:bg-gray-300">Cancelar</button>
                    <button type="submit" :disabled="isSubmitting" class="flex-1 py-3 bg-red-500 text-white font-bold rounded-lg hover:bg-red-600">{{ isSubmitting ? 'Guardando...' : 'Registrar Salida' }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: CERRAR CAJA -->
    <div v-if="showCloseModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Corte Cuadrado (Cierre)</h3>
            <p class="text-sm text-gray-500 mb-6">Cuenta el dinero físico que hay en la gaveta en este momento e introdúcelo abajo.</p>
            
            <div class="bg-blue-50 p-4 rounded-lg mb-6 text-center border border-blue-100">
                <span class="text-blue-800 text-sm font-bold block">Efectivo Esperado (Sistema)</span>
                <span class="text-3xl font-black text-blue-900">${{ Number(shiftData.metrics.expected_cash).toFixed(2) }}</span>
            </div>

            <form @submit.prevent="closeShift">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-1 text-center">Efectivo Físico Contado ($)</label>
                    <input v-model.number="actualCash" type="number" step="0.01" min="0" required class="w-full p-4 text-3xl font-bold text-center bg-gray-50 border rounded-xl focus:ring-2 focus:ring-slate-800">
                    
                    <!-- Previsualización Discrepancia -->
                    <p v-if="actualCash !== null" :class="['text-center mt-2 font-bold text-sm', actualCash < shiftData.metrics.expected_cash ? 'text-red-500' : (actualCash > shiftData.metrics.expected_cash ? 'text-green-500' : 'text-gray-500')]">
                       Diferencia: ${{ (actualCash - shiftData.metrics.expected_cash).toFixed(2) }}
                    </p>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" @click="showCloseModal = false; actualCash = null" class="w-1/3 py-3 bg-gray-200 text-gray-800 font-bold rounded-lg hover:bg-gray-300">Volver</button>
                    <button type="submit" :disabled="isSubmitting" class="flex-1 py-3 bg-slate-800 text-white font-bold rounded-lg hover:bg-black">{{ isSubmitting ? 'Cerrando...' : 'Confirmar Cierre' }}</button>
                </div>
            </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useDialogStore } from '../../stores/dialog'
import { useToast } from '../../composables/useToast'
import axios from 'axios'
import { ArrowLeft, Wallet, TrendingUp, Receipt, AlertCircle, CheckCircle, User } from 'lucide-vue-next'

const auth = useAuthStore()
const dialog = useDialogStore()
const toast = useToast()
const companyId = auth.user?.company_id || 1

// Estado
const loading = ref(true)
const isSubmitting = ref(false)
const shiftData = ref(null) // null significa caja cerrada

// Formularios
const startingCash = ref(null)
const actualCash = ref(null)

// Modales
const showExpenseModal = ref(false)
const showCloseModal = ref(false)
const expenseCategories = ref([])
const expenseForm = ref({ amount: null, description: '', category: '' })

const loadCategories = async () => {
    try {
        const { data } = await axios.get(`/api.php/expense-categories?company_id=${companyId}`)
        expenseCategories.value = data
        // Seleccionar la primera por defecto si existe
        if (data.length > 0) {
            expenseForm.value.category = data[0].name
        } else {
            expenseForm.value.category = 'OTROS'
        }
    } catch (e) {
        console.error('Error cargando categorías de gastos:', e)
        expenseForm.value.category = 'OTROS'
    }
}

// Formateador de fechas
const formatTime = (dateStr) => {
    return new Date(dateStr).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
}

// Cargar Estatus
const loadStatus = async () => {
    loading.value = true
    try {
        const res = await axios.get(`/api.php/cash-register/status?company_id=${companyId}`)
        if (res.data.has_active_shift) {
            shiftData.value = res.data
        } else {
            shiftData.value = null
        }
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

// Acciones API
const openShift = async () => {
    if(!startingCash.value && startingCash.value !== 0) return
    isSubmitting.value = true
    try {
        await axios.post('api.php/cash-register/open', {
            company_id: companyId,
            user_id: auth.user?.id || 1, // Fallback si no hay sesion auth
            starting_cash: startingCash.value
        })
        startingCash.value = null
        await loadStatus() // Refrescar UI a abierta
        toast.success("¡Caja abierta exitosamente!")
    } catch (e) {
        toast.error("Error al abrir caja: " + (e.response?.data?.message || e.message))
    } finally {
        isSubmitting.value = false
    }
}

const recordExpense = async () => {
    isSubmitting.value = true
    try {
        await axios.post('api.php/expenses', {
            company_id: companyId,
            cash_register_shift_id: shiftData.value.shift.id,
            user_id: auth.user?.id || 1,
            amount: expenseForm.value.amount,
            category: expenseForm.value.category,
            description: expenseForm.value.description
        })
        showExpenseModal.value = false
        expenseForm.value = { amount: null, description: '', category: 'OTHER' }
        toast.success("Salida de efectivo registrada")
        await loadStatus() // Refresca métricas live
    } catch (e) {
         toast.error("Error: " + (e.response?.data?.message || e.message))
    } finally {
         isSubmitting.value = false
    }
}

const closeShift = async () => {
    if(actualCash.value === null) return
    
    // Si hay faltante, pide justificación mínima visualmente, o avisa
    const diff = actualCash.value - shiftData.value.metrics.expected_cash
    if (diff < 0) {
        const confirmed = await dialog.confirm({
            title: '¿Confirmar con faltante?',
            message: `⚠️ ADVERTENCIA: Te faltan $${Math.abs(diff).toFixed(2)} en caja.\n\n¿Estás seguro de cerrar el turno de todos modos?`,
            type: 'warning',
            confirmText: 'Cerrar con faltante',
            cancelText: 'Volver a contar'
        })
        if (!confirmed) return
    }

    isSubmitting.value = true
    try {
        const res = await axios.post('api.php/cash-register/close', {
            shift_id: shiftData.value.shift.id,
            user_id: auth.user?.id || 1,
            actual_ending_cash: actualCash.value
        })
        
        const summary = res.data.summary
        const discrepancyText = summary.discrepancy_amount < 0 
            ? `Faltante: -$${Math.abs(summary.discrepancy_amount).toFixed(2)}`
            : (summary.discrepancy_amount > 0 ? `Sobrante: +$${summary.discrepancy_amount.toFixed(2)}` : 'Caja cuadrada')

        await dialog.confirm({
            title: '¡Corte Exitoso!',
            message: `Resumen del Turno:\n\n` +
                     `💰 Fondo Inicial: $${Number(summary.starting_cash).toFixed(2)}\n` +
                     `📈 Ventas Totales: $${Number(summary.cash_sales).toFixed(2)}\n` +
                     `📉 Gastos/Salidas: -$${Number(summary.expenses).toFixed(2)}\n\n` +
                     `🧾 Efectivo Final: $${Number(summary.actual_ending_cash).toFixed(2)}\n` +
                     `📊 ${discrepancyText}`,
            type: 'success',
            confirmText: 'Finalizar',
            cancelText: null // Solo botón de aceptar
        })
        
        showCloseModal.value = false
        actualCash.value = null
        await loadStatus() // Refrescar UI a cerrada
    } catch (e) {
         toast.error("Error al cerrar: " + (e.response?.data?.message || e.message))
    } finally {
         isSubmitting.value = false
    }
}

onMounted(() => {
    loadStatus()
    loadCategories()
})
</script>
