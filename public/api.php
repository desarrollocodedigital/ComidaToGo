<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../src/Config/Database.php';

// Autoloader (copiado de index.php por simplicidad, idealmente common.php)
spl_autoload_register(function ($class) {
    // Definir el prefijo del espacio de nombres
    $prefix = 'App\\';

    // Definir el directorio base para el espacio de nombres
    $base_dir = __DIR__ . '/../src/';

    // Verificar si la clase utiliza el prefijo del espacio de nombres
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Si no, pasar a registrar el siguiente autoloader
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);

    // Reemplazar el prefijo del espacio de nombres por el directorio base
    // reemplazar los separadores de espacio de nombres por separadores de directorio
    // y añadir la extensión .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Si el archivo existe, requerirlo
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\CompanyController;
use App\Controllers\CategoryController;
use App\Controllers\ProductController;
use App\Controllers\ModifierController;
use App\Controllers\OrderController;
use App\Controllers\AuthController;
use App\Controllers\CashRegisterController;
use App\Controllers\ExpenseController;
use App\Controllers\AnalyticsController;
use App\Controllers\TableController;
use App\Controllers\ChatController;
use App\Controllers\UserController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Esperamos /api/recurso/id o /api/recurso...
$key = array_search('api.php', $uri);
$resource = isset($uri[$key + 1]) ? $uri[$key + 1] : null;
$param = isset($uri[$key + 2]) ? $uri[$key + 2] : null;

$method = $_SERVER['REQUEST_METHOD'];

if ($resource === 'auth') {
    $controller = new AuthController();
    if ($param === 'register' && $method === 'POST') {
        $controller->register();
    } elseif ($param === 'login' && $method === 'POST') {
        $controller->login();
    }
} elseif ($resource === 'tenant') {
    $controller = new CompanyController();
    if ($method === 'GET' && $param) {
        $controller->getTenant($param); // Por slug
    } elseif ($method === 'PUT' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        $controller->update($param, $input);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Ruta no encontrada"]);
    }
} elseif ($resource === 'search') {
    // /api.php/search?q=...
    $controller = new CompanyController();
    if ($method === 'GET') {
        $controller->search($_GET);
    }
} elseif ($resource === 'orders') {
    $controller = new OrderController();
    if ($method === 'POST') {
        $controller->create();
    } elseif ($method === 'GET') {
        if ($param) {
             $controller->getOne($param);
        } elseif (isset($_GET['company_id'])) {
            $controller->getByCompany($_GET['company_id']);
        }
    } elseif ($method === 'PUT' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        $controller->update($param, $input);
    }
} elseif ($resource === 'cash-register') {
    $controller = new CashRegisterController();
    if ($method === 'POST') {
        if ($param === 'open') {
            $controller->open();
        } elseif ($param === 'close') {
            $controller->close();
        }
    } elseif ($method === 'GET' && $param === 'status') {
        $controller->getActiveStatus();
    }
} elseif ($resource === 'expenses') {
    $controller = new ExpenseController();
    if ($method === 'POST') {
        $controller->create();
    }
} elseif ($resource === 'analytics') {
    $controller = new AnalyticsController();
    if ($method === 'GET' && $param === 'sales') {
        $controller->getSalesSummary();
    } elseif ($method === 'GET' && $param === 'top-products') {
        $controller->getTopProducts();
    }
} elseif ($resource === 'tables') {
    $controller = new TableController();
    if ($method === 'GET') {
        $controller->index();
    } elseif ($method === 'POST') {
        $controller->create();
    } elseif ($method === 'PUT' && isset($uri[$key + 3]) && $uri[$key + 3] === 'status') {
         $input = json_decode(file_get_contents('php://input'), true);
         $controller->updateStatus($param, $input);
    }
} elseif ($resource === 'chat') {
    $controller = new ChatController();
    if ($method === 'GET') {
        $controller->getConversation(); // Busca por query params
    } elseif ($method === 'POST' && $param === 'message') {
        $controller->sendMessage();
    }
} elseif ($resource === 'categories') {
    $controller = new CategoryController();
    if ($method === 'GET' && isset($_GET['company_id'])) {
        $controller->getByCompany($_GET['company_id']);
    } elseif ($method === 'POST') {
        $controller->create();
    } elseif ($method === 'PUT' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        $controller->update($param, $input);
    } elseif ($method === 'DELETE' && $param) {
        $controller->delete($param);
    }
} elseif ($resource === 'products') {
    $controller = new ProductController();
    if ($method === 'GET' && isset($_GET['company_id'])) {
        $controller->getByCompany($_GET['company_id']);
    } elseif ($method === 'POST') {
        $controller->create();
    } elseif ($method === 'PUT' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        $controller->update($param, $input);
    } elseif ($method === 'DELETE' && $param) {
        $controller->delete($param);
    }
} elseif ($resource === 'modifiers') {
    $controller = new ModifierController();
    if ($method === 'GET' && isset($_GET['company_id'])) {
        $controller->getGroupsByCompany($_GET['company_id']);
    } elseif ($method === 'POST') {
        $controller->createGroup();
    } elseif ($method === 'PUT' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        $controller->updateGroup($param, $input);
    } elseif ($method === 'DELETE' && $param) {
        $controller->deleteGroup($param);
    }
} elseif ($resource === 'modifier_options') {
    $controller = new ModifierController();
    if ($method === 'POST') {
        $controller->createOption();
    } elseif ($method === 'PUT' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        $controller->updateOption($param, $input);
    } elseif ($method === 'DELETE' && $param) {
        $controller->deleteOption($param);
    }
} elseif ($resource === 'product_modifiers') {
    $controller = new ModifierController();
    if ($method === 'POST' && $param) {
        // e.g., POST /product_modifiers/{product_id}
        $input = json_decode(file_get_contents('php://input'), true);
        $controller->assignGroupToProduct($param, $input);
    } elseif ($method === 'DELETE' && $param && isset($_GET['modifier_group_id'])) {
        // e.g., DELETE /product_modifiers/{product_id}?modifier_group_id=X
        $controller->removeGroupFromProduct($param, $_GET['modifier_group_id']);
    }
} elseif ($resource === 'users') {
    $controller = new UserController();
    if ($method === 'GET' && isset($_GET['company_id'])) {
        $controller->getByCompany($_GET['company_id']);
    } elseif ($method === 'POST') {
        $controller->create();
    } elseif ($method === 'DELETE' && $param) {
        $controller->delete($param);
    }
} else {
    http_response_code(404);
    echo json_encode([
        "message" => "Endpoint no encontrado",
        "debug_uri" => $uri
    ]);
}
