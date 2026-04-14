<?php

namespace App\Models;

use PDO;
use Exception;

class Auth extends BaseModel {
    protected $table = 'users';

    public function userExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
    }

    public function registerUserAndCompany($input) {
        $this->db->beginTransaction();
        try {
            $hash = password_hash($input['password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO {$this->table} (name, email, password, role) VALUES (:name, :email, :pass, :role)";
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                ':name' => $input['name'],
                ':email' => $input['email'],
                ':pass' => $hash,
                ':role' => $input['role']
            ]);
            $userId = $this->db->lastInsertId();

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

            $this->db->commit();

            return [
                "success" => true,
                "user" => [
                    "id" => $userId,
                    "name" => $input['name'],
                    "role" => $input['role'],
                    "company_id" => $companyId,
                    "phone" => null,
                    "addresses" => [],
                    "cart_data" => null // Nuevo usuario, carrito vacío
                ]
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }

    public function attemptLogin($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $companyId = null;
            if ($user['role'] === 'OWNER') {
                $stmtC = $this->db->prepare("SELECT id FROM companies WHERE owner_id = :uid LIMIT 1");
                $stmtC->execute([':uid' => $user['id']]);
                $company = $stmtC->fetch(PDO::FETCH_ASSOC);
                if ($company) $companyId = $company['id'];
            } else if (in_array($user['role'], ['KITCHEN', 'CASHIER', 'WAITER'])) {
                $companyId = $user['company_id'] ?? null;
            }

            return [
                "success" => true,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "role" => $user['role'],
                    "company_id" => $companyId,
                    "phone" => $user['phone'],
                    "addresses" => $user['addresses'] ? json_decode($user['addresses'], true) : [],
                    "cart_data" => $user['cart_data'] // Recuperar de la base de datos
                ]
            ];
        }

        return [
            "success" => false
        ];
    }
}
