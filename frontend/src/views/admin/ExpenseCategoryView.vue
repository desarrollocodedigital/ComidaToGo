<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import { ArrowLeft, Plus, Trash2, Wallet, Tag } from 'lucide-vue-next'

const auth = useAuthStore()
const companyId = auth.user?.company_id || 1

const categories = ref([])
const loading = ref(true)
const showModal = ref(false)
const submitting = ref(false)
const newCategoryName = ref('')

const loadCategories = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api.php/expense-categories?company_id=${companyId}`)
        categories.value = data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const createCategory = async () => {
    if (!newCategoryName.value.trim()) return
    submitting.value = true
    try {
        await axios.post('api.php/expense-categories', {
            company_id: companyId,
            name: newCategoryName.value
        })
        newCategoryName.value = ''
        showModal.value = false
        await loadCategories()
    } catch (e) {
        alert('Error al crear categoría')
    } finally {
        submitting.value = false
    }
}

const deleteCategory = async (id, name) => {
    if (!confirm(`¿Eliminar la categoría "${name}"? Los gastos existentes no se borrarán, pero ya no podrás seleccionar esta categoría.`)) return
    try {
        await axios.delete(`/api.php/expense-categories/${id}`)
        await loadCategories()
    } catch (e) {
        alert('Error al eliminar')
    }
}

onMounted(loadCategories)
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header Estándar -->
        <header class="bg-white shadow-sm border-b border-gray-100 mb-8">
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-800">Categorías de Gastos</h1>
                    <p class="text-sm text-gray-500">Define tus propios conceptos para las salidas de efectivo</p>
                </div>
                <div class="flex items-center gap-3">
                    <router-link to="/admin/dashboard" class="flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl shadow-sm hover:bg-black transition-all font-bold w-fit">
                        <ArrowLeft class="w-5 h-5" />
                        Volver al Panel
                    </router-link>
                    <button @click="showModal = true" class="flex items-center gap-2 bg-red-500 text-white font-bold px-5 py-2.5 rounded-xl hover:bg-red-600 shadow-md transition-all">
                        <Plus class="w-5 h-5" />
                        Nueva Categoría
                    </button>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-6 pb-12">
            <div v-if="loading" class="text-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500 mx-auto"></div>
            </div>

            <div v-else-if="categories.length === 0" class="bg-white p-12 rounded-2xl shadow-sm text-center border border-gray-100">
                <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Tag class="w-10 h-10 text-gray-300" />
                </div>
                <h2 class="text-xl font-bold text-gray-600 mb-2">No tienes categorías personalizadas</h2>
                <p class="text-gray-400 mb-6 font-medium">Crea conceptos como "Hielo", "Escobas", "Pintura" o "Pago de Luz" para organizar tus gastos.</p>
                <button @click="showModal = true" class="bg-red-50 text-red-600 font-bold px-6 py-3 rounded-xl hover:bg-red-100 transition-colors">
                    Crear mi primera categoría
                </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="cat in categories" :key="cat.id" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center">
                            <Wallet class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">{{ cat.name }}</h3>
                        </div>
                    </div>
                    <button @click="deleteCategory(cat.id, cat.name)" class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                        <Trash2 class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Nueva Categoría -->
        <div v-if="showModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
                <h2 class="text-2xl font-black text-gray-800 mb-2">Nueva Categoría</h2>
                <p class="text-sm text-gray-500 mb-6">Este concepto aparecerá al registrar una salida de dinero en caja.</p>
                
                <form @submit.prevent="createCategory" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Concepto</label>
                        <input v-model="newCategoryName" type="text" required class="w-full border-none bg-gray-50 rounded-xl p-4 focus:ring-2 focus:ring-red-500 outline-none font-bold text-lg" placeholder="Ej: Insumos de Limpieza">
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showModal = false" class="flex-1 py-4 bg-gray-100 text-gray-500 font-bold rounded-xl hover:bg-gray-200 transition-colors">Cancelar</button>
                        <button type="submit" :disabled="submitting || !newCategoryName.trim()" class="flex-1 py-4 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 shadow-lg shadow-red-100 disabled:opacity-50 transition-all">
                            {{ submitting ? 'Guardando...' : 'Crear Categoría' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
