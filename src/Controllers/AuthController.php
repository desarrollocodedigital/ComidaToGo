<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class AuthController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['name'], $input['email'], $input['password'], $input['role'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos"]);
            return;
        }

        // Validar si existe
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $input['email']]);
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode(["message" => "El email ya está registrado"]);
            return;
        }

        // Crear usuario
        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :pass, :role)";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':name' => $input['name'],
                ':email' => $input['email'],
                ':pass' => $hash,
                ':role' => $input['role'] // CUSTOMER or OWNER
            ]);
            $userId = $this->db->lastInsertId();

            // Si es Owner y mandó datos de empresa, crearla (Simplificado para MVP)
            $companyId = null;
            if ($input['role'] === 'OWNER' && isset($input['company_name'], $input['company_slug'])) {
                $sqlComp = "INSERT INTO companies (name, slug, owner_id, is_open) VALUES (:name, :slug, :oid, 0)";
                $stmtComp = $this->db->prepare($sqlComp);
                $stmtComp->execute([
                    ':name' => $input['company_name'],
                    ':slug' => $input['company_slug'],
                    ':oid' => $userId
                ]);
                $companyId = $this->db->lastInsertId();
            }

            echo json_encode([
                "message" => "Usuario registrado",
                "user" => [
                    "id" => $userId,
                    "name" => $input['name'],
                    "role" => $input['role'],
                    "company_id" => $companyId
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error al registrar: " . $e->getMessage()]);
        }
    }

    public function login() {
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            // Log para debug
            file_put_contents('C:/xampp/htdocs/ComidaToGo/debug.log', "Login attempt. Raw input: " . $rawInput . "\n", FILE_APPEND);

            if (!isset($input['email'], $input['password'])) {
                http_response_code(400);
                echo json_encode(["message" => "Faltan credenciales"]);
                return;
            }

            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $input['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($input['password'], $user['password'])) {
                $companyId = null;
                if ($user['role'] === 'OWNER') {
                    $stmtC = $this->db->prepare("SELECT id FROM companies WHERE owner_id = :uid LIMIT 1");
                    $stmtC->execute([':uid' => $user['id']]);
                    $company = $stmtC->fetch(PDO::FETCH_ASSOC);
                    if ($company) $companyId = $company['id'];
                } else if (in_array($user['role'], ['KITCHEN', 'CASHIER', 'WAITER'])) {
                    $companyId = $user['company_id'] ?? null;
                }

                echo json_encode([
                    "message" => "Login exitoso",
                    "user" => [
                        "id" => $user['id'],
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "role" => $user['role'],
                        "company_id" => $companyId
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Credenciales inválidas"]);
            }
        } catch (\Throwable $e) {
            file_put_contents('C:/xampp/htdocs/ComidaToGo/debug.log', "Login Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n", FILE_APPEND);
            http_response_code(500);
            echo json_encode(["message" => "Internal Server Error", "error" => $e->getMessage()]);
        }
    }
}
