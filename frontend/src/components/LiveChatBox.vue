<template>
  <div class="fixed bottom-6 right-6 z-50 font-sans">
    
    <!-- Floating Button (Collapsed State) -->
    <button 
        v-if="!isOpen" 
        @click="openChat"
        class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-2xl transition-transform hover:scale-105 flex items-center justify-center relative"
    >
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
      <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center animate-bounce border-2 border-white">{{ unreadCount }}</span>
    </button>

    <!-- Chat Window (Expanded State) -->
    <div v-else class="bg-white rounded-2xl shadow-2xl w-80 sm:w-96 flex flex-col h-[500px] border border-gray-200 overflow-hidden text-sm">
        <!-- Header -->
        <div class="bg-blue-600 p-4 text-white flex justify-between items-center shadow-md z-10">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-full">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                </div>
                <div>
                   <h3 class="font-bold text-lg leading-tight">{{ isAdmin ? 'Chat con Clientes' : 'Chat Soporte' }}</h3>
                   <span class="text-xs text-blue-100">{{ loading ? 'Conectando...' : 'En línea 🟢' }}</span>
                </div>
            </div>
            <button @click="isOpen = false" class="text-white hover:bg-black/10 p-1.5 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Admin Only: Selector de Cliente -->
        <div v-if="isAdmin && !loading" class="bg-slate-50 border-b border-gray-200 px-4 py-2 flex items-center gap-2">
            <span class="text-xs font-bold text-gray-500 uppercase">Viendo a ID:</span>
            <input v-model="targetUserId" type="number" class="w-16 px-2 py-1 border rounded text-xs text-center" @change="loadChat" title="Ingresa el ID del usuario cliente">
            <button @click="loadChat" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold hover:bg-blue-200">Ir</button>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-3" ref="messagesContainer">
             <div v-if="messages.length === 0" class="text-center text-gray-400 mt-10 text-xs px-6">
                 No hay mensajes yet. ¡Envía un mensaje para comenzar la conversación!
             </div>
             
             <div v-for="msg in messages" :key="msg.id" :class="['flex w-full', isMe(msg.sender_type) ? 'justify-end' : 'justify-start']">
                 <div :class="[
                     'max-w-[80%] rounded-2xl px-4 py-2.5 shadow-sm',
                     isMe(msg.sender_type) 
                        ? 'bg-blue-600 text-white rounded-br-none' 
                        : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none'
                 ]">
                     <p class="leading-relaxed whitespace-pre-wrap">{{ msg.message }}</p>
                     <p :class="['text-[10px] mt-1 text-right', isMe(msg.sender_type) ? 'text-blue-200' : 'text-gray-400']">{{ formatTime(msg.created_at) }}</p>
                 </div>
             </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-gray-100">
            <form @submit.prevent="sendMessage" class="flex gap-2">
                <input 
                    v-model="newMessage" 
                    type="text" 
                    placeholder="Escribe un mensaje..." 
                    class="flex-1 bg-gray-100 border-none rounded-full px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    :disabled="sending"
                >
                <button 
                   type="submit" 
                   :disabled="!newMessage.trim() || sending"
                   class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-full p-2.5 w-10 h-10 flex items-center justify-center transition-colors"
                >
                    <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
    isAdmin: { type: Boolean, default: false } // Si true, esta ventana la usa el restaurante
})

const auth = useAuthStore()

// Lógica de Identidad
// Si eres Admin, tu companyId es el tuyo. Si eres Cliente, le hablas al tenant activo (hardcode 1 para demo global)
const companyId = computed(() => props.isAdmin ? (auth.user?.company_id || 1) : 1) 
// Si eres Admin, tú eliges a qué cliente le hablas (caja de texto). Si eres cliente, eres tú mismo.
const targetUserId = ref(props.isAdmin ? 2 : (auth.user?.id || 2)) // Asume id=2 para cliente demo
const senderType = computed(() => props.isAdmin ? 'COMPANY' : 'CUSTOMER')

const isOpen = ref(false)
const loading = ref(false)
const messages = ref([])
const newMessage = ref('')
const sending = ref(false)
const unreadCount = ref(0)
const activeChatId = ref(null)

const messagesContainer = ref(null)
let pollInterval = null

const formatTime = (dateStr) => {
    if(!dateStr) return ''
    return new Date(dateStr).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
}

const isMe = (msgSenderType) => {
    return msgSenderType === senderType.value
}

const scrollToBottom = async () => {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

const openChat = () => {
    isOpen.value = true
    unreadCount.value = 0
    loadChat()
}

const loadChat = async () => {
    if(!companyId.value || !targetUserId.value) return
    loading.value = true
    try {
        const { data } = await axios.get(`/api.php/chat?company_id=${companyId.value}&user_id=${targetUserId.value}`)
        activeChatId.value = data.chat_id
        
        // Detectar mensajes nuevos para scroll animado o notificaciones (si estuviera cerrado)
        const newCount = data.messages.length - messages.value.length
        messages.value = data.messages
        
        if (newCount > 0) {
            scrollToBottom()
            if (!isOpen.value) unreadCount.value += newCount
        }

    } catch (e) {
        console.error("Error loading chat", e)
    } finally {
        loading.value = false
    }
}

const sendMessage = async () => {
    if (!newMessage.value.trim() || !activeChatId.value) return
    
    // Optimistic UI Send
    const textToSend = newMessage.value
    const tempMsg = {
        id: 'temp-' + Date.now(),
        sender_type: senderType.value,
        message: textToSend,
        created_at: new Date().toISOString()
    }
    messages.value.push(tempMsg)
    newMessage.value = ''
    scrollToBottom()
    
    sending.value = true
    try {
        await axios.post('api.php/chat/message', {
            chat_id: activeChatId.value,
            sender_type: senderType.value,
            message: textToSend
        })
        loadChat() // Confirmar con DB
    } catch (e) {
        alert("Error enviando mensaje")
        // Revertir optimistic UI
        messages.value = messages.value.filter(m => m.id !== tempMsg.id)
        newMessage.value = textToSend
    } finally {
        sending.value = false
    }
}

onMounted(() => {
    // Si la demo carga, arrancar polling silencioso
    loadChat()
    pollInterval = setInterval(() => {
        loadChat()
    }, 5000) // Poll cada 5s para simular "Tiempo real" en web compartida
})

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval)
})
</script>
