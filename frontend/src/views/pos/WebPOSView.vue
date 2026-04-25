<template>
  <div class="web-pos flex h-screen bg-slate-50 overflow-hidden font-sans">
    
    <!-- Sidebar de Navegación -->
    <div class="w-24 bg-white border-r border-gray-200 flex flex-col items-center py-6 shadow-sm z-10">
      <router-link to="/admin/dashboard" class="mb-8 p-3 rounded-xl bg-orange-100 text-orange-600 hover:bg-orange-200" title="Volver al Panel">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      </router-link>
      
      <div class="space-y-6 flex-1 w-full px-4">
        <!-- Modo Venta -->
        <button 
            @click="activeView = 'MENU'"
            :class="['w-full h-16 rounded-2xl flex flex-col items-center justify-center shadow-sm transition-colors', activeView === 'MENU' ? 'bg-slate-800 text-white shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border border-gray-200']">
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
          <span class="text-xs font-bold">Menú</span>
        </button>
        <!-- Modo Pedidos Web -->
        <button 
            @click="activeView = 'ORDERS_WEB'"
            :class="['w-full h-16 rounded-2xl flex flex-col items-center justify-center shadow-sm transition-colors relative', activeView === 'ORDERS_WEB' ? 'bg-orange-500 text-white shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border border-gray-200']">
          <div v-if="pendingWebOrders.length > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-white text-xs font-black flex items-center justify-center animate-pulse">
              {{ pendingWebOrders.length }}
          </div>
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
          <span class="text-[10px] font-bold text-center leading-tight">Pedidos Web</span>
        </button>

        <button 
            @click="activeView = 'ORDERS_LOCAL'"
            :class="['w-full h-16 rounded-2xl flex flex-col items-center justify-center shadow-sm transition-colors relative', activeView === 'ORDERS_LOCAL' ? 'bg-slate-800 text-white shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border border-gray-200']">
          <div v-if="preparingLocalOrders.length > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full text-white text-xs font-black flex items-center justify-center animate-pulse">
              {{ preparingLocalOrders.length }}
          </div>
          <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
          <span class="text-[10px] font-bold text-center leading-tight">Pedidos Local</span>
        </button>
      </div>

      <!-- Indicador Caja Abierta -->
      <div class="mt-auto px-2">
         <div v-if="activeShiftId" class="w-full text-center">
             <div class="w-3 h-3 bg-green-500 rounded-full mx-auto animate-pulse mb-1"></div>
             <span class="text-[10px] font-bold text-green-600 block leading-tight">Caja<br>Abierta</span>
         </div>
         <div v-else class="w-full text-center cursor-pointer" @click="$router.push('/caja')">
             <div class="w-3 h-3 bg-red-500 rounded-full mx-auto mb-1"></div>
             <span class="text-[10px] font-bold text-red-600 block leading-tight">Abrir<br>Caja</span>
         </div>
      </div>
    </div>

    <!-- MAIN AREA: MENÚ DE PRODUCTOS -->
    <div v-if="activeView === 'MENU'" class="flex-1 flex flex-col h-full bg-slate-50">
      <div class="h-20 bg-white border-b border-gray-200 px-8 flex items-center shadow-sm z-0">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight min-w-max mr-8">Punto de Venta</h2>
        <div class="relative flex-1 max-w-xl">
          <input v-model="searchQuery" type="text" placeholder="Buscar platillo por nombre..." class="w-full bg-slate-100 border-none rounded-full py-3 pl-12 pr-4 text-slate-700 focus:ring-2 focus:ring-orange-500 focus:outline-none">
          <svg class="w-5 h-5 text-gray-400 absolute left-4 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
      </div>

      <div v-if="loading" class="flex-1 flex items-center justify-center">
         <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
      </div>
      <div v-else-if="error" class="flex-1 flex items-center justify-center p-8 text-center text-red-500">{{ error }}</div>

      <div v-else class="flex-1 overflow-y-auto p-8">
        <div class="mb-6 flex gap-3 overflow-x-auto pb-2 no-scrollbar">
          <span @click="selectedCategory = null" :class="['px-5 py-2 rounded-full text-sm font-semibold cursor-pointer shadow-sm whitespace-nowrap', selectedCategory === null ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']">Todos</span>
          <span v-for="cat in categories" :key="cat.id" @click="selectedCategory = cat.id" :class="['px-5 py-2 rounded-full text-sm font-semibold cursor-pointer shadow-sm whitespace-nowrap', selectedCategory === cat.id ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']">{{ cat.name }}</span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div v-for="product in filteredProducts" :key="product.id" @click="addToCart(product)" class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-200 cursor-pointer transition-all transform hover:-translate-y-1 relative overflow-hidden">
            <div class="w-full h-32 bg-slate-100 rounded-xl mb-4 flex items-center justify-center text-4xl overflow-hidden" :class="{ 'animate-pulse': product.image_url }">
                <img 
                    v-if="product.image_url" 
                    :src="product.image_url" 
                    @load="$event.target.classList.remove('opacity-0'); $event.target.parentElement.classList.remove('animate-pulse')"
                    class="w-full h-full object-cover transition-opacity duration-700 opacity-0"
                >
                <span v-else>🍽️</span>
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight line-clamp-2">{{ product.name }}</h3>
            <p class="text-orange-600 font-bold mt-2 text-xl">${{ Number(product.price).toFixed(2) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN AREA: PEDIDOS WEB -->
    <div v-if="activeView === 'ORDERS_WEB'" class="flex-1 flex flex-col h-full bg-slate-50 overflow-y-auto">
        <!-- 1. Pedidos Web Pendientes -->
        <div class="p-6 border-b border-slate-200 bg-red-50/50">
            <h2 class="text-xl font-bold text-red-700 mb-4 flex items-center gap-2">
                Pedidos Web Pendientes
                <span v-if="pendingWebOrders.length > 0" class="bg-red-500 text-white px-2 py-0.5 rounded-full text-sm">{{ pendingWebOrders.length }}</span>
            </h2>
            <div class="grid gap-3">
                <div v-for="order in pendingWebOrders" :key="order.id" 
                    :class="[
                        'bg-white rounded-3xl shadow-sm border-l-[6px] transition-all relative overflow-hidden',
                        order.order_type === 'DELIVERY' ? 'border-purple-500 bg-purple-50/10' : 
                        order.order_type === 'PICKUP' ? 'border-blue-500 bg-blue-50/10' : 
                        'border-green-500 bg-green-50/10'
                    ]"
                >
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-4">
                                <div :class="[
                                    'w-14 h-14 rounded-2xl flex items-center justify-center font-black text-xl shadow-inner',
                                    order.order_type === 'DELIVERY' ? 'bg-purple-100 text-purple-600' : 
                                    order.order_type === 'PICKUP' ? 'bg-blue-100 text-blue-600' : 
                                    'bg-green-100 text-green-600'
                                ]">
                                    #{{ order.id }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-black text-xl text-slate-800">{{ order.customer_name }}</h3>
                                        <span v-if="order.order_type === 'DINE_IN'" class="bg-green-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">
                                            {{ (order.table_number || order.table_name || '?').toString().toLowerCase().startsWith('mesa') ? (order.table_number || order.table_name) : `Mesa ${order.table_number || order.table_name || '?'}` }}
                                        </span>
                                        <span v-else-if="order.order_type === 'TAKEAWAY'" class="bg-green-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">
                                            Para llevar
                                        </span>
                                        <span v-else-if="order.order_type === 'DELIVERY'" class="bg-purple-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">
                                            Domicilio
                                        </span>
                                        <span v-else class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">
                                            Recoger {{ order.scheduled_at ? `(${formatTime(order.scheduled_at)})` : '' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1 text-sm text-slate-500 font-bold">
                                        <div class="flex items-center gap-1" v-if="order.customer_phone">
                                            <Phone class="w-3.5 h-3.5" /> {{ order.customer_phone }}
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <component :is="order.order_type === 'DELIVERY' ? Truck : (order.order_type === 'DINE_IN' ? Utensils : ShoppingBag)" class="w-3.5 h-3.5" />
                                            {{ order.order_type === 'DELIVERY' ? 'Envío' : (order.order_type === 'DINE_IN' ? 'Mesa' : 'En Local') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div :class="['px-4 py-2 rounded-2xl text-sm font-black flex items-center gap-2 shadow-sm border border-white/50', getTimerColor(order.created_at)]">
                                <Clock class="w-4 h-4" /> {{ formatElapsed(order.created_at) }}
                            </div>
                        </div>

                        <div v-if="order.customer_address" class="mb-4 p-3 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-2 text-sm text-slate-600">
                             <div class="flex items-start gap-2">
                                <MapPin class="w-4 h-4 text-purple-500 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-black block text-xs text-slate-400 uppercase tracking-tighter">Dirección de Entrega</span>
                                    {{ order.customer_address }}
                                </div>
                             </div>
                             <div v-if="order.customer_references" class="flex items-start gap-2 pt-2 border-t border-slate-200/50">
                                <div class="w-4 h-4 flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="text-purple-400 text-xs">ℹ️</span>
                                </div>
                                <div class="italic text-slate-500 text-[11px]">
                                    <span class="font-black not-italic inline-block text-[10px] text-slate-400 uppercase tracking-tighter mr-1">Ref:</span>
                                    {{ order.customer_references }}
                                </div>
                             </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-4">
                            <div class="bg-slate-50/50 px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 flex justify-between">
                                <span>Detalle del Pedido</span>
                                <span>Cantidad</span>
                            </div>
                            <div class="divide-y divide-slate-50">
                                <div v-for="item in order.items" :key="item.id" class="px-4 py-3 flex justify-between items-start">
                                    <div>
                                        <span class="font-bold text-slate-700">{{ item.product_name }}</span>
                                        <div v-if="item.modifiers" class="text-[11px] text-slate-400 font-medium leading-tight mt-0.5 italic">
                                            {{ item.modifiers }}
                                        </div>
                                    </div>
                                    <div class="bg-slate-100 text-slate-600 px-2 py-1 rounded-lg font-black text-xs">
                                        {{ item.quantity }}x
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center bg-slate-50 -mx-5 -mb-5 p-5 border-t border-slate-100">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase block leading-none mb-1">Total a Pagar</span>
                                <span class="text-2xl font-black text-slate-800">${{ Number(order.total_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button @click="rejectOrder(order.id)" class="flex items-center gap-2 px-4 py-3 bg-white border border-red-100 text-red-500 font-bold rounded-2xl hover:bg-red-50 transition-all text-sm group">
                                    <XCircle class="w-5 h-5 group-hover:scale-110 transition-transform" /> Rechazar
                                </button>
                                <button @click="acceptOrder(order.id)" class="flex items-center gap-2 px-6 py-3 bg-orange-500 text-white font-black rounded-2xl hover:bg-orange-600 shadow-lg shadow-orange-100 transition-all text-sm group">
                                    <CheckCircle2 class="w-5 h-5 group-hover:scale-110 transition-transform" /> Aceptar Pedido <ArrowRight class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="pendingWebOrders.length === 0" class="text-center text-gray-400 py-12">No hay pedidos web pendientes</p>
            </div>
        </div>
        <!-- 1.1 Pedidos Web en Preparación -->
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-xl font-bold text-slate-700 mb-4 flex items-center gap-2">
                Pedidos Web en preparación
                <span v-if="preparingWebOrders.length > 0" class="bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full text-sm">{{ preparingWebOrders.length }}</span>
            </h2>
            <div class="grid gap-6">
                <div v-for="order in preparingWebOrders" :key="order.id" 
                    :class="[
                        'bg-white rounded-3xl shadow-sm border-l-[6px] transition-all relative overflow-hidden',
                        order.order_type === 'DELIVERY' ? 'border-purple-500 bg-purple-50/10' : 'border-blue-500 bg-blue-50/10'
                    ]"
                >
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-4">
                                <div :class="[
                                    'w-14 h-14 rounded-2xl flex items-center justify-center font-black text-xl shadow-inner',
                                    order.order_type === 'DELIVERY' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600'
                                ]">
                                    #{{ order.id }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-black text-xl text-slate-800">{{ order.customer_name }}</h3>
                                        <span v-if="order.order_type === 'DELIVERY'" class="bg-purple-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">Domicilio</span>
                                        <span v-else class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">Recoger {{ order.scheduled_at ? `(${formatTime(order.scheduled_at)})` : '' }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        {{ translateStatus(order.status) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div :class="['px-4 py-2 rounded-2xl text-sm font-black flex items-center gap-2 shadow-sm border border-white/50', getTimerColor(order.created_at)]">
                                    <Clock class="w-4 h-4" /> {{ formatElapsed(order.created_at) }}
                                </div>
                            </div>
                        </div>

                        <div v-if="order.customer_address" class="mb-4 p-3 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-2 text-sm text-slate-600">
                             <div class="flex items-start gap-2">
                                <MapPin class="w-4 h-4 text-purple-500 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-black block text-xs text-slate-400 uppercase tracking-tighter">Dirección de Entrega</span>
                                    {{ order.customer_address }}
                                </div>
                             </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                            <div class="bg-slate-50/50 px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 flex justify-between">
                                <span>Detalle del Pedido</span>
                                <span>Cant.</span>
                            </div>
                            <div class="divide-y divide-slate-50 overflow-y-auto max-h-48 no-scrollbar">
                                <div v-for="item in order.items" :key="item.id" class="px-4 py-3 flex justify-between items-start">
                                    <div>
                                        <span class="font-bold text-slate-700">{{ item.product_name }}</span>
                                        <div v-if="item.modifiers" class="text-[11px] text-slate-400 font-medium leading-tight mt-0.5 italic">
                                            {{ item.modifiers }}
                                        </div>
                                    </div>
                                    <div class="bg-slate-100 text-slate-600 px-2 py-1 rounded-lg font-black text-xs">
                                        {{ item.quantity }}x
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="preparingWebOrders.length === 0" class="text-center text-gray-400 py-12">No hay pedidos web en preparación</p>
            </div>
        </div>

        <!-- 2. Pedidos Web Listos -->
        <div v-if="readyWebOrders.length > 0" class="p-6">
            <h2 class="text-xl font-bold text-blue-700 mb-4 flex items-center gap-2">
                Pedidos Web Listos
                <span class="bg-blue-500 text-white px-2 py-0.5 rounded-full text-sm">{{ readyWebOrders.length }}</span>
            </h2>
            <div class="grid gap-3">
                <div v-for="order in readyWebOrders" :key="order.id" 
                    :class="[
                        'bg-white rounded-3xl shadow-sm border p-5 flex flex-col md:flex-row justify-between items-center gap-4 transition-all',
                        order.order_type === 'DELIVERY' ? 'border-purple-200' : 
                        order.order_type === 'PICKUP' ? 'border-blue-200' : 
                        'border-green-200'
                    ]"
                >
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div :class="[
                            'w-12 h-12 rounded-xl flex items-center justify-center font-black shadow-sm',
                            order.order_type === 'DELIVERY' ? 'bg-purple-100 text-purple-600' : 
                            order.order_type === 'PICKUP' ? 'bg-blue-100 text-blue-600' : 
                            'bg-green-100 text-green-600'
                        ]">
                            #{{ order.id }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-black text-lg text-slate-800">{{ order.customer_name }}</h3>
                                <span v-if="order.order_type === 'DINE_IN'" class="text-xs font-black text-green-600">{{ (order.table_number || order.table_name || '?').toString().toLowerCase().startsWith('mesa') ? (order.table_number || order.table_name) : `Mesa ${order.table_number || order.table_name || '?'}` }}</span>
                                <span v-else-if="order.order_type === 'TAKEAWAY'" class="text-xs font-black text-green-600">Para Llevar</span>
                                <span v-else-if="order.order_type === 'DELIVERY'" class="text-xs font-black text-purple-600">A Domicilio</span>
                                <span v-else class="text-xs font-black text-blue-600">Para Recoger {{ order.scheduled_at ? `(${formatTime(order.scheduled_at)})` : '' }}</span>
                            </div>
                            <p class="text-slate-500 font-bold text-sm">Total a cobrar: <span class="text-slate-800 font-black text-lg ml-1">${{ Number(order.total_amount).toFixed(2) }}</span></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 w-full md:w-auto justify-end">
                        <button @click="printTicket(order)" class="p-2.5 bg-white border border-slate-200 text-slate-500 rounded-xl hover:bg-slate-50 transition-all shadow-sm" title="Imprimir Ticket">
                            <Printer class="w-5 h-5" />
                        </button>
                        <button @click="chargeWebOrder(order, 'CASH')" class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-green-500 text-white font-black py-2.5 px-5 rounded-xl hover:bg-green-600 shadow-lg shadow-green-100 transition-all text-xs group">
                            <span class="group-hover:scale-110 transition-transform">💵</span> Efectivo
                        </button>
                        <button @click="chargeWebOrder(order, 'CARD')" class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-blue-500 text-white font-black py-2.5 px-5 rounded-xl hover:bg-blue-600 shadow-lg shadow-blue-100 transition-all text-xs group">
                            <span class="group-hover:scale-110 transition-transform">💳</span> Tarjeta
                        </button>
                        <button @click="chargeWebOrder(order, 'TRANSFER')" class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-slate-700 text-white font-black py-2.5 px-5 rounded-xl hover:bg-slate-800 shadow-lg shadow-slate-200 transition-all text-xs group">
                            <span class="group-hover:scale-110 transition-transform">📲</span> Transfer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN AREA: PEDIDOS LOCALES -->
    <div v-if="activeView === 'ORDERS_LOCAL'" class="flex-1 flex flex-col h-full bg-slate-50 overflow-y-auto">
        <!-- 1. Pedidos Locales en Preparación -->
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-xl font-bold text-slate-700 mb-6 flex items-center gap-2">
                En preparación
                <span v-if="preparingLocalOrders.length > 0" class="bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full text-sm">{{ preparingLocalOrders.length }}</span>
            </h2>
            <div class="grid gap-6">
                <div v-for="order in preparingLocalOrders" :key="order.id" 
                    :class="[
                        'bg-white rounded-3xl shadow-sm border-l-[6px] transition-all relative overflow-hidden',
                        order.order_type === 'DINE_IN' ? 'border-green-500 bg-green-50/10' : 'border-blue-500 bg-blue-50/10'
                    ]"
                >
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-800 flex items-center justify-center font-black text-xl shadow-inner">
                                    #{{ order.id }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-black text-xl text-slate-800">{{ order.customer_name }}</h3>
                                        <span class="bg-green-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">
                                            {{ order.order_type === 'DINE_IN' ? ( (order.table_number || order.table_name || '?').toString().toLowerCase().startsWith('mesa') ? (order.table_number || order.table_name) : `Mesa ${order.table_number || order.table_name || '?'}`) : 'Llevar' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        {{ translateStatus(order.status) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div :class="['px-4 py-2 rounded-2xl text-sm font-black flex items-center gap-2 shadow-sm border border-white/50', getTimerColor(order.created_at)]">
                                    <Clock class="w-4 h-4" /> {{ formatElapsed(order.created_at) }}
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                            <div class="bg-slate-50/50 px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 flex justify-between">
                                <span>Detalle del Pedido</span>
                                <span>Cant.</span>
                            </div>
                            <div class="divide-y divide-slate-50 overflow-y-auto max-h-48 no-scrollbar">
                                <div v-for="item in order.items" :key="item.id" class="px-4 py-3 flex justify-between items-start hover:bg-slate-50/50 transition-colors">
                                    <div>
                                        <span class="font-bold text-slate-700">{{ item.product_name }}</span>
                                        <div v-if="item.modifiers" class="text-[11px] text-slate-400 font-medium leading-tight mt-0.5 italic">
                                            {{ item.modifiers }}
                                        </div>
                                    </div>
                                    <div class="bg-slate-100 text-slate-600 px-2 py-1 rounded-lg font-black text-xs">
                                        {{ item.quantity }}x
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="preparingLocalOrders.length === 0" class="text-center text-gray-400 py-12">No hay pedidos en preparación</p>
            </div>
        </div>

        <!-- 2. Pedidos Locales Listos para Cobrar -->
        <div class="p-6 bg-green-50/30 min-h-[300px]">
            <h2 class="text-xl font-bold text-green-700 mb-6 flex items-center gap-2">
                Listos para cobrar
                <span v-if="readyLocalOrders.length > 0" class="bg-green-500 text-white px-2 py-0.5 rounded-full text-sm">{{ readyLocalOrders.length }}</span>
            </h2>
            <div class="grid gap-6">
                <div v-for="order in readyLocalOrders" :key="order.id" 
                    :class="[
                        'bg-white rounded-3xl shadow-md border-l-[6px] transition-all relative overflow-hidden ring-1 ring-green-100',
                        order.order_type === 'DINE_IN' ? 'border-green-500' : 'border-blue-500'
                    ]"
                >
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center font-black text-xl shadow-inner border border-green-200">
                                    #{{ order.id }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-black text-xl text-slate-800">{{ order.customer_name }}</h3>
                                        <span class="bg-green-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">
                                            {{ order.order_type === 'DINE_IN' ? ( (order.table_number || order.table_name || '?').toString().toLowerCase().startsWith('mesa') ? (order.table_number || order.table_name) : `Mesa ${order.table_number || order.table_name || '?'}`) : 'Llevar' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1 text-[10px] font-black text-green-600 uppercase tracking-widest">
                                        {{ translateStatus(order.status) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-4">
                            <div class="bg-slate-50/50 px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 flex justify-between">
                                <span>Detalle del Pedido</span>
                                <span>Cant.</span>
                            </div>
                            <div class="divide-y divide-slate-50 overflow-y-auto max-h-48 no-scrollbar">
                                <div v-for="item in order.items" :key="item.id" class="px-4 py-3 flex justify-between items-start hover:bg-slate-50/50 transition-colors">
                                    <div>
                                        <span class="font-bold text-slate-700">{{ item.product_name }}</span>
                                        <div v-if="item.modifiers" class="text-[11px] text-slate-400 font-medium leading-tight mt-0.5 italic">
                                            {{ item.modifiers }}
                                        </div>
                                    </div>
                                    <div class="bg-slate-100 text-slate-600 px-2 py-1 rounded-lg font-black text-xs">
                                        {{ item.quantity }}x
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 bg-green-50/50 -mx-5 -mb-5 p-5 border-t border-green-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black text-green-700 uppercase block leading-none mb-1">Total a Cobrar</span>
                                <span class="text-2xl font-black text-slate-800">${{ Number(order.total_amount).toFixed(2) }}</span>
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                <button @click="printTicket(order)" class="flex items-center justify-center bg-white border border-slate-200 text-slate-500 rounded-xl hover:bg-slate-50 transition-all shadow-sm" title="Imprimir Ticket">
                                    <Printer class="w-5 h-5" />
                                </button>
                                <button @click="processCharge(order, 'CASH')" class="flex items-center justify-center gap-2 bg-green-500 text-white font-black py-2.5 rounded-xl hover:bg-green-600 shadow-lg shadow-green-100 transition-all text-[10px] group">
                                    <span class="text-sm group-hover:scale-110 transition-transform">💵</span> Efectivo
                                </button>
                                <button @click="processCharge(order, 'CARD')" class="flex items-center justify-center gap-2 bg-blue-500 text-white font-black py-2.5 rounded-xl hover:bg-blue-600 shadow-lg shadow-blue-100 transition-all text-[10px] group">
                                    <span class="text-sm group-hover:scale-110 transition-transform">💳</span> Tarjeta
                                </button>
                                <button @click="processCharge(order, 'TRANSFER')" class="flex items-center justify-center gap-2 bg-slate-700 text-white font-black py-2.5 rounded-xl hover:bg-slate-800 shadow-lg shadow-slate-200 transition-all text-[10px] group">
                                    <span class="text-sm group-hover:scale-110 transition-transform">📲</span> Transfer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="readyLocalOrders.length === 0" class="text-center text-gray-400 py-12">No hay pedidos listos para cobrar</p>
            </div>
        </div>
    </div>

    <!-- TICKET PANEL LATERAL -->
    <div class="w-96 bg-white border-l border-gray-200 shadow-xl flex flex-col z-20">
      
      <div class="p-6 border-b border-slate-100 bg-slate-50/50">
        <div class="flex bg-slate-200 rounded-lg p-1 relative overflow-hidden">
          <div 
            class="absolute inset-y-1 bg-white rounded-md shadow-sm transition-all duration-300 ease-out" 
            :style="{ 
                left: cartType === 'DINE_IN' ? '4px' : 'calc(50% + 2px)', 
                width: 'calc(50% - 6px)' 
            }"
          ></div>
          <button @click="cartType = 'DINE_IN'" class="flex-1 py-2 text-sm font-bold text-slate-800 relative z-10 whitespace-nowrap">Para Mesa</button>
          <button @click="cartType = 'TAKEAWAY'" class="flex-1 py-2 text-sm font-bold text-slate-800 relative z-10 whitespace-nowrap">Para Llevar</button>
        </div>
        
        <div v-if="cartType === 'DINE_IN'" class="mt-4">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Asignar Mesa</label>
            <button 
                @click="openTableModal"
                :class="[
                    'w-full py-3 px-4 rounded-xl border-2 border-dashed font-bold flex items-center justify-between transition-colors',
                    selectedTableId 
                        ? 'border-green-500 bg-green-50 text-green-700' 
                        : 'border-slate-300 bg-slate-50 text-slate-500 hover:bg-slate-100'
                ]"
            >
                <span class="flex items-center gap-2">
                    <Utensils class="w-5 h-5" />
                    {{ selectedTableId ? tables.find(t => t.id === selectedTableId)?.name || 'Mesa Seleccionada' : 'Seleccionar Mesa' }}
                </span>
                <span class="text-xs bg-white shadow-sm px-2 py-1 rounded-lg border" v-if="!selectedTableId">Elegir</span>
            </button>
        </div>
         <div v-if="cartType === 'TAKEAWAY'" class="mt-4">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Nombre del Cliente</label>
            <input v-model="customerName" type="text" placeholder="Ej. Juan Pérez" class="w-full bg-white border border-slate-200 rounded-lg p-3 text-slate-700 font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none">
        </div>

        <div v-if="isEditingOrder" class="mt-4 p-3 bg-orange-100 border border-orange-200 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-lg">🔥</span>
                <div class="leading-tight">
                    <p class="text-[10px] font-black text-orange-600 uppercase tracking-widest">Añadiendo a</p>
                    <p class="font-black text-slate-800">Orden #{{ editingOrderId }}</p>
                </div>
            </div>
            <button @click="isEditingOrder = false; editingOrderId = null; selectedTableId = null" class="text-orange-600 hover:bg-orange-200 p-1.5 rounded-lg transition-colors">
                <XCircle class="w-5 h-5" />
            </button>
        </div>
      </div>

      <!-- Ítems del Ticket -->
      <div class="flex-1 overflow-y-auto p-0">
        <div v-if="cart.length === 0" class="p-8 text-center text-slate-400 font-medium flex flex-col items-center justify-center h-full">
            <svg class="w-16 h-16 mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            El ticket está vacío.<br>Agrega productos del menú.
        </div>
        
        <ul class="divide-y divide-slate-100">
            <li v-for="(item, index) in cart" :key="index" class="p-4 hover:bg-slate-50 group">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1 pr-4">
                        <h4 class="font-bold text-slate-800 text-sm">{{ item.quantity }}x {{ item.name }}</h4>
                        <!-- Modificadores -->
                        <div v-if="item.modifiers?.length" class="text-[10px] text-slate-500 mt-0.5 italic leading-tight">
                            {{ item.modifiers.map(m => m.name).join(', ') }}
                        </div>
                        <!-- Instrucciones -->
                        <div v-if="item.special_instructions" class="text-[10px] text-orange-600 mt-1 font-bold flex items-center gap-1">
                            <span class="text-xs">📝</span> {{ item.special_instructions }}
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-slate-800 whitespace-nowrap">${{ (
                            (parseFloat(item.price) + (item.modifiers || []).reduce((acc, m) => acc + parseFloat(m.price_adjustment || 0), 0)) * item.quantity
                        ).toFixed(2) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="updateQuantity(index, -1)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-200 font-bold">-</button>
                    <span class="font-bold text-sm min-w-[20px] text-center">{{ item.quantity }}</span>
                    <button @click="updateQuantity(index, 1)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-200 font-bold">+</button>
                    <button @click="cart.splice(index, 1)" class="ml-auto text-xs text-red-500 opacity-0 group-hover:opacity-100 transition-opacity font-bold hover:underline">Eliminar</button>
                </div>
            </li>
        </ul>
      </div>

      <!-- Totales y Realizar Pedido -->
      <div class="p-6 bg-slate-800 shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.3)] z-30">
        <div class="flex justify-between items-center mb-6">
           <span class="text-slate-300 font-medium text-lg">Total Pedido</span>
           <span class="text-white font-black text-4xl">${{ cartTotal.toFixed(2) }}</span>
        </div>
        
        <button 
            @click="placePOSOrder" 
            :disabled="!canCheckout" 
            :class="['w-full font-black py-5 rounded-2xl shadow-xl transition-all flex flex-col items-center justify-center gap-1', canCheckout ? 'bg-orange-500 hover:bg-orange-600 text-white transform hover:-translate-y-1 active:scale-95' : 'bg-slate-700 text-slate-500 cursor-not-allowed opacity-50']"
        >
            {{ isEditingOrder ? 'Agregar al Pedido' : 'Realizar Pedido' }}
        </button>
      </div>
    </div>

    <!-- Componente Modal de Producto -->
    <ProductModal 
        :is-open="isProductModalOpen"
        :product="selectedProduct"
        :show-image="false"
        :show-description="false"
        @close="isProductModalOpen = false"
        @add-to-cart="handleModalAddToCart"
    />

    <!-- Componente Modal de Mapa de Mesas -->
    <div v-if="isTableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 pb-20 sm:pb-6">
        <div @click="isTableModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="bg-slate-50 w-full max-w-4xl max-h-full rounded-3xl shadow-2xl overflow-hidden relative flex flex-col transform transition-all">
            <div class="bg-white px-6 py-4 border-b border-slate-200 flex justify-between items-center relative z-10 shrink-0">
                <div>
                    <h2 class="text-xl font-black text-slate-800">Mapa de Mesas</h2>
                    <p class="text-sm text-slate-500 font-medium">Selecciona una mesa para crear o añadir al pedido</p>
                </div>
                <button @click="isTableModalOpen = false" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                    <XCircle class="w-6 h-6 text-slate-400" />
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <div class="flex items-center gap-6 mb-5 text-xs font-bold text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Disponible</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span> Con pedido abierto</span>
                </div>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-4">
                    <button 
                        v-for="table in tables" 
                        :key="table.id"
                        @click="selectGridTable(table.id)"
                        :class="[
                            'aspect-square p-2 rounded-2xl font-black text-lg border-2 transition-all flex flex-col items-center justify-center gap-1 shadow-sm relative overflow-hidden cursor-pointer',
                            selectedTableId === table.id 
                                ? 'bg-green-500 border-green-600 text-white shadow-inner scale-95' 
                                : table.status !== 'AVAILABLE'
                                    ? 'bg-orange-50 border-orange-300 text-orange-600 hover:bg-orange-100 hover:border-orange-400 hover:scale-[1.02]'
                                    : 'bg-white border-green-200 text-green-700 hover:bg-green-50 hover:border-green-400 hover:scale-[1.02]'
                        ]"
                    >
                        <div v-if="table.status !== 'AVAILABLE'" class="absolute top-1 right-1">
                            <span class="text-[8px] bg-orange-500 text-white px-1.5 py-0.5 rounded-full font-black uppercase tracking-wider shadow-sm">Abierta</span>
                        </div>
                        <Utensils class="w-6 h-6 mb-1 opacity-50" />
                        <div class="flex flex-col items-center">
                            <span>{{ table.name.replace(/mesa /i, '') }}</span>
                            <span v-if="table.table_number" class="text-xs font-bold opacity-50 mt-0.5">#{{ table.table_number }}</span>
                        </div>
                        <span class="text-[10px] font-bold opacity-60 uppercase tracking-widest mt-1">{{ table.capacity }} Pax</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL DE PAGO EN EFECTIVO / CALCULADORA DE CAMBIO -->
    <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="isPaymentModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden relative flex flex-col transform transition-all animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="bg-slate-800 px-6 py-4 flex justify-between items-center text-white">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-500 rounded-lg">
                        <span class="text-xl">💵</span>
                    </div>
                    <h2 class="text-xl font-black">Cobro en Efectivo</h2>
                </div>
                <button @click="isPaymentModalOpen = false" class="p-2 hover:bg-slate-700 rounded-full transition-colors">
                    <XCircle class="w-6 h-6 text-slate-400" />
                </button>
            </div>

            <div class="flex flex-col md:flex-row h-[450px]">
                <!-- Teclado y Atajos -->
                <div class="w-full md:w-3/5 p-6 border-r border-slate-100 flex flex-col">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Pagos Rápidos</label>
                    <div class="grid grid-cols-3 gap-3 mb-6">
                        <button 
                            v-for="amount in fastAmounts" 
                            :key="amount"
                            @click="setFastAmount(amount)"
                            class="py-4 bg-slate-50 border border-slate-200 rounded-2xl font-black text-slate-700 hover:bg-slate-800 hover:text-white hover:border-slate-800 transition-all shadow-sm"
                        >
                            ${{ amount }}
                        </button>
                        <button 
                            @click="setExactPayment"
                            class="py-4 bg-orange-50 border border-orange-200 rounded-2xl font-black text-orange-600 hover:bg-orange-500 hover:text-white transition-all shadow-sm flex flex-col items-center justify-center leading-tight"
                        >
                            <span class="text-[10px] uppercase">Exacto</span>
                            ${{ Number(orderToCharge?.total_amount).toFixed(0) }}
                        </button>
                    </div>

                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Monto Personalizado</label>
                    <div class="relative mb-auto">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-2xl font-black text-slate-400">$</span>
                        <input 
                            v-model.number="amountReceived" 
                            type="number" 
                            autofocus
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-6 pl-12 pr-6 text-4xl font-black text-slate-800 focus:outline-none focus:border-green-500 transition-all"
                            placeholder="0.00"
                        >
                    </div>
                </div>

                <!-- Resumen y Cambio -->
                <div class="w-full md:w-2/5 p-6 bg-slate-50/50 flex flex-col">
                    <div class="space-y-6 mb-auto">
                        <div class="flex justify-between items-center pb-4 border-b border-slate-200">
                            <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Total Orden</span>
                            <span class="text-xl font-black text-slate-800">${{ Number(orderToCharge?.total_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-4 border-b border-slate-200">
                            <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Recibido</span>
                            <span class="text-xl font-black text-blue-600">${{ Number(amountReceived || 0).toFixed(2) }}</span>
                        </div>
                        
                        <div class="pt-4 text-center">
                            <span class="font-bold text-slate-400 uppercase text-[10px] tracking-widest block mb-1">Cambio a Entregar</span>
                            <div :class="['text-5xl font-black transition-colors', cashChange > 0 ? 'text-green-600' : 'text-slate-300']">
                                ${{ cashChange.toFixed(2) }}
                            </div>
                        </div>
                    </div>

                    <button 
                        @click="completeCashPayment"
                        :disabled="!canCompletePayment"
                        :class="[
                            'w-full py-5 rounded-2xl font-black text-xl shadow-xl transition-all transform flex items-center justify-center gap-2',
                            canCompletePayment 
                                ? 'bg-green-500 text-white hover:bg-green-600 hover:-translate-y-1 active:scale-95' 
                                : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                        ]"
                    >
                        <CheckCircle2 class="w-6 h-6" /> Completar Pago
                    </button>
                    <p v-if="!canCompletePayment && amountReceived > 0" class="text-center mt-3 text-red-500 text-xs font-bold animate-pulse">
                        Monto insuficiente
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- TICKET DE IMPRESIÓN (OCULTO EN PANTALLA) -->
    <div v-if="orderToPrint" id="print-ticket" :class="['print-only', printerWidth === '58' ? 'w-58' : 'w-80']">
        <div class="ticket-header">
            <div class="business-title-row">
                <img v-if="currentCompany?.logo_url" :src="currentCompany.logo_url" class="ticket-logo-small" />
                <h1 class="business-name">{{ currentCompany?.name || 'ComidaToGo' }}</h1>
            </div>
            <p class="business-info" v-if="currentCompany?.whatsapp_number">Tel: {{ currentCompany.whatsapp_number }}</p>
        </div>

        <div class="ticket-divider">*******************************</div>

        <div class="ticket-info">
            <p><strong>FOLIO: #{{ orderToPrint.id }}</strong></p>
            <p>Fecha: {{ new Date(orderToPrint.created_at).toLocaleString() }}</p>
            <p>Cliente: {{ orderToPrint.customer_name }}</p>
            <p v-if="orderToPrint.customer_phone">Tel: {{ orderToPrint.customer_phone }}</p>
            <p>Tipo: {{ orderToPrint.order_type === 'DELIVERY' ? 'DOMICILIO' : (orderToPrint.order_type === 'PICKUP' ? 'RECOGER' : 'LOCAL') }}</p>
            <p v-if="orderToPrint.table_number">Mesa: {{ orderToPrint.table_number }}</p>
            
            <!-- Dirección para domicilios -->
            <div v-if="orderToPrint.order_type === 'DELIVERY' && orderToPrint.customer_address" class="delivery-address-box">
                <p class="address-label">DIRECCIÓN DE ENTREGA:</p>
                <p class="address-text">{{ orderToPrint.customer_address }}</p>
                <div v-if="orderToPrint.customer_references" class="address-refs">
                    <strong>Ref:</strong> {{ orderToPrint.customer_references }}
                </div>
            </div>
        </div>

        <div class="ticket-divider">-------------------------------</div>

        <table class="ticket-table">
            <thead>
                <tr>
                    <th class="qty">Cant</th>
                    <th class="desc">Producto</th>
                    <th class="price">Precio</th>
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="item in orderToPrint.items" :key="item.id">
                    <tr class="item-row">
                        <td>{{ item.quantity }}</td>
                        <td>{{ item.product_name }}</td>
                        <td>${{ Number(item.unit_price).toFixed(2) }}</td>
                        <td>${{ (item.quantity * item.unit_price).toFixed(2) }}</td>
                    </tr>
                    <tr v-if="item.modifiers" class="modifier-row">
                        <td></td>
                        <td colspan="3" class="modifiers-text italic">+ {{ item.modifiers }}</td>
                    </tr>
                </template>
            </tbody>
        </table>

        <div class="ticket-divider">-------------------------------</div>

        <div class="ticket-total">
            <div class="total-row large">
                <span>TOTAL:</span>
                <span>${{ Number(orderToPrint.total_amount).toFixed(2) }}</span>
            </div>
        </div>

        <div class="ticket-footer">
            <p>¡Gracias por su preferencia!</p>
            <p class="promo-text">Buscanos en ComidaToGo.com.mx</p>
            <p>{{ currentCompany?.name }}</p>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import { 
  Truck, ShoppingBag, Utensils, Phone, MapPin, Clock, 
  ArrowRight, XCircle, CheckCircle2, User, Hash, Plus, Minus, Printer
} from 'lucide-vue-next'
import ProductModal from '../../components/ProductModal.vue'
import { useToast } from '../../composables/useToast'
import { useDialogStore } from '../../stores/dialog'

const toast = useToast()
const dialog = useDialogStore()
const auth = useAuthStore()
const companyId = auth.user?.company_id || 1

// Estado de la Vista
const activeView = ref('MENU') // MENU, TABLES, ORDERS_WEB, ORDERS_LOCAL, SHIFT
const loading = ref(true)
const error = ref(null)
const now = ref(Date.now())

// Datos del Servidor
const categories = ref([])
const products = ref([])
const tables = ref([])
const webOrders = ref([])
const currentCompany = ref(null)
const activeShiftId = ref(null)
const isPaymentModalOpen = ref(false)
const orderToCharge = ref(null)
const amountReceived = ref(0)
const fastAmounts = [20, 50, 100, 200, 500]

// Configuración Impresora
const printerWidth = computed(() => {
    return currentCompany.value?.printer_width || '80'
})
const orderToPrint = ref(null)

const cashChange = computed(() => {
    if (!orderToCharge.value) return 0
    const total = parseFloat(orderToCharge.value.total_amount)
    const received = parseFloat(amountReceived.value) || 0
    return Math.max(0, received - total)
})

const canCompletePayment = computed(() => {
    if (!orderToCharge.value) return false
    return amountReceived.value >= parseFloat(orderToCharge.value.total_amount)
})

// Timer config
const timerGreen = ref(10)
const timerYellow = ref(20)

// Sound tracking
const lastPendingCount = ref(0)
const lastReadyCount = ref(0)

// Controles de Búsqueda y Filtros
const searchQuery = ref('')
const selectedCategory = ref(null)

const cart = ref([])
const cartType = ref('TAKEAWAY')
const selectedTableId = ref(null)
const customerName = ref('')
const isEditingOrder = ref(false)
const editingOrderId = ref(null)

// Mapa de Mesas Modal
const isTableModalOpen = ref(false)

const openTableModal = async () => {
    try {
        const resTables = await axios.get(`/api.php/tables?company_id=${companyId}`)
        tables.value = resTables.data
    } catch (e) {
        console.error("Error fetching tables", e)
    }
    isTableModalOpen.value = true
}

const selectGridTable = (id) => {
    const table = tables.value.find(t => t.id === id)
    if (table && table.status === 'OCCUPIED') {
        // Buscar cualquier orden activa (no finalizada) para esta mesa
        const activeOrder = webOrders.value.find(o => 
            o.table_id && parseInt(o.table_id) === parseInt(id) && 
            !['COMPLETED', 'CANCELLED', 'REJECTED'].includes(o.status)
        )
        
        if (activeOrder) {
            isEditingOrder.value = true
            editingOrderId.value = activeOrder.id
            selectedTableId.value = id
            cartType.value = 'DINE_IN'
            toast.info(`Modo Edición: Agregando productos a la Orden #${activeOrder.id}`)
        } else {
            // Si la mesa está ocupada pero no encontramos la orden en el top 50, 
            // permitimos seleccionarla pero como pedido nuevo (o podrías manejarlo de otra forma)
            selectedTableId.value = id
            cartType.value = 'DINE_IN'
            isEditingOrder.value = false
            editingOrderId.value = null
        }
    } else {
        selectedTableId.value = id
        cartType.value = 'DINE_IN'
        isEditingOrder.value = false
        editingOrderId.value = null
    }
    isTableModalOpen.value = false
}

// Modal de Personalización
const isProductModalOpen = ref(false)
const selectedProduct = ref(null)

// Polling and timers
let pollInterval = null
let timerInterval = null

// Alert sounds
const playSound = (type = 'new') => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.connect(gain)
        gain.connect(ctx.destination)
        gain.gain.value = 0.25
        
        if (type === 'new') {
            osc.frequency.value = 800
            osc.start()
            setTimeout(() => { osc.frequency.value = 1000 }, 150)
            setTimeout(() => { osc.frequency.value = 1200 }, 300)
            setTimeout(() => { osc.stop(); ctx.close() }, 500)
        } else if (type === 'ready') {
            osc.frequency.value = 1200
            osc.start()
            setTimeout(() => { osc.frequency.value = 1400 }, 100)
            setTimeout(() => { osc.stop(); ctx.close() }, 300)
        }
    } catch (e) { console.warn('Audio not available') }
}

// Timer helpers
const formatElapsed = (dateStr) => {
    if (!dateStr) return '00:00'
    const totalSec = Math.max(0, Math.floor((now.value - new Date(dateStr).getTime()) / 1000))
    const h = Math.floor(totalSec / 3600)
    const m = Math.floor((totalSec % 3600) / 60)
    const s = totalSec % 60
    
    if (h > 0) {
        return `${h}h ${String(m).padStart(2, '0')}m`
    }
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

const formatTime = (dateStr) => {
    if (!dateStr || dateStr.includes('0000-00-00')) return ''
    try {
        const timePart = dateStr.includes(' ') ? dateStr.split(' ')[1] : dateStr
        let [h, m] = timePart.split(':')
        h = parseInt(h)
        const ampm = h >= 12 ? 'PM' : 'AM'
        h = h % 12
        h = h ? h : 12
        return `${h}:${m} ${ampm}`
    } catch (e) { return dateStr }
}

const getTimerColor = (dateStr) => {
    if (!dateStr) return 'text-green-600 bg-green-100'
    const mins = (now.value - new Date(dateStr).getTime()) / 60000
    if (mins >= timerYellow.value) return 'text-red-600 bg-red-100'
    if (mins >= timerGreen.value) return 'text-yellow-600 bg-yellow-100'
    return 'text-green-600 bg-green-100'
}

// Computed
const filteredProducts = computed(() => {
    let result = products.value
    if (selectedCategory.value) result = result.filter(p => p.category_id === selectedCategory.value)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(p => p.name.toLowerCase().includes(query))
    }
    return result
})

const cartTotal = computed(() => {
    return cart.value.reduce((total, item) => {
        let itemPrice = parseFloat(item.price)
        if (item.modifiers && Array.isArray(item.modifiers)) {
            item.modifiers.forEach(m => {
                itemPrice += parseFloat(m.price_adjustment || 0)
            })
        }
        return total + (itemPrice * item.quantity)
    }, 0)
})

const canCheckout = computed(() => {
    if (cart.value.length === 0) return false
    if (cartType.value === 'DINE_IN' && !selectedTableId.value) return false
    if (cartType.value === 'TAKEAWAY' && !customerName.value.trim()) return false
    return true
})

const translateStatus = (status) => {
    const translations = {
        'PENDING': 'Pendiente',
        'ACCEPTED': 'En preparación',
        'PREPARING': 'En preparación',
        'READY': 'Listo',
        'COMPLETED': 'Completado',
        'CANCELLED': 'Cancelado',
        'REJECTED': 'Rechazado'
    }
    return translations[status] || status
}


const pendingWebOrders = computed(() => webOrders.value.filter(o => 
    o.status === 'PENDING' && (o.order_type === 'PICKUP' || o.order_type === 'DELIVERY')
))

const preparingWebOrders = computed(() => webOrders.value.filter(o => 
    (o.order_type === 'PICKUP' || o.order_type === 'DELIVERY') && 
    ['ACCEPTED', 'PREPARING'].includes(o.status)
))

const readyWebOrders = computed(() => webOrders.value.filter(o => 
    (o.order_type === 'PICKUP' || o.order_type === 'DELIVERY') && 
    o.status === 'READY'
))

const preparingLocalOrders = computed(() => webOrders.value.filter(o => 
    (o.order_type === 'DINE_IN' || o.order_type === 'TAKEAWAY') && 
    ['PENDING', 'ACCEPTED', 'PREPARING'].includes(o.status)
))

const readyLocalOrders = computed(() => webOrders.value.filter(o => 
    (o.order_type === 'DINE_IN' || o.order_type === 'TAKEAWAY') && 
    o.status === 'READY'
))

// --- METHODS ---
const loadInitialData = async () => {
    loading.value = true
    try {
        const resTenant = await axios.get(`/api.php/tenant/${companyId}`)
        currentCompany.value = resTenant.data
        if(resTenant.data.menu) {
            categories.value = resTenant.data.menu
            let allProds = []
            categories.value.forEach(cat => {
                if(cat.products) allProds.push(...cat.products)
            })
            // Normalizar URLs de imágenes para producción/subdirectorios
            products.value = allProds.map(p => {
                if (p.image_url && !p.image_url.startsWith('http')) {
                    const cleanPath = p.image_url.startsWith('/') ? p.image_url.slice(1) : p.image_url;
                    p.image_url = import.meta.env.BASE_URL + cleanPath;
                }
                return p
            })
        }
        // Load timer config
        if (resTenant.data.schedule_config) {
            const config = typeof resTenant.data.schedule_config === 'string' ? JSON.parse(resTenant.data.schedule_config) : resTenant.data.schedule_config
            if (config.timer_green) timerGreen.value = config.timer_green
            if (config.timer_yellow) timerYellow.value = config.timer_yellow
        }

        const resCaja = await axios.get(`/api.php/cash-register/status?company_id=${companyId}`)
        if (resCaja.data.has_active_shift) {
            activeShiftId.value = resCaja.data.shift.id
        }

        const resTables = await axios.get(`/api.php/tables?company_id=${companyId}`)
        tables.value = resTables.data

    } catch (err) {
        console.error(err)
        error.value = "Error al cargar datos del POS."
    } finally {
        loading.value = false
    }
}

const refreshOrders = async () => {
    try {
        const res = await axios.get(`/api.php/orders?company_id=${companyId}`)
        if (Array.isArray(res.data)) {
            // Check for new PENDING orders
            const newPending = res.data.filter(o => o.status === 'PENDING').length
            if (newPending > lastPendingCount.value && lastPendingCount.value > 0) {
                playSound('new')
                // Auto-switch to WEB tab when new order arrives (only if not initial load)
                if (activeView.value === 'MENU') activeView.value = 'ORDERS_WEB'
            }
            lastPendingCount.value = newPending

            // Check for new READY orders
            const newReady = res.data.filter(o => o.status === 'READY').length
            if (newReady > lastReadyCount.value && lastReadyCount.value >= 0) {
                playSound('ready')
            }
            lastReadyCount.value = newReady

            webOrders.value = res.data
        }
    } catch (e) { console.error(e) }
}

const addToCart = (product) => {
    selectedProduct.value = product
    isProductModalOpen.value = true
}

const handleModalAddToCart = (data) => {
    const { product, quantity, modifiers, special_instructions } = data
    
    // Buscar si existe exactamente el mismo producto con exactamente los mismos modificadores e instrucciones
    const existingIndex = cart.value.findIndex(item => {
        if (item.id !== product.id) return false
        if (item.special_instructions !== special_instructions) return false
        
        const itemMods = (item.modifiers || []).map(m => m.id).sort().join(',')
        const newMods = (modifiers || []).map(m => m.id).sort().join(',')
        return itemMods === newMods
    })

    if (existingIndex !== -1) {
        cart.value[existingIndex].quantity += quantity
    } else {
        cart.value.push({
            id: product.id,
            name: product.name,
            price: product.price,
            quantity: quantity,
            modifiers: modifiers,
            special_instructions: special_instructions
        })
    }
}

const updateQuantity = (index, delta) => {
    const item = cart.value[index]
    item.quantity += delta
    if (item.quantity <= 0) cart.value.splice(index, 1)
}

// CAJERA acepta pedido → va a cocina
const acceptOrder = async (orderId) => {
    try {
        await axios.put(`/api.php/orders/${orderId}`, { status: 'ACCEPTED' })
        await refreshOrders()
    } catch (e) { toast.error("Error al aceptar pedido") }
}

const rejectOrder = async (orderId) => {
    const reason = await dialog.prompt({
        title: '¿Rechazar Pedido?',
        message: 'Por favor, escribe el motivo del rechazo para notificar al cliente.',
        placeholder: 'Ej. Se agotaron los insumos / Fuera de zona...',
        confirmText: 'Rechazar Pedido',
        cancelText: 'Volver'
    })
    
    if (!reason || reason.trim() === '') return
    
    try {
        await axios.put(`/api.php/orders/${orderId}`, { 
            status: 'REJECTED',
            rejection_reason: reason
        })
        toast.success(`Pedido #${orderId} rechazado`)
        await refreshOrders()
    } catch (e) { toast.error("Error al rechazar pedido") }
}

// Cobrar pedido web listo
const chargeWebOrder = async (order, method) => {
    if (method === 'CASH') {
        if (!activeShiftId.value) {
            toast.error("¡Abre la caja primero para cobrar en efectivo!")
            return
        }
        orderToCharge.value = order
        amountReceived.value = 0
        isPaymentModalOpen.value = true
        return
    }

    const confirmed = await dialog.confirm({
        title: 'Confirmar Cobro',
        message: `¿Cobrar pedido #${order.id} por $${Number(order.total_amount).toFixed(2)} con ${method === 'CASH' ? 'Efectivo' : 'Tarjeta'}?`,
        confirmText: 'Cobrar Ahora',
        cancelText: 'Cancelar'
    })

    if (!confirmed) return
    
    try {
        await axios.put(`/api.php/orders/${order.id}`, {
            status: 'COMPLETED',
            payment_method: method,
            cash_register_shift_id: method === 'CASH' ? activeShiftId.value : null
        })
        toast.success(`Pedido #${order.id} cobrado ($${Number(order.total_amount).toFixed(2)} - ${method})`)
        await refreshOrders()
    } catch (e) { toast.error("Error al cobrar pedido") }
}

const completeCashPayment = async () => {
    if (!canCompletePayment.value) return
    
    try {
        const order = orderToCharge.value
        await axios.put(`/api.php/orders/${order.id}`, {
            status: 'COMPLETED',
            payment_method: 'CASH',
            cash_register_shift_id: activeShiftId.value
        })
        toast.success(`Pago completado. Cambio: $${cashChange.value.toFixed(2)}`)
        isPaymentModalOpen.value = false
        await refreshOrders()
    } catch (e) { 
        toast.error("Error al procesar el pago")
    }
}

const setFastAmount = (amount) => {
    amountReceived.value = amount
}

const setExactPayment = () => {
    if (orderToCharge.value) {
        amountReceived.value = parseFloat(orderToCharge.value.total_amount)
    }
}

// Enviar pedido POS directamente a cocina
const placePOSOrder = async () => {
    if (!canCheckout.value) return

    const itemsPayload = cart.value.map(i => ({
        product_id: i.id,
        quantity: i.quantity,
        price: i.price,
        modifiers: (i.modifiers || []).map(m => m.id),
        special_instructions: i.special_instructions || ''
    }))

    if (isEditingOrder.value && editingOrderId.value) {
        try {
            await axios.post(`api.php/orders/${editingOrderId.value}/items`, { items: itemsPayload })
            toast.success(`Productos agregados a la Orden #${editingOrderId.value} 🔥`)
            resetPOSState()
        } catch (err) {
            toast.error("Error al actualizar pedido: " + (err.response?.data?.message || err.message))
        }
        return
    }

    const orderPayload = {
        company_id: companyId,
        customer_name: cartType.value === 'DINE_IN' ? tables.value.find(t => t.id === selectedTableId.value)?.name : customerName.value,
        order_type: cartType.value,
        table_id: selectedTableId.value,
        total_amount: cartTotal.value,
        status: 'ACCEPTED',
        payment_method: 'PENDING',
        items: itemsPayload
    }

    try {
        await axios.post('api.php/orders', orderPayload)
        
        if (cartType.value === 'DINE_IN' && selectedTableId.value) {
             await axios.put(`/api.php/tables/${selectedTableId.value}/status`, { status: 'OCCUPIED' })
        }

        toast.success(`Encargo enviado a cocina 🔥`)
        resetPOSState()

    } catch (err) {
        toast.error("Error al enviar pedido: " + (err.response?.data?.message || err.message))
    }
}

const resetPOSState = () => {
    cart.value = []
    customerName.value = ''
    selectedTableId.value = null
    isEditingOrder.value = false
    editingOrderId.value = null
    loadInitialData()
    refreshOrders()
    activeView.value = 'ORDERS_LOCAL'
}

// Abrir diálogo de cobro para una orden local activa
const showChargeOrder = async (order) => {
    const confirmed = await dialog.confirm({
        title: 'Seleccionar Método de Pago',
        message: `¿Cómo desea pagar el cliente la orden #${order.id} por $${Number(order.total_amount).toFixed(2)}?`,
        confirmText: 'Efectivo 💵',
        cancelText: 'Cancelar'
    })

    if (confirmed) {
        await processCharge(order, 'CASH')
    } else {
        // En un sistema real aquí abriríamos un sub-modal con Tarjeta/Transfer.
        // Por simplicidad, usemos otro diálogo si cancela o una UI dedicada.
        // Vamos a implementar una lógica de "Tarjeta" si el usuario elige una opción secundaria en el futuro,
        // pero por ahora usemos el flujo de chargeWebOrder que ya está pulido.
    }
}

// Reutilizamos chargeWebOrder para órdenes locales al dar clic en botones
const processCharge = async (order, method) => {
    await chargeWebOrder(order, method)
}

const printTicket = async (order) => {
    orderToPrint.value = order
    // Esperar a que Vue renderice el template oculto
    setTimeout(() => {
        window.print()
        // Limpiamos después de imprimir (o cancelar)
        setTimeout(() => { orderToPrint.value = null }, 500)
    }, 100)
}


onMounted(async () => {
    await loadInitialData()
    await refreshOrders()
    pollInterval = setInterval(refreshOrders, 6000)
    timerInterval = setInterval(() => { now.value = Date.now() }, 1000)
})

onUnmounted(() => {
    clearInterval(pollInterval)
    clearInterval(timerInterval)
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* ESTILOS DE IMPRESIÓN SE MOVIERON A BLOQUE GLOBAL ABAJO */
</style>

<style>
/* ESTILOS GLOBALES PARA IMPRESIÓN (SIN SCOPED) */
@media screen {
    .print-only { 
        display: none !important; 
        visibility: hidden !important;
        position: fixed !important;
        top: -10000px !important;
    }
}

@media print {
    @page {
        margin: 0 !important;
        size: auto;
    }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }

    body * { 
        visibility: hidden !important; 
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    #print-ticket, #print-ticket * { 
        visibility: visible !important; 
    }
    
    #print-ticket {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
        padding: 4mm !important;
        color: black !important;
        background: white !important;
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 12px !important;
        line-height: 1.2 !important;
        display: block !important;
        box-sizing: border-box !important;
    }
    
    .w-80 { width: 80mm !important; max-width: 80mm !important; }
    .w-58 { width: 58mm !important; max-width: 58mm !important; }
    
    .ticket-header { text-align: center; margin-bottom: 4mm; }
    .business-title-row { display: flex; align-items: center; justify-content: center; gap: 2mm; margin-bottom: 1mm; }
    .ticket-logo-small { width: 8mm; height: 8mm; object-fit: contain; filter: grayscale(100%) contrast(1.5); }
    .business-name { font-size: 16px !important; font-weight: 900; margin: 0; text-transform: uppercase; line-height: 1; }
    .business-address, .business-info { font-size: 11px !important; margin: 0; line-height: 1.1; }
    
    .ticket-divider { text-align: center; margin: 2mm 0; letter-spacing: -1px; }
    .ticket-info { font-size: 11px !important; }
    .ticket-info p { margin: 0.5mm 0; }
    
    .ticket-table { width: 100%; border-collapse: collapse; margin: 2mm 0; table-layout: fixed; }
    .ticket-table th { text-align: left; border-bottom: 1px dashed black; font-size: 10px !important; padding-bottom: 1mm; }
    .ticket-table td { font-size: 12px !important; padding: 1.5mm 0; vertical-align: top; word-wrap: break-word; }
    
    .qty { width: 10%; }
    .desc { width: 40%; }
    .price { width: 25%; text-align: right; }
    .total { width: 25%; text-align: right; }
    
    .modifier-row td { font-size: 10px !important; padding-top: 0; padding-bottom: 1.5mm; color: #333 !important; }
    
    .ticket-total { margin-top: 4mm; text-align: right; border-top: 1px solid black; padding-top: 2mm; }
    .total-row { display: flex; justify-content: space-between; font-weight: 900; }
    .total-row.large { font-size: 20px !important; margin-bottom: 1mm; }
    .payment-method { font-size: 11px !important; font-style: italic; }
    .promo-text { font-weight: 900; color: black; margin: 1mm 0; border: 1px solid black; padding: 1mm; display: inline-block; }
    
    .ticket-footer { text-align: center; margin-top: 2mm; font-size: 11px !important; border-top: 1px dashed #666; padding-top: 4mm; padding-bottom: 10mm; }
}
</style>
