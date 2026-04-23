## [2026-04-23] - Restricción por Horario y Flujo de Geolocalización

### 📝 Resumen de Cambios
- Implementación de restricciones estrictas de pedidos cuando un negocio está fuera de su horario de atención.
- Nuevo flujo de consentimiento de geolocalización para mejorar la transparencia y experiencia del usuario (UX).
- Refactorización del motor de estados de apertura para centralizar mensajes amigables desde el backend.
- Nuevo sistema de **Onboarding de Usuario** para captura obligatoria de teléfono y dirección con soporte para geocodificación de coordenadas.

### 🚀 Detalle Técnico
- **Backend (PHP)**:
    - [x] `Company.php`: Refactorización de `getIsOpenNow` para devolver objetos de estado detallados.
    - [x] `OrderController.php`: Validación de seguridad en la creación de pedidos basada en el horario del negocio.
- **Frontend (Vue 3)**:
    - [x] `OnboardingModal.vue`: Nuevo componente global para captura de datos post-registro con soporte para GPS y geocodificación inversa.
    - [x] `auth.js (Store)`: Centralización de `userState`, guardado de estado en BD y sincronización reactiva con `localStorage`.
    - [x] `HomeView.vue`: Integración de modal de consentimiento, respaldo reactivo de coordenadas de perfil para habilitar métricas de distancia/tiempo, geocodificación inversa como "fallback" para perfiles antiguos (legacy) y URLs codificadas (`encodeURIComponent`).
    - [x] `App.vue`: Inclusión global de `OnboardingModal`.
    - [x] `TenantView.vue`: Banner de advertencia de negocio cerrado.
    - [x] `ProductModal.vue`: Bloqueo de botón "Agregar" y advertencia visual.
    - [x] `CheckoutView.vue`: Verificación reactiva de estado de apertura antes de confirmar pedido.

---

## [2026-04-21] - Optimización de UI y Estados en Tiempo Real

### 📝 Resumen de Cambios
- Optimización de visualización de tiempos de entrega en el Marketplace (>60 min = Horas).
- Corrección del estado de apertura dinámico en tiempo real para todos los negocios.
- Implementación de bloqueo de scroll y desenfoque (blur) en modales.
- Despliegue masivo de Skeleton Loaders en POS, Gestor de Menú y Configuraciones.

### 🚀 Detalle Técnico
- **Frontend (Vue 3)**:
    - [x] `HomeView.vue`: Lógica de tiempo formateado y limpieza de scroll.
    - [x] `TenantView.vue`: Sincronización de estado `is_open` real-time y bloqueo de scroll.
    - [x] `WebPOSView.vue`: Skeleton loaders en imágenes del menú.
    - [x] `MenuManagerView.vue`: Skeleton loaders en lista y modal de edición.
    - [x] `RestaurantSettingsView.vue`: Skeleton loaders en banner y logo.
    - [x] `ProductModal.vue`: Implementación de `backdrop-blur-sm`.
- **Backend (PHP)**:
    - [x] `Company.php`: Refactorización de `findBySlugOrId` para recalcular disponibilidad dinámicamente.

---

## [2026-04-20] - Sistema de Calificaciones (Reviews)

### 📝 Resumen de Cambios
- Implementación completa del sistema de calificaciones de negocios basado en la experiencia del cliente y visualización de promedios en el Marketplace.
- Modernización del Control de Caja: Humanización de responsables del turno, migración total a diálogos premium y soporte para reportes multilineales.
- Automatización Contable: Registro automático de faltantes de caja como gastos para garantizar la precisión de las analíticas de utilidad.
- Optimización POS: Centralización de configuración de impresora en base de datos y nueva calculadora de cambio para pagos en efectivo.
- Autenticación Social: Integración completa de **Google Login** en el sistema para agilizar el acceso y registro de clientes.

### 💾 Scripts de Base de Datos (SQL)
```sql
-- Sistema de Calificaciones
-- ALTER TABLE companies ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00;

-- Gestión de Pedidos - Motivo de Rechazo
-- ALTER TABLE orders ADD COLUMN rejection_reason VARCHAR(255) DEFAULT NULL;

-- Configuración POS - Ancho de Impresora
-- ALTER TABLE companies ADD COLUMN printer_width VARCHAR(10) DEFAULT '80';
```

### 🚀 Nuevas Funcionalidades / Refactorizaciones
- **Backend (MVC)**:
    - [x] Modelo `Review.php`: Gestión de reseñas y recálculo de promedios.
    - [x] Controlador `ReviewController.php`: API para recepción de calificaciones.
    - [x] Extensión `Order.php`: Soporte para `has_review` y persistencia de `rejection_reason`.
    - [x] Extensión `Analytics.php`: Nuevo método para distribución de estrellas.
- **Frontend (Vue 3)**:
    - [x] `DialogProvider.vue`: Extensión del sistema de diálogos para soportar **prompts** (entrada de texto).
    - [x] `WebPOSView.vue`: Impresión de tickets de venta con soporte para 58mm/80mm y flujo de rechazo con motivo.
    - [x] `MyOrdersView.vue`: Modal de calificación y visualización del motivo de rechazo para el cliente.
    - [x] `AnalyticsDashboard.vue`: Nueva sección de "Satisfacción del Cliente" con gráficas de distribución.
    - [x] `RestaurantSettingsView.vue`: Centralización de la configuración de impresora (POS) en la base de datos.
    - [x] `WebPOSView.vue (Refactor)`: Implementación de calculadora de cambio para pagos en efectivo y optimización de diseño de ticket térmico.
    - [x] `CashRegisterView.vue`: Humanización del responsable del turno (Nombre vs ID) y migración total a diálogos premium.
    - [x] `DialogProvider.vue (Refactor)`: Soporte para mensajes multilínea (`whitespace-pre-line`).
    - [x] `CashRegister.php (Model)`: Automatización de registro de faltantes de caja como gastos contables.
    - [x] `Auth System`: Implementación de **Google Social Login** con registro automático de clientes.

---

## Estructura de Registro
Para mantener el orden, cada entrada debe incluir:
1. **Fecha**: Entre corchetes `[YYYY-MM-DD]`.
2. **Resumen**: Breve descripción del objetivo del cambio.
3. **Scripts SQL**: Código exacto ejecutado en la base de datos.
4. **Detalle Técnico**: Archivos modificados y lógica aplicada.
