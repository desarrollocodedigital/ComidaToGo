import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useDialogStore = defineStore('dialog', () => {
    const isOpen = ref(false)
    const title = ref('')
    const message = ref('')
    const type = ref('info') // info, confirm, warning, success
    const confirmText = ref('Aceptar')
    const cancelText = ref('Cancelar')
    
    // Promesa para manejar la respuesta del usuario
    let resolvePromise = null

    function alert(options) {
        return show({ ...options, type: 'info', cancelText: null })
    }

    function confirm(options) {
        return show({ ...options, type: 'confirm' })
    }

    function show(options) {
        title.value = options.title || 'Atención'
        message.value = options.message || ''
        type.value = options.type || 'info'
        confirmText.value = options.confirmText || 'Aceptar'
        cancelText.value = options.cancelText || 'Cancelar'
        
        isOpen.value = true
        
        return new Promise((resolve) => {
            resolvePromise = resolve
        })
    }

    function handleConfirm() {
        isOpen.value = false
        if (resolvePromise) resolvePromise(true)
    }

    function handleCancel() {
        isOpen.value = false
        if (resolvePromise) resolvePromise(false)
    }

    return {
        isOpen,
        title,
        message,
        type,
        confirmText,
        cancelText,
        alert,
        confirm,
        handleConfirm,
        handleCancel
    }
})
