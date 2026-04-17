import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import axios from 'axios'

// Configurar base URL dinámica para subdirectorios
axios.defaults.baseURL = import.meta.env.BASE_URL

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
