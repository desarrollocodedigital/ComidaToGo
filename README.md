# ComidaToGo 🍔🚀

**ComidaToGo** es una plataforma híbrida (SaaS + Marketplace) diseñada específicamente para pequeñas carretas, puestos de comida y restaurantes locales. Permite a los negocios tener su propia presencia digital y gestionar pedidos complejos sin las altas comisiones de las grandes plataformas.

## 🌟 Características Principales

### Para el Negocio (SaaS)
*   **App Web Propia**: Acceso directo vía slug (ej: `comidatogo.com/tacos-juan`).
*   **Gestor de Menú**: Configuración de productos, categorías y modificadores complejos (ej: "Con todo", "Extra queso").
*   **Cocina View**: Panel administrativo para recibir y gestionar pedidos en tiempo real con alertas sonoras.

### Para el Comensal (Marketplace)
*   **Buscador Global**: Encuentra comida local por tipo o nombre.
*   **Personalizador de Antojitos**: Interfaz interactiva para armar el pedido ideal.
*   **Seguimiento de Pedidos**: Notificaciones sobre el estado del pedido (Aceptado, En Cocina, Listo).

## 🛠️ Stack Tecnológico

*   **Backend**: PHP 8.x (Arquitectura limpia, autoloader manual).
*   **Frontend**: Vue.js 3 (Vite) para la interactividad del cliente.
*   **Base de Datos**: MySQL.
*   **Estilos**: CSS moderno / Diseño Responsivo.

## 📂 Estructura del Proyecto

```text
ComidaToGo/
├── database/     # Scripts SQL y migraciones
├── frontend/     # Código fuente de Vue.js (Vite)
├── public/       # Punto de entrada (index.php) y assets públicos
├── src/          # Lógica del servidor (PHP)
│   ├── Config/   # Configuraciones (DB, .env loader)
│   ├── Controllers/ # Controladores de la aplicación
│   └── Models/      # Modelos de datos
├── .env          # Variables de entorno (No subir a Git)
└── .gitignore    # Archivos excluidos del repositorio
```

## 🚀 Instalación y Configuración

1.  **Clonar el repositorio**:
    ```bash
    git clone <url-del-repositorio>
    ```

2.  **Configurar el Servidor (XAMPP/Laragon)**:
    Apunta tu servidor web a la carpeta raíz o coloca el proyecto en `htdocs`.

3.  **Configurar Base de Datos**:
    *   Crea una base de datos llamada `comidatogo_db`.
    *   Importa los scripts de la carpeta `database/`.

4.  **Configurar Variables de Entorno**:
    *   Crea un archivo `.env` en la raíz (puedes usar el ya creado o basarte en los ejemplos).
    *   Asegúrate de que las credenciales coincidan con las de tu entorno local.

5.  **Instalar Frontend**:
    ```bash
    cd frontend
    npm install
    npm run dev
    ```
