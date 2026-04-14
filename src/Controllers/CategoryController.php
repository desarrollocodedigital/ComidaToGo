<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class CategoryController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE company_id = :cid ORDER BY sort_order ASC, name ASC");
        $stmt->execute([':cid' => $companyId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['company_id'], $input['name'])) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos requeridos"]);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO categories (company_id, name, sort_order) VALUES (:cid, :keyname, :sort)");
        $stmt->execute([
            ':cid' => $input['company_id'],
            ':keyname' => $input['name'],
            ':sort' => $input['sort_order'] ?? 0
        ]);

        echo json_encode(["id" => $this->db->lastInsertId(), "message" => "Categoría creada"]);
    }

    public function update($id, $input) {
        if (!isset($input['name'])) {
            http_response_code(400);
            return;
        }

        $stmt = $this->db->prepare("UPDATE categories SET name = :keyname, sort_order = :sort WHERE id = :id");
        $stmt->execute([
            ':keyname' => $input['name'],
            ':sort' => $input['sort_order'] ?? 0,
            ':id' => $id
        ]);

        echo json_encode(["message" => "Categoría actualizada"]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["message" => "Categoría eliminada"]);
    }
}
