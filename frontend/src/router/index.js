import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import TenantView from '../views/TenantView.vue'
import ExpenseCategoryView from '../views/admin/ExpenseCategoryView.vue'

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
        meta: { requiresAuth: true, roles: ['OWNER', 'CASHIER', 'KITCHEN', 'WAITER'] }
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
        meta: { requiresAuth: true, roles: ['OWNER', 'KITCHEN'] }
    },
    {
        path: '/admin/team',
        name: 'team-manager',
        component: () => import('../views/admin/UserManagementView.vue'),
        meta: { requiresAuth: true, roles: ['OWNER'] }
    },
    {
        path: '/admin/expense-categories',
        name: 'expense-categories',
        component: ExpenseCategoryView,
        meta: { requiresAuth: true, role: 'OWNER' }
    },
    {
        path: '/admin/tables',
        name: 'table-manager',
        component: () => import('../views/admin/TableManagementView.vue'),
        meta: { requiresAuth: true, roles: ['OWNER'] }
    },
    {
        path: '/mis-pedidos',
        name: 'my-orders',
        component: () => import('../views/MyOrdersView.vue')
    },
    // --- ROLE-BASED ROUTES ---
    {
        path: '/cocina',
        name: 'kitchen',
        component: () => import('../views/KitchenDashboard.vue'),
        meta: { requiresAuth: true, roles: ['OWNER', 'KITCHEN', 'WAITER'] }
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

import { useDialogStore } from '../stores/dialog'

const router = createRouter({
    history: createWebHistory('/Dev/COMIDATOGO/'),
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
            const dialog = useDialogStore()
            dialog.alert({
                title: 'Acceso Restringido',
                message: 'Tu cuenta no tiene los permisos suficientes para ingresar a este módulo.',
                confirmText: 'Entendido',
                type: 'warning'
            })
            
            // Redirect staff to dashboard if they are staff
            if (['KITCHEN', 'CASHIER', 'WAITER'].includes(user.role)) next('/admin/dashboard')
            else next('/')
        } else {
            next()
        }
    } else {
        next()
    }
})

export default router
