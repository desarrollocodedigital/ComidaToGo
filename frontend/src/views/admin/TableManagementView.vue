<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'
import { Plus, Edit, Trash2, Save, X, Utensils, Users, Hash, ArrowLeft } from 'lucide-vue-next'
import { useToast } from '../../composables/useToast'

const toast = useToast()
const auth = useAuthStore()
const tables = ref([])
const loading = ref(true)

// Modal state
const tableModal = ref({ open: false, data: {} })

const fetchData = async () => {
    loading.value = true
    try {
        const companyId = auth.user?.company_id || 1
        const res = await axios.get(`/api.php/tables?company_id=${companyId}`)
        tables.value = res.data
    } catch (e) {
        console.error("Error fetching tables data", e)
        toast.error("Error al cargar las mesas")
    } finally {
        loading.value = false
    }
}

const openTableModal = (table = null) => {
    tableModal.value.data = table ? { ...table } : { 
        name: '', 
        capacity: 4, 
        table_number: '', 
        company_id: auth.user.company_id 
    }
    tableModal.value.open = true
}

const saveTable = async () => {
    if (!tableModal.value.data.name) {
        toast.error("El nombre de la mesa es obligatorio")
        return
    }

    try {
        if (tableModal.value.data.id) {
            await axios.put(`/api.php/tables/${tableModal.value.data.id}`, tableModal.value.data)
            toast.success("Mesa actualizada correctamente")
        } else {
            await axios.post('api.php/tables', tableModal.value.data)
            toast.success("Mesa creada correctamente")
        }
        tableModal.value.open = false
        fetchData()
    } catch (e) {
        toast.error("Error al guardar la mesa")
    }
}

const deleteTable = async (id) => {
    if (!confirm("¿Seguró que deseas eliminar esta mesa permanentemente?")) return
    try {
        await axios.delete(`/api.php/tables/${id}`)
        toast.success("Mesa eliminada")
        fetchData()
    } catch (e) {
        toast.error("Error al eliminar la mesa")
    }
}

const getStatusColor = (status) => {
    switch (status) {
        case 'AVAILABLE': return 'bg-green-100 text-green-700'
        case 'OCCUPIED': return 'bg-red-100 text-red-700'
        case 'RESERVED': return 'bg-yellow-100 text-yellow-700'
        default: return 'bg-gray-100 text-gray-700'
    }
}

const getStatusLabel = (status) => {
    switch (status) {
        case 'AVAILABLE': return 'Libre'
        case 'OCCUPIED': return 'Ocupada'
        case 'RESERVED': return 'Reservada'
        default: return status
    }
}

onMounted(fetchData)
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header Estándar -->
        <header class="bg-white shadow-sm border-b border-gray-100 mb-8">
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-800">Gestión de Mesas</h1>
                    <p class="text-sm text-gray-500">Administra las mesas y su capacidad física</p>
                </div>
                <div class="flex items-center gap-3">
                    <router-link to="/admin/dashboard" class="flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl shadow-sm hover:bg-black transition-all font-bold w-fit">
                        <ArrowLeft class="w-5 h-5" />
                        Volver al Panel
                    </router-link>
                    <button @click="openTableModal()" class="flex items-center gap-2 bg-orange-500 text-white font-bold px-5 py-2.5 rounded-xl hover:bg-orange-600 shadow-lg shadow-orange-200 transition-all">
                        <Plus class="w-5 h-5" />
                        Nueva Mesa
                    </button>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-6 pb-12">

        <div v-if="loading" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto"></div>
        </div>

        <div v-else class="max-w-6xl mx-auto">
            <div v-if="tables.length === 0" class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-gray-200">
                <div class="bg-orange-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Utensils class="w-10 h-10 text-orange-500" />
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No tienes mesas configuradas</h3>
                <p class="text-gray-500 mb-6">Agrega mesas para poder asignar pedidos en el Punto de Venta.</p>
                <button @click="openTableModal()" class="bg-orange-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-orange-600 transition-all">
                    Crear mi primera mesa
                </button>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="table in tables" :key="table.id" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all group relative overflow-hidden">
                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4 text-[10px] font-black uppercase tracking-wider px-2 py-1 rounded-full" :class="getStatusColor(table.status)">
                        {{ getStatusLabel(table.status) }}
                    </div>

                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 font-black text-xl">
                            {{ table.table_number || '#' }}
                        </div>
                        <div>
                            <h3 class="font-black text-gray-800 text-lg leading-tight">{{ table.name }}</h3>
                            <div class="flex items-center gap-1 text-gray-400 text-xs font-bold uppercase mt-0.5">
                                <Users class="w-3 h-3" />
                                {{ table.capacity }} personas
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 border-t pt-4">
                        <button @click="openTableModal(table)" class="flex-1 flex items-center justify-center gap-2 bg-gray-50 text-gray-600 py-2 rounded-lg font-bold hover:bg-gray-100 transition-all text-sm">
                            <Edit class="w-4 h-4" /> Editar
                        </button>
                        <button @click="deleteTable(table.id)" class="bg-red-50 text-red-500 p-2 rounded-lg hover:bg-red-100 transition-all border border-red-100" title="Eliminar mesa">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="tableModal.open" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl transform transition-all">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h3 class="font-black text-2xl text-gray-800">{{ tableModal.data.id ? 'Editar Mesa' : 'Nueva Mesa' }}</h3>
                        <p class="text-sm text-gray-500">Ingresa los detalles de la mesa</p>
                    </div>
                    <button @click="tableModal.open = false" class="p-2 hover:bg-gray-100 rounded-full text-gray-400 transition-colors"><X class="w-6 h-6"/></button>
                </div>

                <div class="space-y-5">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-wider mb-1">Número</label>
                            <div class="relative">
                                <Hash class="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                <input v-model="tableModal.data.table_number" type="text" class="w-full pl-9 pr-3 py-3 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-orange-500 outline-none font-bold" placeholder="1">
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-wider mb-1">Nombre Descriptivo</label>
                            <input v-model="tableModal.data.name" type="text" class="w-full px-4 py-3 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-orange-500 outline-none font-bold" placeholder="Ej: Terraza 1, VIP">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-wider mb-1">Capacidad de Personas</label>
                        <div class="relative">
                            <Users class="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                            <input v-model="tableModal.data.capacity" type="number" min="1" class="w-full pl-9 pr-4 py-3 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-orange-500 outline-none font-bold">
                        </div>
                    </div>
                </div>

                <button @click="saveTable" class="w-full mt-10 bg-orange-500 text-white font-black py-4 rounded-2xl flex justify-center items-center gap-2 hover:bg-orange-600 shadow-xl shadow-orange-200 transition-all transform hover:-translate-y-1">
                    <Save class="w-5 h-5" /> Guardar Mesa
                </button>
            </div>
        </div>
        </div>
    </div>
</template>

<style scoped>
.min-h-screen {
    font-family: 'Inter', sans-serif;
}
</style>
