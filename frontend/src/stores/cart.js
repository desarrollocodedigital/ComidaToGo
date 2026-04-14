import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from './auth'
import { useDialogStore } from './dialog'

export const useCartStore = defineStore('cart', () => {
    // State
    const items = ref([])
    const companyId = ref(null)
    const companyName = ref('')
    const isSyncing = ref(false)

    // Load from localStorage (Initial Load)
    try {
        if (localStorage.getItem('cart_items')) {
            items.value = JSON.parse(localStorage.getItem('cart_items'))
            companyId.value = JSON.parse(localStorage.getItem('cart_company_id'))
            companyName.value = localStorage.getItem('cart_company_name') || ''
        }
    } catch (e) {
        console.error("Error loading cart from localStorage", e)
    }

    // Persist to LocalStorage
    watch([items, companyId, companyName], () => {
        localStorage.setItem('cart_items', JSON.stringify(items.value))
        localStorage.setItem('cart_company_id', JSON.stringify(companyId.value))
        localStorage.setItem('cart_company_name', companyName.value)
        
        // Sync to cloud if authenticated
        syncToBackend()
    }, { deep: true })

    // Getters
    const cartCount = computed(() => items.value.reduce((acc, item) => acc + item.quantity, 0))
    const cartTotal = computed(() => items.value.reduce((acc, item) => acc + (item.unitPrice * item.quantity), 0))

    // SYNC LOGIC
    let syncTimeout = null
    async function syncToBackend() {
        const auth = useAuthStore()
        if (!auth.isAuthenticated || isSyncing.value) return

        if (syncTimeout) clearTimeout(syncTimeout)
        
        syncTimeout = setTimeout(async () => {
            try {
                await axios.post('/api.php/cart/sync', {
                    user_id: auth.user.id,
                    cart_data: {
                        items: items.value,
                        company_id: companyId.value,
                        company_name: companyName.value
                    }
                })
            } catch (e) {
                console.error("Failed to sync cart to backend", e)
            }
        }, 2000) // 2s debounce
    }

    function loadFromServer(cartData) {
        if (!cartData) return
        isSyncing.value = true
        try {
            const data = typeof cartData === 'string' ? JSON.parse(cartData) : cartData
            
            if (data && data.items) {
                if (companyId.value === data.company_id || !companyId.value) {
                    const localItems = [...items.value]
                    data.items.forEach(serverItem => {
                        const existing = localItems.find(li => li.key === serverItem.key)
                        if (existing) {
                            existing.quantity = Math.max(existing.quantity, serverItem.quantity)
                        } else {
                            localItems.push(serverItem)
                        }
                    })
                    items.value = localItems
                    companyId.value = data.company_id
                    companyName.value = data.company_name || ''
                } else {
                    items.value = data.items
                    companyId.value = data.company_id
                    companyName.value = data.company_name || ''
                }
            }
        } catch (e) {
            console.error("Error parsing server cart data", e)
        } finally {
            nextTick(() => { isSyncing.value = false })
        }
    }

    function nextTick(cb) { setTimeout(cb, 0) }

    // Actions
    async function addItem(product, quantity, modifiers, currentCompanyId, currentCompanyName, specialInstructions = '') {
        const dialog = useDialogStore()
        
        if (companyId.value && companyId.value !== currentCompanyId) {
            const confirmed = await dialog.confirm({
                title: 'Carrito ocupado',
                message: `No puedes elegir otro platillo porque tienes un pedido pendiente de "${companyName.value}". ¿Deseas vaciarlo para pedir en este nuevo restaurante?`,
                confirmText: 'Sí, vaciar y agregar',
                cancelText: 'Mejor no'
            })

            if (!confirmed) return false
            clearCart()
        }

        companyId.value = currentCompanyId
        companyName.value = currentCompanyName
        
        let unitPrice = parseFloat(product.price)
        modifiers.forEach(mod => {
            unitPrice += parseFloat(mod.price_adjustment)
        })

        const modIds = modifiers.map(m => m.id).sort().join('-')
        const safeInstructions = specialInstructions.trim().toLowerCase().replace(/[^a-z0-9]/g, '')
        const itemKey = `${product.id}-${modIds}-${safeInstructions}`

        const existingItem = items.value.find(i => i.key === itemKey)

        if (existingItem) {
            existingItem.quantity += quantity
        } else {
            items.value.push({
                key: itemKey,
                productId: product.id,
                name: product.name,
                unitPrice: unitPrice,
                quantity: quantity,
                modifiers: modifiers,
                specialInstructions: specialInstructions,
                imageUrl: product.image_url || null
            })
        }
        return true
    }

    function removeItem(key) {
        items.value = items.value.filter(i => i.key !== key)
        if (items.value.length === 0) {
            companyId.value = null
            companyName.value = ''
        }
    }

    function clearCart() {
        items.value = []
        companyId.value = null
        companyName.value = ''
        localStorage.removeItem('cart_items')
        localStorage.removeItem('cart_company_id')
        localStorage.removeItem('cart_company_name')
    }

    return { items, companyId, companyName, cartCount, cartTotal, addItem, removeItem, clearCart, loadFromServer }
})
