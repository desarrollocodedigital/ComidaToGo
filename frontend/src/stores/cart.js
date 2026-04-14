import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

export const useCartStore = defineStore('cart', () => {
    // State
    const items = ref([])
    const companyId = ref(null)

    // Load from localStorage
    if (localStorage.getItem('cart_items')) {
        items.value = JSON.parse(localStorage.getItem('cart_items'))
        companyId.value = JSON.parse(localStorage.getItem('cart_company_id'))
    }

    // Persist
    watch([items, companyId], () => {
        localStorage.setItem('cart_items', JSON.stringify(items.value))
        localStorage.setItem('cart_company_id', JSON.stringify(companyId.value))
    }, { deep: true })

    // Getters
    const cartCount = computed(() => items.value.reduce((acc, item) => acc + item.quantity, 0))
    const cartTotal = computed(() => items.value.reduce((acc, item) => acc + (item.unitPrice * item.quantity), 0))

    // Actions
    function addItem(product, quantity, modifiers, currentCompanyId, specialInstructions = '') {
        // Validar Single Tenant Cart
        if (companyId.value && companyId.value !== currentCompanyId) {
            if (!confirm('Solo puedes pedir de un negocio a la vez. ¿Quieres limpiar el carrito y empezar de nuevo?')) {
                return false
            }
            clearCart()
        }

        companyId.value = currentCompanyId

        // Calcular precio unitario con modificadores
        let unitPrice = parseFloat(product.price)
        modifiers.forEach(mod => {
            unitPrice += parseFloat(mod.price_adjustment)
        })

        // Generar Key única basada en modificadores e instrucciones para agrupar
        // Sort IDs para que [1, 2] sea igual a [2, 1]
        const modIds = modifiers.map(m => m.id).sort().join('-')
        const safeInstructions = specialInstructions.trim().toLowerCase().replace(/[^a-z0-9]/g, '')
        const itemKey = `${product.id}-${modIds}-${safeInstructions}`

        const existingItem = items.value.find(i => i.key === itemKey)

        if (existingItem) {
            existingItem.quantity += quantity
            // Retrocompatibilidad: Si el item viejo no tenía imagen guardada, se la agregamos
            if (existingItem.imageUrl === undefined || !existingItem.imageUrl) {
                existingItem.imageUrl = product.image_url || null
            }
        } else {
            items.value.push({
                key: itemKey,
                productId: product.id,
                name: product.name,
                unitPrice: unitPrice,
                quantity: quantity,
                modifiers: modifiers, // Guardamos objeto completo para mostrar nombres
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
        }
    }

    function clearCart() {
        items.value = []
        companyId.value = null
    }

    return { items, companyId, cartCount, cartTotal, addItem, removeItem, clearCart }
})
