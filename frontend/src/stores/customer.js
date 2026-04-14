import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useCustomerStore = defineStore('customer', () => {
    // State
    const profile = ref({
        name: '',
        phone: '',
        addresses: [] // Array of strings or objects, e.g. ['Calle 123, Colonia Centro']
    })

    // Initialization flag to know if we've asked them before
    const isConfigured = ref(false)

    // Load from localStorage
    if (localStorage.getItem('customer_profile')) {
        profile.value = JSON.parse(localStorage.getItem('customer_profile'))
        isConfigured.value = true
    } else {
        isConfigured.value = false
    }

    // Persist
    watch([profile, isConfigured], () => {
        if (isConfigured.value) {
            localStorage.setItem('customer_profile', JSON.stringify(profile.value))
        }
    }, { deep: true })

    // Actions
    function saveProfile(name, phone) {
        profile.value.name = name
        profile.value.phone = phone
        isConfigured.value = true
    }

    function addAddress(address) {
        if (!address.trim()) return
        if (!profile.value.addresses.includes(address.trim())) {
            profile.value.addresses.push(address.trim())
        }
    }

    function removeAddress(index) {
        profile.value.addresses.splice(index, 1)
    }

    return {
        profile,
        isConfigured,
        saveProfile,
        addAddress,
        removeAddress
    }
})
