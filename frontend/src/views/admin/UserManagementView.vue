<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useDialogStore } from '../../stores/dialog'
import axios from 'axios'
import { UserPlus, Trash2, ArrowLeft, Shield } from 'lucide-vue-next'

const auth = useAuthStore()
const dialog = useDialogStore()
const companyId = auth.user?.company_id || 1

const users = ref([])
const loading = ref(true)
const showModal = ref(false)
const submitting = ref(false)
const errorMessage = ref('')

const form = ref({ name: '', email: '', password: '', role: 'CASHIER' })

const roleLabels = {
    KITCHEN: { label: '👨‍🍳 Cocina', color: 'bg-yellow-100 text-yellow-800' },
    CASHIER: { label: '💵 Cajero/a', color: 'bg-green-100 text-green-800' },
    WAITER: { label: '🍽️ Mesero/a', color: 'bg-blue-100 text-blue-800' },
    OWNER: { label: '👑 Administrador', color: 'bg-purple-100 text-purple-800' }
}

const loadUsers = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/api.php/users?company_id=${companyId}`)
        users.value = data
    } catch (e) { console.error(e) }
    finally { loading.value = false }
}

const addUser = async () => {
    submitting.value = true
    errorMessage.value = ''
    try {
        await axios.post('/api.php/users', { ...form.value, company_id: companyId })
        form.value = { name: '', email: '', password: '', role: 'CASHIER' }
        showModal.value = false
        await loadUsers()
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Error al crear empleado'
    } finally { submitting.value = false }
}

const deleteUser = async (id, name) => {
    const ok = await dialog.confirm({
        title: '¿Eliminar Empleado?',
        message: `Esta acción eliminará a ${name} de forma permanente del equipo. ¿Estás seguro?`,
        confirmText: 'Sí, Eliminar',
        cancelText: 'No, Mantener'
    })
    
    if (!ok) return
    try {
        await axios.delete(`/api.php/users/${id}`)
        await loadUsers()
    } catch (e) { alert('Error al eliminar') }
}

const toggleStatus = async (user) => {
    const newStatus = user.active === 1 ? 0 : 1
    const action = newStatus === 1 ? 'activar' : 'desactivar'
    
    const ok = await dialog.confirm({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Acceso`,
        message: `¿Deseas ${action} el acceso al sistema para ${user.name}?`,
        confirmText: `Sí, ${action}`,
        cancelText: 'Cancelar'
    })

    if (!ok) return

    try {
        await axios.put('/api.php/users/status', {
            id: user.id,
            active: newStatus
        })
        await loadUsers()
    } catch (e) {
        alert('Error al cambiar el estado')
    }
}

onMounted(loadUsers)
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header Estándar -->
        <header class="bg-white shadow-sm border-b border-gray-100 mb-8">
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-800">Mi Equipo</h1>
                    <p class="text-sm text-gray-500">Gestiona el personal, sus roles y accesos al sistema</p>
                </div>
                <div class="flex items-center gap-3">
                    <router-link to="/admin/dashboard" class="flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl shadow-sm hover:bg-black transition-all font-bold w-fit">
                        <ArrowLeft class="w-5 h-5" />
                        Volver al Panel
                    </router-link>
                    <button @click="showModal = true" class="flex items-center gap-2 bg-orange-500 text-white font-bold px-5 py-2.5 rounded-xl hover:bg-orange-600 shadow-md transition-all">
                        <UserPlus class="w-5 h-5" />
                        Nuevo Empleado
                    </button>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-6 pb-12">

            <div v-if="loading" class="text-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto"></div>
            </div>

            <div v-else-if="users.length === 0" class="bg-white p-12 rounded-2xl shadow-sm text-center">
                <Shield class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <h2 class="text-xl font-bold text-gray-600 mb-2">Sin empleados registrados</h2>
                <p class="text-gray-400">Agrega personal de cocina, cajeros o meseros para que usen el sistema.</p>
            </div>

            <div v-else class="space-y-3">
                <div v-for="user in users" :key="user.id" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">{{ user.name }}</h3>
                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['px-3 py-1 rounded-full text-xs font-bold', roleLabels[user.role]?.color || 'bg-gray-100 text-gray-600']">
                            {{ roleLabels[user.role]?.label || user.role }}
                        </span>
                        
                        <!-- Badge de Estado -->
                        <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider', 
                                      user.active === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                            {{ user.active === 1 ? 'Activo' : 'Inactivo' }}
                        </span>

                        <!-- Acciones (Solo para empleados, no para el Dueño) -->
                        <div v-if="user.role !== 'OWNER'" class="flex items-center border-l pl-3 ml-1 gap-1">
                            <button @click="toggleStatus(user)" 
                                    :title="user.active === 1 ? 'Desactivar acceso' : 'Activar acceso'"
                                    :class="['p-2 rounded-full transition-colors', 
                                             user.active === 1 ? 'text-gray-400 hover:text-red-500 hover:bg-red-50' : 'text-emerald-500 hover:bg-emerald-50']">
                                <Shield class="w-5 h-5" />
                            </button>
                            <button @click="deleteUser(user.id, user.name)" title="Eliminar permanentemente" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>
                        <div v-else class="pl-3 ml-1">
                            <span class="text-[10px] font-bold text-gray-300 uppercase italic">Cuenta Principal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Nuevo Empleado -->
        <div v-if="showModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Nuevo Empleado</h2>
                
                <!-- Mensaje de Error Integrado -->
                <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-bold rounded flex items-center gap-2 animate-shake">
                    <span>⚠️</span>
                    {{ errorMessage }}
                </div>

                <form @submit.prevent="addUser" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nombre Completo</label>
                        <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-500 outline-none" placeholder="María López">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email (para iniciar sesión)</label>
                        <input v-model="form.email" type="email" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-500 outline-none" placeholder="maria@mirestaurante.com">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                        <input v-model="form.password" type="password" required minlength="4" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-500 outline-none" placeholder="••••••">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Rol</label>
                        <select v-model="form.role" class="w-full border border-gray-300 rounded-lg p-3 font-bold focus:ring-2 focus:ring-orange-500 outline-none">
                            <option value="CASHIER">💵 Cajero/a — Acepta pedidos, cobra</option>
                            <option value="KITCHEN">👨‍🍳 Cocina — Ve pedidos aceptados, marca listos</option>
                            <option value="WAITER">🍽️ Mesero/a — Toma pedidos en POS</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="flex-1 py-3 bg-gray-200 text-gray-800 font-bold rounded-lg hover:bg-gray-300">Cancelar</button>
                        <button type="submit" :disabled="submitting" class="flex-1 py-3 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 shadow-lg">
                            {{ submitting ? 'Guardando...' : 'Crear Empleado' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
