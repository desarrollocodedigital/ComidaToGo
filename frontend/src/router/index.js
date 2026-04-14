import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import TenantView from '../views/TenantView.vue'

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeView
    },
    {
        path: '/buscar',
        name: 'search',
        component: HomeView
    },
    {
        path: '/checkout',
        name: 'checkout',
        component: () => import('../views/CheckoutView.vue')
    },
    // --- ADMIN / OWNER ROUTES ---
    {
        path: '/admin/dashboard',
        name: 'dashboard',
        component: () => import('../views/admin/AdminDashboard.vue'),
        meta: { requiresAuth: true, roles: ['OWNER'] }
    },
    {
        path: '/admin/settings',
        name: 'settings',
        component: () => import('../views/RestaurantSettingsView.vue'),
        meta: { requiresAuth: true, roles: ['OWNER'] }
    },
    {
        path: '/admin/menu',
        name: 'menu-manager',
        component: () => import('../views/admin/MenuManagerView.vue'),
        meta: { requiresAuth: true, roles: ['OWNER'] }
    },
    {
        path: '/admin/team',
        name: 'team-manager',
        component: () => import('../views/admin/UserManagementView.vue'),
        meta: { requiresAuth: true, roles: ['OWNER'] }
    },
    // --- ROLE-BASED ROUTES ---
    {
        path: '/cocina',
        name: 'kitchen',
        component: () => import('../views/KitchenDashboard.vue'),
        meta: { requiresAuth: true, roles: ['OWNER', 'KITCHEN'] }
    },
    {
        path: '/pos',
        name: 'pos',
        component: () => import('../views/pos/WebPOSView.vue'),
        meta: { requiresAuth: true, roles: ['OWNER', 'CASHIER', 'WAITER'] }
    },
    {
        path: '/caja',
        name: 'caja',
        component: () => import('../views/pos/CashRegisterView.vue'),
        meta: { requiresAuth: true, roles: ['OWNER', 'CASHIER'] }
    },
    // --- DEMO ROUTES (sin auth para desarrollo) ---
    {
        path: '/demo',
        name: 'demo-menu',
        component: () => import('../views/DemoMenuView.vue')
    },
    {
        path: '/demo/pos',
        name: 'demo-pos',
        component: () => import('../views/pos/WebPOSView.vue')
    },
    {
        path: '/demo/caja',
        name: 'demo-caja',
        component: () => import('../views/pos/CashRegisterView.vue')
    },
    {
        path: '/demo/analytics',
        name: 'demo-analytics',
        component: () => import('../views/admin/AnalyticsDashboard.vue')
    },
    // --- PUBLIC ROUTES ---
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/LoginView.vue')
    },
    {
        path: '/pedido/:id',
        name: 'order-status',
        component: () => import('../views/OrderStatusView.vue')
    },
    {
        path: '/registro',
        name: 'register',
        component: () => import('../views/RegisterView.vue')
    },
    {
        path: '/:slug',
        name: 'tenant',
        component: TenantView
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Role-based guard: OWNER can access everything, staff only their modules
router.beforeEach((to, from, next) => {
    let user = null
    try {
        user = JSON.parse(localStorage.getItem('user'))
    } catch (e) {
        localStorage.removeItem('user')
    }

    if (to.meta.requiresAuth) {
        if (!user) {
            next('/login')
        } else if (to.meta.roles && !to.meta.roles.includes(user.role)) {
            alert("No tienes permiso para ver esta página")
            // Redirect staff to their home
            if (user.role === 'KITCHEN') next('/cocina')
            else if (user.role === 'CASHIER') next('/pos')
            else if (user.role === 'WAITER') next('/pos')
            else next('/')
        } else {
            next()
        }
    } else {
        next()
    }
})

export default router
