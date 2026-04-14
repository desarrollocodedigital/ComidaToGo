<?php

namespace App\Controllers;

use App\Models\Product;

class ProductController {
    private $model;

    public function __construct() {
        $this->model = new Product();
    }

    public function getByCompany($companyId) {
        $products = $this->model->getProductsByCompany($companyId);
        echo json_encode($products);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['category_id'], $input['name'], $input['price'])) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos requeridos"]);
            return;
        }

        $id = $this->model->createProduct($input);
        echo json_encode(["id" => $id, "message" => "Producto creado"]);
    }

    public function update($id, $input) {
        if (!isset($input['name'], $input['price'])) {
            http_response_code(400);
            return;
        }

        $this->model->updateProduct($id, $input);
        echo json_encode(["message" => "Producto actualizado"]);
    }

    public function delete($id) {
        $this->model->deleteProduct($id);
        echo json_encode(["message" => "Producto eliminado"]);
    }
}

