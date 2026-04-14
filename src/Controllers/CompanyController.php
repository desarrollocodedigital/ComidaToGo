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
        // Asumiendo que había un update aquí o agregar a ser necesario.
        http_response_code(501);
        echo json_encode(["message" => "Not implemented"]);
    }

    // GET /api/search?q=...
    public function search($queryParams) {
        $q = isset($queryParams['q']) ? $queryParams['q'] : '';
        $results = $this->model->search($q);
        echo json_encode($results);
    }
}
