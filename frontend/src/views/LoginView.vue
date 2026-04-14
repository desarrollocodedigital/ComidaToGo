<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const doLogin = async () => {
    loading.value = true
    error.value = ''
    const success = await auth.login(email.value, password.value)
    if (!success) {
        error.value = 'Credenciales incorrectas'
    }
    loading.value = false
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-3xl font-bold text-center mb-8">Iniciar Sesión</h1>

            <form @submit.prevent="doLogin" class="space-y-4">
                <div v-if="error" class="bg-red-100 text-red-700 p-3 rounded-lg text-sm text-center">
                    {{ error }}
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                    <input v-model="email" type="email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                    <input v-model="password" type="password" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none">
                </div>

                <button 
                    :disabled="loading"
                    class="w-full bg-black text-white py-4 rounded-xl font-bold text-lg hover:bg-gray-800 transition-colors"
                >
                    {{ loading ? 'Entrando...' : 'Entrar' }}
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    ¿Nuevo aquí? 
                    <router-link to="/registro" class="text-orange-600 font-bold hover:underline">Crea una cuenta</router-link>
                </p>
            </div>
        </div>
    </div>
</template>
