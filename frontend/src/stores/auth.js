import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import router from '../router'
import { useCartStore } from './cart'

export const useAuthStore = defineStore('auth', () => {
    // ESTADO UNIFICADO
    let initialUser = {
        id: null,
        name: '',
        phone: '',
        email: '',
        role: null, // OWNER, CUSTOMER, GUEST, etc.
        addresses: [],
        company_id: null
    }

    try {
        const saved = localStorage.getItem('user')
        if (saved) {
            initialUser = { ...initialUser, ...JSON.parse(saved) }
        }
    } catch (e) {
        localStorage.removeItem('user')
    }

    const user = ref(initialUser)
    const token = ref(localStorage.getItem('token'))

    // GETTERS
    const isAuthenticated = computed(() => !!user.value.id)
    const isGuest = computed(() => !user.value.id && !!user.value.name)
    const isConfigured = computed(() => !!user.value.name)

    // PERSISTENCIA
    watch(user, (val) => {
        if (val.name || val.id) {
            localStorage.setItem('user', JSON.stringify(val))
        } else {
            localStorage.removeItem('user')
        }
    }, { deep: true })

    // ACCIONES
    async function login(email, password) {
        try {
            const { data } = await axios.post('/api.php/auth/login', { email, password })

            if (data.user) {
                // Combinar direcciones: Servidor + Locales (sin duplicados)
                const serverAddresses = data.user.addresses || []
                const localAddresses = user.value.addresses || []
                const mergedAddresses = [...new Set([...serverAddresses, ...localAddresses])]

                user.value = { 
                    ...initialUser, 
                    ...data.user, 
                    addresses: mergedAddresses 
                }
                
                token.value = data.token || 'dummy-token'
                localStorage.setItem('token', token.value)

                // Sincronizar carrito tras el login exitoso
                const cart = useCartStore()
                cart.loadFromServer(data.user.cart_data)

                if (user.value.role === 'OWNER') {
                    router.push('/admin/dashboard')
                } else {
                    router.push('/')
                }
                return true
            }
            return false
        } catch (e) {
            console.error(e)
            return false
        }
    }

    async function register(userData) {
        try {
            const localAddresses = user.value.addresses || []
            const { data } = await axios.post('/api.php/auth/register', userData)
            
            // Combinar con direcciones previas de invitado
            const serverAddresses = data.user.addresses || []
            const mergedAddresses = [...new Set([...serverAddresses, ...localAddresses])]

            user.value = { 
                ...initialUser, 
                ...data.user,
                addresses: mergedAddresses
            }
            
            // Sincronizar de inmediato si había direcciones de invitado
            if (localAddresses.length > 0) {
                await syncProfile()
            }

            token.value = data.token || 'dummy-token'
            localStorage.setItem('token', token.value)

            // Sincronizar carrito tras el registro
            const cart = useCartStore()
            cart.loadFromServer(data.user.cart_data)

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

    // Manejo de Perfil (Invitados o Clientes)
    async function syncProfile() {
        if (!isAuthenticated.value) return;
        try {
            await axios.post('/api.php/users/profile', {
                id: user.value.id,
                name: user.value.name,
                phone: user.value.phone,
                addresses: user.value.addresses
            }, {
                headers: { Authorization: `Bearer ${token.value}` }
            })
        } catch (e) {
            console.error("Error sincronizando perfil", e)
        }
    }

    async function setGuestProfile(name, phone) {
        user.value.name = name
        user.value.phone = phone
        if (!user.value.role) user.value.role = 'GUEST'
        await syncProfile()
    }

    async function addAddress(addressData) {
        let newEntry = typeof addressData === 'string' 
            ? { address: addressData.trim(), references: '' } 
            : { address: (addressData.address || '').trim(), references: (addressData.references || '').trim() }

        if (!newEntry.address) return
        if (!user.value.addresses) user.value.addresses = []

        // Normalizar y buscar duplicados
        const exists = user.value.addresses.some(item => {
            const addr = typeof item === 'string' ? item : item.address
            return addr.trim() === newEntry.address
        })

        if (!exists) {
            user.value.addresses.push(newEntry)
            await syncProfile()
        }
    }

    async function removeAddress(index) {
        user.value.addresses.splice(index, 1)
        await syncProfile()
    }

    function logout() {
        // Limpiamos el carrito primero
        const cart = useCartStore()
        cart.clearCart()

        // Limpieza total de sesión
        user.value = {
            id: null,
            name: '',
            phone: '',
            email: '',
            role: null,
            addresses: [],
            company_id: null
        }
        token.value = null
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        localStorage.removeItem('customer_profile')
        
        window.location.reload()
    }

    return { 
        user, 
        token, 
        isAuthenticated, 
        isGuest, 
        isConfigured, 
        login, 
        register, 
        logout,
        setGuestProfile,
        addAddress,
        removeAddress
    }
})
