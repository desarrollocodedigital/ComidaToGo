<?php

namespace App\Controllers;

use App\Models\Table;

class TableController {
    private $model;

    public function __construct() {
        $this->model = new Table();
    }

    // GET /api.php/tables?company_id=1
    public function index() {
        $company_id = $_GET['company_id'] ?? null;
        if (!$company_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Falta company_id']);
            return;
        }

        $tables = $this->model->getTablesByCompany($company_id);
        echo json_encode($tables);
    }

    // POST /api.php/tables
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        $company_id = $input['company_id'] ?? null;
        $name = $input['name'] ?? null;
        $capacity = $input['capacity'] ?? 4;
        $table_number = $input['table_number'] ?? null;

        if (!$company_id || !$name) {
            http_response_code(400);
            echo json_encode(['message' => 'Faltan datos obligatorios para crear mesa.']);
            return;
        }

        $result = $this->model->createTable($company_id, $name, $capacity, $table_number);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode(['message' => 'Mesa creada', 'id' => $result['id']]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al crear mesa.']);
        }
    }

    // PUT /api.php/tables/:id
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? null;
        $capacity = $input['capacity'] ?? 4;
        $table_number = $input['table_number'] ?? null;

        if (!$name) {
            http_response_code(400);
            echo json_encode(['message' => 'El nombre de la mesa es obligatorio.']);
            return;
        }

        if ($this->model->updateTable($id, $name, $capacity, $table_number)) {
            echo json_encode(['message' => 'Mesa actualizada correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al actualizar mesa.']);
        }
    }

    // DELETE /api.php/tables/:id
    public function delete($id) {
        if ($this->model->delete($id)) {
            echo json_encode(['message' => 'Mesa eliminada correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al eliminar mesa.']);
        }
    }

    // PUT /api.php/tables/:id/status
    public function updateStatus($id, $input) {
         $status = $input['status'] ?? 'AVAILABLE';
         // Validate ENUM
         if (!in_array($status, ['AVAILABLE', 'OCCUPIED', 'RESERVED'])) {
              http_response_code(400);
              echo json_encode(['message' => 'Status inválido']);
              return;
         }

         if ($this->model->updateStatus($id, $status)) {
             echo json_encode(['message' => 'Estado de la mesa actualizado']);
         } else {
             http_response_code(500);
         }
    }
}

