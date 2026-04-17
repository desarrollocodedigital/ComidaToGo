<?php
namespace App\Controllers;
use App\Models\ExpenseCategory;

class ExpenseCategoryController {
    private $model;

    public function __construct() {
        $this->model = new ExpenseCategory();
    }

    public function getByCompany() {
        if (!isset($_GET['company_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Falta el ID de la empresa."]);
            return;
        }
        $categories = $this->model->getByCompany($_GET['company_id']);
        echo json_encode($categories);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['company_id'], $input['name'])) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos obligatorios."]);
            return;
        }

        $id = $this->model->createCustom($input);
        if ($id) {
            http_response_code(201);
            echo json_encode(["id" => $id, "message" => "Categoría creada."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear la categoría."]);
        }
    }

    public function delete($id) {
        if ($this->model->delete($id)) {
            echo json_encode(["message" => "Categoría eliminada."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al eliminar."]);
        }
    }
}
