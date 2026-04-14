<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { Store, User } from 'lucide-vue-next'

const auth = useAuthStore()
const role = ref('CUSTOMER') // CUSTOMER | OWNER
const name = ref('')
const email = ref('')
const password = ref('')
// Owner fields
const companyName = ref('')
const companySlug = ref('')

const loading = ref(false)
const error = ref('')

const doRegister = async () => {
    error.value = ''
    if (!name.value || !email.value || !password.value) {
        error.value = 'Completa todos los campos básicos'
        return
    }
    if (role.value === 'OWNER' && (!companyName.value || !companySlug.value)) {
        error.value = 'Completa los datos de tu negocio'
        return
    }

    loading.value = true
    const success = await auth.register({
        name: name.value,
        email: email.value,
        password: password.value,
        role: role.value,
        company_name: companyName.value,
        company_slug: companySlug.value
    })

    if (!success) {
        error.value = 'Error al registrar. Revisa los datos o intenta con otro email.'
    }
    loading.value = false
}

// Auto-generar slug
const generateSlug = () => {
    companySlug.value = companyName.value
        .toLowerCase()
        .replace(/ /g, '-')
        .replace(/[^\w-]+/g, '');
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-3xl font-bold text-center mb-2">Crear Cuenta</h1>
            <p class="text-center text-gray-500 mb-8">Únete a ComidaToGo</p>

            <!-- Role Selector -->
            <div class="flex gap-4 mb-8">
                <div 
                    @click="role = 'CUSTOMER'"
                    class="flex-1 p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center gap-2"
                    :class="role === 'CUSTOMER' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300'"
                >
                    <User class="w-8 h-8" />
                    <span class="font-bold">Comensal</span>
                </div>
                <div 
                    @click="role = 'OWNER'"
                    class="flex-1 p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center gap-2"
                    :class="role === 'OWNER' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300'"
                >
                    <Store class="w-8 h-8" />
                    <span class="font-bold">Negocio</span>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="doRegister" class="space-y-4">
                <div v-if="error" class="bg-red-100 text-red-700 p-3 rounded-lg text-sm text-center">
                    {{ error }}
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre Completo</label>
                    <input v-model="name" type="text" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Juan Pérez">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                    <input v-model="email" type="email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="juan@ejemplo.com">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                    <input v-model="password" type="password" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="********">
                </div>

                <!-- Owner Fields -->
                <div v-if="role === 'OWNER'" class="pt-4 border-t border-gray-100 space-y-4 animate-fade-in">
                    <h3 class="font-bold text-gray-900">Datos del Restaurante</h3>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Negocio</label>
                        <input @input="generateSlug" v-model="companyName" type="text" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Tacos Juan">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">URL (Slug)</label>
                        <div class="flex items-center border rounded-lg bg-gray-50 px-3">
                            <span class="text-gray-500 text-sm">comidatogo.com/</span>
                            <input v-model="companySlug" type="text" class="w-full p-3 bg-transparent outline-none text-gray-900 font-medium" placeholder="tacos-juan">
                        </div>
                    </div>
                </div>

                <button 
                    :disabled="loading"
                    class="w-full bg-black text-white py-4 rounded-xl font-bold text-lg hover:bg-gray-800 transition-colors mt-6"
                >
                    {{ loading ? 'Creando cuenta...' : 'Registrarse' }}
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes cuenta? 
                    <router-link to="/login" class="text-orange-600 font-bold hover:underline">Inicia Sesión</router-link>
                </p>
            </div>
        </div>
    </div>
</template>
