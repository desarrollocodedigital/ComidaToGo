<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class UserController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api.php/users?company_id=X
    public function getByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT id, name, email, role, company_id, created_at FROM users WHERE company_id = :cid ORDER BY created_at DESC");
        $stmt->execute([':cid' => $companyId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // POST /api.php/users
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['name'], $input['email'], $input['password'], $input['role'], $input['company_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Se requiere: name, email, password, role, company_id"]);
            return;
        }

        $allowedRoles = ['KITCHEN', 'CASHIER', 'WAITER'];
        if (!in_array($input['role'], $allowedRoles)) {
            http_response_code(400);
            echo json_encode(["message" => "Rol inválido. Roles permitidos: " . implode(', ', $allowedRoles)]);
            return;
        }

        // Check duplicate email
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $input['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(["message" => "El email ya está registrado"]);
            return;
        }

        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role, company_id) VALUES (:name, :email, :pass, :role, :cid)";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                ':name' => $input['name'],
                ':email' => $input['email'],
                ':pass' => $hash,
                ':role' => $input['role'],
                ':cid' => $input['company_id']
            ]);
            echo json_encode([
                "message" => "Empleado creado",
                "user_id" => $this->db->lastInsertId()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    }

    // DELETE /api.php/users/{id}
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id AND role IN ('KITCHEN','CASHIER','WAITER')");
        $stmt->execute([':id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "Empleado eliminado"]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Empleado no encontrado o no se puede eliminar (solo staff)"]);
        }
    }
}
