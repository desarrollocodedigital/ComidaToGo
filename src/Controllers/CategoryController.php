<?php

namespace App\Controllers;

use App\Models\Category;

class CategoryController {
    private $model;

    public function __construct() {
        $this->model = new Category();
    }

    public function getByCompany($companyId) {
        $categories = $this->model->getCategoriesByCompany($companyId);
        echo json_encode($categories);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['company_id'], $input['name'])) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos requeridos"]);
            return;
        }

        $id = $this->model->createCategory($input);

        echo json_encode(["id" => $id, "message" => "Categoría creada"]);
    }

    public function update($id, $input) {
        if (!isset($input['name'])) {
            http_response_code(400);
            return;
        }

        $this->model->updateCategory($id, $input);

        echo json_encode(["message" => "Categoría actualizada"]);
    }

    public function delete($id) {
        $this->model->delete($id);
        echo json_encode(["message" => "Categoría eliminada"]);
    }
}

