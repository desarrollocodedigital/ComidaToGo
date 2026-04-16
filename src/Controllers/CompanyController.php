<?php

namespace App\Controllers;

use App\Models\Company;

class CompanyController {
    private $model;

    public function __construct() {
        $this->model = new Company();
    }

    // GET /api.php/tenant/{slug_or_id}
    public function getTenant($param) {
        $company = $this->model->findBySlugOrId($param);

        if (!$company) {
            http_response_code(404);
            echo json_encode(["message" => "Empresa no encontrada"]);
            return;
        }

        $company['menu'] = $this->model->getCategoriesWithProductsAndModifiers($company['id']);

        echo json_encode($company);
    }

    public function update($id, $data) {
        if (!$id || !$data) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos"]);
            return;
        }

        $success = $this->model->update($id, $data);

        if ($success) {
            echo json_encode(["message" => "Configuración actualizada con éxito"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar la configuración"]);
        }
    }

    // GET /api/search?q=...
    public function search() {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'negocios';
        $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
        $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
        $state = $_GET['state'] ?? null;
        
        $results = $this->model->search($query, $type, $lat, $lng, $state);
        echo json_encode($results);
    }
}
