## [2026-04-20] - Sistema de Calificaciones (Reviews)

### 📝 Resumen de Cambios
- Implementación completa del sistema de calificaciones de negocios basado en la experiencia del cliente y visualización de promedios en el Marketplace.
- Modernización del Control de Caja: Humanización de responsables del turno, migración total a diálogos premium y soporte para reportes multilineales.
- Automatización Contable: Registro automático de faltantes de caja como gastos para garantizar la precisión de las analíticas de utilidad.
- Optimización POS: Centralización de configuración de impresora en base de datos y nueva calculadora de cambio para pagos en efectivo.

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

---

## Estructura de Registro
Para mantener el orden, cada entrada debe incluir:
1. **Fecha**: Entre corchetes `[YYYY-MM-DD]`.
2. **Resumen**: Breve descripción del objetivo del cambio.
3. **Scripts SQL**: Código exacto ejecutado en la base de datos.
4. **Detalle Técnico**: Archivos modificados y lógica aplicada.
