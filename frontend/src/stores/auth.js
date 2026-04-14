import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import router from '../router'

export const useAuthStore = defineStore('auth', () => {
    let initialUser = null
    try {
        initialUser = JSON.parse(localStorage.getItem('user'))
    } catch (e) {
        localStorage.removeItem('user')
    }
    const user = ref(initialUser)
    const token = ref(localStorage.getItem('token')) // Si usáramos JWT, por ahora null/dummy

    async function login(email, password) {
        try {
            const { data } = await axios.post('/api.php/auth/login', { email, password })

            if (data.user) {
                user.value = data.user
                localStorage.setItem('user', JSON.stringify(data.user))

                // Redirigir según rol
                if (user.value.role === 'OWNER') {
                    router.push('/admin/dashboard')
                } else {
                    router.push('/')
                }
                return true
            } else {
                return false
            }
        } catch (e) {
            console.error(e)
            return false
        }
    }

    async function register(userData) {
        try {
            const { data } = await axios.post('/api.php/auth/register', userData)
            user.value = data.user
            localStorage.setItem('user', JSON.stringify(data.user))

            if (user.value.role === 'OWNER') {
                router.push('/admin/dashboard')
            } else {
                router.push('/')
            }
            return true
        } catch (e) {
            console.error(e)
            return false
        }
    }

    function logout() {
        user.value = null
        localStorage.removeItem('user')
        router.push('/login')
    }

    return { user, login, register, logout }
})
