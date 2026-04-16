<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'
import { Plus, Edit, Trash2, Save, X, Image as ImageIcon, Upload, Loader2, Star } from 'lucide-vue-next'
import { useToast } from '../../composables/useToast'

const toast = useToast()
const auth = useAuthStore()
const categories = ref([])
const products = ref([])
const modifiers = ref([])
const loading = ref(true)

const activeTab = ref('MENU') // 'MENU' | 'EXTRAS'

// Modals state
const categoryModal = ref({ open: false, data: {} })
const productModal = ref({ open: false, data: {} })
const modifierModal = ref({ open: false, data: { options: [] } })
const uploadingImage = ref(false)
const fileInput = ref(null)

const fetchData = async () => {
    loading.value = true
    try {
        const companyId = auth.user?.company_id || 1
        const [catsRes, prodsRes, modsRes] = await Promise.all([
            axios.get(`/api.php/categories?company_id=${companyId}`),
            axios.get(`/api.php/products?company_id=${companyId}`),
            axios.get(`/api.php/modifiers?company_id=${companyId}`)
        ])
        categories.value = catsRes.data
        products.value = prodsRes.data
        modifiers.value = modsRes.data
    } catch (e) {
        console.error("Error fetching menu data", e)
    } finally {
        loading.value = false
    }
}

// --- Categorías ---
const openCategoryModal = (cat = null) => {
    categoryModal.value.data = cat ? { ...cat } : { name: '', sort_order: 0, company_id: auth.user.company_id }
    categoryModal.value.open = true
}

const saveCategory = async () => {
    try {
        if (categoryModal.value.data.id) {
            await axios.put(`/api.php/categories/${categoryModal.value.data.id}`, categoryModal.value.data)
        } else {
            await axios.post('/api.php/categories', categoryModal.value.data)
        }
        categoryModal.value.open = false
        toast.success(categoryModal.value.data.id ? "Categoría actualizada" : "Categoría creada")
        fetchData()
    } catch (e) {
        toast.error("Error al guardar categoría")
    }
}

const deleteCategory = async (id) => {
    if (!confirm("¿Seguro que deseas eliminar esta categoría? Se borrarán sus productos.")) return
    try {
        await axios.delete(`/api.php/categories/${id}`)
        toast.success("Categoría eliminada")
        fetchData()
    } catch (e) {
        toast.error("Error al eliminar categoría")
    }
}

// --- Productos ---
const openProductModal = (prod = null) => {
    productModal.value.data = prod ? { 
        ...prod, 
        modifier_group_ids: prod.modifier_group_ids || [] 
    } : { 
        name: '', description: '', price: 0, category_id: categories.value[0]?.id, 
        image_url: '', is_available: 1, is_featured: 0, modifier_group_ids: []
    }
    productModal.value.open = true
}

const saveProduct = async () => {
    try {
        // Validación de límite de platillos estrella (Máximo 3)
        if (Number(productModal.value.data.is_featured) === 1) {
            const currentFeaturedCount = products.value.filter(p => 
                Number(p.is_featured) === 1 && p.id !== productModal.value.data.id
            ).length;

            if (currentFeaturedCount >= 3) {
                toast.error("Límite excedido: Solo puedes tener hasta 3 platillos estrella.");
                return;
            }
        }

        if (productModal.value.data.id) {
            await axios.put(`/api.php/products/${productModal.value.data.id}`, productModal.value.data)
        } else {
            await axios.post('/api.php/products', productModal.value.data)
        }
        productModal.value.open = false
        toast.success(productModal.value.data.id ? "Platillo actualizado" : "Platillo creado")
        fetchData()
    } catch (e) {
        toast.error("Error al guardar producto")
    }
}

const deleteProduct = async (id) => {
    if (!confirm("¿Seguro que deseas eliminar este producto?")) return
    try {
        await axios.delete(`/api.php/products/${id}`)
        toast.success("Platillo eliminado")
        fetchData()
    } catch (e) {
        toast.error("Error al eliminar producto")
    }
}

const triggerFileUpload = () => {
    fileInput.value.click()
}

const handleFileUpload = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    const formData = new FormData()
    formData.append('image', file)
    
    // Enviar URL antigua para que el servidor la borre
    if (productModal.value.data.image_url) {
        formData.append('old_url', productModal.value.data.image_url)
    }

    uploadingImage.value = true
    try {
        const res = await axios.post('/api.php/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        productModal.value.data.image_url = res.data.url
        toast.success("Imagen cargada correctamente")
    } catch (e) {
        toast.error(e.response?.data?.message || "Error al subir la imagen")
    } finally {
        uploadingImage.value = false
    }
}

// --- Modificadores ---
const openModifierModal = (modGroup = null) => {
    if (modGroup) {
        modifierModal.value.data = JSON.parse(JSON.stringify(modGroup))
    } else {
        modifierModal.value.data = { 
            name: '', min_selection: 0, max_selection: 1, company_id: auth.user.company_id, options: [] 
        }
    }
    modifierModal.value.open = true
}

const addOptionLine = () => {
    modifierModal.value.data.options.push({ name: '', price_adjustment: 0 })
}

const removeOptionLine = (index) => {
    modifierModal.value.data.options.splice(index, 1)
}

const saveModifierGroup = async () => {
    try {
        let groupId = modifierModal.value.data.id
        
        // 1. Save Group
        if (groupId) {
            await axios.put(`/api.php/modifiers/${groupId}`, modifierModal.value.data)
        } else {
            const res = await axios.post('/api.php/modifiers', modifierModal.value.data)
            groupId = res.data.id
        }

        modifierModal.value.open = false
        toast.success("Grupo de extras guardado")
        fetchData()
    } catch (e) {
        toast.error("Error al guardar modificadores")
    }
}

const deleteModifierGroup = async (id) => {
    if (!confirm("¿Seguro que deseas eliminar este grupo de modificadores?")) return
    try {
        await axios.delete(`/api.php/modifiers/${id}`)
        toast.success("Grupo eliminado")
        fetchData()
    } catch (e) {
        toast.error("Error al eliminar cargador")
    }
}

onMounted(() => {
    fetchData()
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-6">
        <header class="max-w-6xl mx-auto flex justify-between items-center mb-4">
            <h1 class="text-3xl font-bold text-gray-800">Gestor de Menú</h1>
            <router-link to="/admin/dashboard" class="bg-gray-900 text-white px-4 py-2 rounded-lg shadow hover:bg-black font-medium">
                Volver al Panel
            </router-link>
        </header>

        <!-- Tabs -->
        <div class="max-w-6xl mx-auto border-b border-gray-200 mb-8 flex gap-8">
             <button @click="activeTab = 'MENU'" :class="['pb-3 font-bold text-lg border-b-2 transition-colors', activeTab === 'MENU' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-800']">
                 Platillos y Categorías
             </button>
             <button @click="activeTab = 'EXTRAS'" :class="['pb-3 font-bold text-lg border-b-2 transition-colors', activeTab === 'EXTRAS' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-800']">
                 Extras / Modificadores
             </button>
        </div>

        <div v-if="loading" class="text-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto"></div>
        </div>

        <!-- TAB MENU -->
        <div v-show="activeTab === 'MENU' && !loading" class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            
            <!-- Sidebar Categorías -->
            <div class="bg-white rounded-xl shadow-sm p-4 h-fit">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="font-bold text-lg">Categorías</h2>
                    <button @click="openCategoryModal()" class="text-orange-600 hover:text-orange-800" title="Nueva Categoría">
                        <Plus class="w-5 h-5"/>
                    </button>
                </div>
                
                <ul class="space-y-2">
                    <li v-for="cat in categories" :key="cat.id" class="flex justify-between items-center p-2 rounded hover:bg-gray-50 group">
                        <span class="font-medium text-gray-700">{{ cat.name }}</span>
                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openCategoryModal(cat)" class="text-blue-500 hover:text-blue-700"><Edit class="w-4 h-4"/></button>
                            <button @click="deleteCategory(cat.id)" class="text-red-500 hover:text-red-700"><Trash2 class="w-4 h-4"/></button>
                        </div>
                    </li>
                    <li v-if="categories.length === 0" class="text-sm text-gray-400 text-center py-4">No hay categorías</li>
                </ul>
            </div>

            <!-- Main Panel Productos -->
            <div class="md:col-span-3 bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h2 class="font-bold text-2xl">Platillos</h2>
                    <button @click="openProductModal()" class="flex items-center gap-2 bg-orange-500 text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition">
                        <Plus class="w-5 h-5"/> Nuevo Platillo
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="prod in products" :key="prod.id" class="border rounded-xl  overflow-hidden hover:shadow-md transition bg-white flex flex-col">
                        <div class="h-40 bg-gray-100 relative">
                            <img v-if="prod.image_url" :src="prod.image_url" class="origin-center w-full h-full object-cover">
                            <div v-else class="flex items-center justify-center w-full h-full text-gray-300">
                                <ImageIcon class="w-12 h-12" />
                            </div>
                            <div class="absolute top-2 right-2 flex flex-col gap-2 items-end">
                                <span v-if="!Number(prod.is_available)" class="bg-red-600 text-white text-[10px] font-black px-2 py-1 rounded shadow-lg uppercase tracking-widest">Agotado</span>
                                <div v-if="Number(prod.is_featured)" class="bg-amber-400 text-white p-1.5 rounded-full shadow-lg border border-white/50 backdrop-blur-sm">
                                    <Star class="w-3.5 h-3.5 fill-current" />
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-bold text-lg leading-tight">{{ prod.name }}</h3>
                                <span class="font-bold text-green-600">${{ parseFloat(prod.price).toFixed(2) }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mb-2">{{ prod.category_name }}</p>
                            <p class="text-sm text-gray-600 line-clamp-2 mb-4 flex-1">{{ prod.description }}</p>
                            
                            <div class="flex justify-end gap-2 mt-auto pt-2 border-t">
                                <button @click="openProductModal(prod)" class="p-2 text-blue-600 hover:bg-blue-50 rounded"><Edit class="w-4 h-4"/></button>
                                <button @click="deleteProduct(prod.id)" class="p-2 text-red-600 hover:bg-red-50 rounded"><Trash2 class="w-4 h-4"/></button>
                            </div>
                        </div>
                    </div>
                    <div v-if="products.length === 0" class="col-span-full py-10 text-center text-gray-400">
                        No hay platillos. Crea primero una categoría y añade platillos.
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB EXTRAS -->
        <div v-show="activeTab === 'EXTRAS' && !loading" class="max-w-6xl mx-auto flex flex-col gap-6">
             <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h2 class="font-bold text-2xl">Grupos de Extras</h2>
                    <button @click="openModifierModal()" class="flex items-center gap-2 bg-indigo-500 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-600 transition">
                        <Plus class="w-5 h-5"/> Nuevo Grupo
                    </button>
                </div>
                
                <div v-for="group in modifiers" :key="group.id" class="border rounded-xl mb-4 overflow-hidden shadow-sm">
                    <div class="bg-gray-50 p-4 border-b flex justify-between items-center">
                         <div>
                             <h3 class="font-bold text-lg text-gray-800">{{ group.name }}</h3>
                             <p class="text-sm text-gray-500">
                                Mínimo seleccionar: {{ group.min_selection }} | Máximo: {{ group.max_selection }}
                             </p>
                         </div>
                         <div class="flex gap-2">
                             <button @click="openModifierModal(group)" class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100"><Edit class="w-5 h-5"/></button>
                             <button @click="deleteModifierGroup(group.id)" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100"><Trash2 class="w-5 h-5"/></button>
                         </div>
                    </div>
                    <div class="p-4 bg-white grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                         <div v-for="opt in group.options" :key="opt.id" class="border rounded-lg p-3 flex justify-between items-center bg-gray-50/50">
                             <span class="font-medium text-gray-800">{{ opt.name }}</span>
                             <span class="text-green-600 font-bold">+${{ parseFloat(opt.price_adjustment).toFixed(2) }}</span>
                         </div>
                         <div v-if="group.options.length === 0" class="col-span-full text-gray-400 text-sm py-2">Sin opciones extras aún. Edita el grupo para añadirlas.</div>
                    </div>
                </div>

                <div v-if="modifiers.length === 0" class="text-center py-10 text-gray-400">
                    No tienes Grupos de Extras aún. (Ej: "Bebidas", "Tipos de Queso", "Salsas")
                </div>
             </div>
        </div>

        <!-- Modal Categoría -->
        <div v-if="categoryModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-xl">{{ categoryModal.data.id ? 'Editar' : 'Nueva' }} Categoría</h3>
                    <button @click="categoryModal.open = false" class="text-gray-400 hover:text-black"><X class="w-5 h-5"/></button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Nombre</label>
                        <input v-model="categoryModal.data.name" type="text" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Orden de aparición</label>
                        <input v-model="categoryModal.data.sort_order" type="number" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 outline-none">
                    </div>
                </div>
                <button @click="saveCategory" class="w-full mt-6 bg-black text-white font-bold py-3 rounded-lg flex justify-center items-center gap-2 hover:bg-gray-800">
                    <Save class="w-4 h-4" /> Guardar
                </button>
            </div>
        </div>

        <!-- Modal Producto -->
        <div v-if="productModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto">
            <div class="bg-white rounded-2xl p-6 w-full max-w-lg my-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-xl">{{ productModal.data.id ? 'Editar' : 'Nuevo' }} Platillo</h3>
                    <button @click="productModal.open = false" class="text-gray-400 hover:text-black"><X class="w-5 h-5"/></button>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold mb-1">Nombre del Platillo</label>
                            <input v-model="productModal.data.name" type="text" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Precio ($)</label>
                            <input v-model="productModal.data.price" type="number" step="0.01" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Categoría</label>
                            <select v-model="productModal.data.category_id" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 outline-none">
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold mb-1">Descripción</label>
                            <textarea v-model="productModal.data.description" rows="3" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 outline-none"></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold mb-2">Imagen del Producto</label>
                            
                            <div class="flex flex-col items-center gap-4">
                                <!-- Área de visualización/carga -->
                                <div 
                                    @click="triggerFileUpload"
                                    class="relative group w-full h-48 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col items-center justify-center cursor-pointer overflow-hidden hover:border-orange-500 hover:bg-orange-50/30 transition-all shadow-sm"
                                >
                                    <input 
                                        type="file" 
                                        ref="fileInput" 
                                        @change="handleFileUpload" 
                                        accept=".jpg,.jpeg,.png"
                                        class="hidden"
                                    >
                                    
                                    <div v-if="uploadingImage" class="flex flex-col items-center">
                                        <Loader2 class="w-8 h-8 text-orange-500 animate-spin mb-2" />
                                        <p class="text-sm font-medium text-gray-500">Subiendo...</p>
                                    </div>
                                    
                                    <template v-else>
                                        <img 
                                            v-if="productModal.data.image_url" 
                                            :src="productModal.data.image_url" 
                                            class="w-full h-full object-cover group-hover:opacity-75 transition-opacity"
                                        >
                                        <div v-else class="flex flex-col items-center text-gray-400 group-hover:text-orange-500">
                                            <Upload class="w-10 h-10 mb-2" />
                                            <p class="font-bold">Haz clic para subir imagen</p>
                                            <p class="text-xs">JPG, PNG permitidos</p>
                                        </div>
                                        
                                        <!-- Overlay de cambio -->
                                        <div v-if="productModal.data.image_url" class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <div class="bg-white/20 backdrop-blur-md px-4 py-2 rounded-full text-white font-bold text-sm border border-white/30">
                                                Cambiar Imagen
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                
                                <div class="w-full">
                                    <label class="block text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">O link directo</label>
                                    <input v-model="productModal.data.image_url" type="text" placeholder="https://..." class="w-full p-2 text-sm border rounded bg-gray-50 focus:ring-2 focus:ring-orange-500 outline-none transition-all">
                                </div>
                            </div>
                        </div>
                        <div class="col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                            <div class="flex items-center gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100 cursor-pointer hover:bg-white transition-colors">
                                <input type="checkbox" v-model="productModal.data.is_available" :true-value="1" :false-value="0" id="isAvail" class="w-4 h-4 text-orange-600 focus:ring-orange-500 rounded border-gray-300">
                                <label for="isAvail" class="text-sm font-bold text-gray-700 cursor-pointer">Disponible para la venta</label>
                            </div>
                            <div class="flex items-center gap-2 bg-amber-50/50 p-3 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition-colors group">
                                <input type="checkbox" v-model="productModal.data.is_featured" :true-value="1" :false-value="0" id="isFeat" class="w-4 h-4 text-amber-500 focus:ring-amber-500 rounded border-amber-200">
                                <label for="isFeat" class="text-sm font-bold text-amber-700 cursor-pointer flex items-center gap-2">
                                    <Star class="w-4 h-4 fill-amber-400 text-amber-400" />
                                    Es Platillo Estrella
                                </label>
                            </div>
                        </div>

                        <!-- Asignación de Extras -->
                        <div class="col-span-2 border-t pt-4 mt-2">
                            <label class="block text-sm font-bold mb-2">Asignar Grupos de Extras a este Platillo</label>
                            <div class="flex flex-wrap gap-3">
                                <label v-for="g in modifiers" :key="g.id" class="flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg shadow-sm cursor-pointer hover:bg-gray-100 transition">
                                    <input type="checkbox" v-model="productModal.data.modifier_group_ids" :value="g.id" class="w-4 h-4 text-orange-600 rounded">
                                    <span class="text-sm font-medium text-gray-800">{{ g.name }}</span>
                                </label>
                                <div v-if="modifiers.length === 0" class="text-sm text-gray-500 italic pb-2">
                                    Ve a la pestaña "Extras / Modificadores" para crear opciones como "Tipos de Queso" o "Salsas".
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button @click="saveProduct" class="w-full mt-6 bg-orange-500 text-white font-bold py-3 rounded-lg flex justify-center items-center gap-2 hover:bg-orange-600">
                    <Save class="w-4 h-4" /> Guardar Platillo
                </button>
            </div>
        </div>

        <!-- Modal Modificador -->
        <div v-if="modifierModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto">
            <div class="bg-white rounded-2xl p-6 w-full max-w-2xl my-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-xl">{{ modifierModal.data.id ? 'Editar' : 'Nuevo' }} Grupo de Extras</h3>
                    <button @click="modifierModal.open = false" class="text-gray-400 hover:text-black"><X class="w-5 h-5"/></button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-bold mb-1">Nombre del Grupo</label>
                        <input v-model="modifierModal.data.name" type="text" placeholder="Ej. Elige tu Salsa" class="w-full p-2 border rounded focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Sel. Mínima</label>
                        <input v-model="modifierModal.data.min_selection" type="number" min="0" class="w-full p-2 border rounded focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Sel. Máxima</label>
                        <input v-model="modifierModal.data.max_selection" type="number" min="1" class="w-full p-2 border rounded focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-gray-800">Opciones Extra</h4>
                        <button @click="addOptionLine" class="text-indigo-600 font-bold text-sm bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100">+ Añadir Opción</button>
                    </div>

                    <div class="space-y-3 max-h-48 overflow-y-auto pr-2">
                        <div v-for="(opt, idx) in modifierModal.data.options" :key="idx" class="flex gap-2 items-center">
                            <input v-model="opt.name" type="text" placeholder="Ej. Queso Extra" class="flex-1 p-2 border rounded text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <input v-model="opt.price_adjustment" type="number" step="0.01" placeholder="$0.00" class="w-24 p-2 border rounded text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <button @click="removeOptionLine(idx)" class="p-2 text-red-500 hover:bg-red-50 rounded"><Trash2 class="w-4 h-4"/></button>
                        </div>
                        <div v-if="modifierModal.data.options.length === 0" class="text-center text-sm text-gray-400 py-4">
                            Sin opciones. Da clic en "+ Añadir Opción".
                        </div>
                    </div>
                </div>
                
                <button @click="saveModifierGroup" class="w-full mt-6 bg-indigo-500 text-white font-bold py-3 rounded-lg flex justify-center items-center gap-2 hover:bg-indigo-600">
                    <Save class="w-4 h-4" /> Guardar Grupo de Extras
                </button>
            </div>
        </div>

    </div>
</template>
