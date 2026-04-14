<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class TableController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api.php/tables?company_id=1
    public function index() {
        $company_id = $_GET['company_id'] ?? null;
        if (!$company_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Falta company_id']);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM restaurant_tables WHERE company_id = ? ORDER BY id ASC");
        $stmt->execute([$company_id]);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($tables);
    }

    // POST /api.php/tables
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        $company_id = $input['company_id'] ?? null;
        $name = $input['name'] ?? null;
        $capacity = $input['capacity'] ?? 4;

        if (!$company_id || !$name) {
            http_response_code(400);
            echo json_encode(['message' => 'Faltan datos obligatorios para crear mesa.']);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO restaurant_tables (company_id, name, capacity) VALUES (?, ?, ?)");
        if ($stmt->execute([$company_id, $name, $capacity])) {
            http_response_code(201);
            echo json_encode(['message' => 'Mesa creada', 'id' => $this->db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al crear mesa.']);
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

         $stmt = $this->db->prepare("UPDATE restaurant_tables SET status = ? WHERE id = ?");
         if ($stmt->execute([$status, $id])) {
             echo json_encode(['message' => 'Estado de la mesa actualizado']);
         } else {
             http_response_code(500);
         }
    }
}
