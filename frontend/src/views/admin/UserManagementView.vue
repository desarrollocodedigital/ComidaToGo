<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import { UserPlus, Trash2, ArrowLeft, Shield } from 'lucide-vue-next'

const auth = useAuthStore()
const companyId = auth.user?.company_id || 1

const users = ref([])
const loading = ref(true)
const showModal = ref(false)
const submitting = ref(false)

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
    try {
        await axios.post('/api.php/users', { ...form.value, company_id: companyId })
        form.value = { name: '', email: '', password: '', role: 'CASHIER' }
        showModal.value = false
        await loadUsers()
    } catch (e) {
        alert(e.response?.data?.message || 'Error al crear empleado')
    } finally { submitting.value = false }
}

const deleteUser = async (id, name) => {
    if (!confirm(`¿Eliminar a ${name} del equipo?`)) return
    try {
        await axios.delete(`/api.php/users/${id}`)
        await loadUsers()
    } catch (e) { alert('Error al eliminar') }
}

onMounted(loadUsers)
</script>

<template>
    <div class="min-h-screen bg-gray-100 p-6 font-sans">
        <div class="max-w-3xl mx-auto">
            <header class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <router-link to="/admin/dashboard" class="p-2 hover:bg-gray-200 rounded-full">
                        <ArrowLeft class="w-6 h-6" />
                    </router-link>
                    <h1 class="text-3xl font-bold text-gray-800">Mi Equipo</h1>
                </div>
                <button @click="showModal = true" class="flex items-center gap-2 bg-orange-500 text-white font-bold px-5 py-3 rounded-xl hover:bg-orange-600 shadow-lg transition-all">
                    <UserPlus class="w-5 h-5" />
                    Nuevo Empleado
                </button>
            </header>

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
                        <button @click="deleteUser(user.id, user.name)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors">
                            <Trash2 class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Nuevo Empleado -->
        <div v-if="showModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Nuevo Empleado</h2>
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
