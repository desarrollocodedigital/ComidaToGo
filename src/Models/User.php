<?php

namespace App\Models;

use PDO;
use Exception;

class User extends BaseModel {
    protected $table = 'users';

    public function getUsersByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT id, name, email, role, company_id, created_at FROM {$this->table} WHERE company_id = :cid ORDER BY created_at DESC");
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createUser($data) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE email = :email");
        $stmt->execute([':email' => $data['email']]);
        if ($stmt->fetch()) {
            return ["success" => false, "message" => "El email ya está registrado"];
        }

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO {$this->table} (name, email, password, role, company_id) VALUES (:name, :email, :pass, :role, :cid)";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':pass' => $hash,
                ':role' => $data['role'],
                ':cid' => $data['company_id']
            ]);
            return ["success" => true, "user_id" => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }

    public function updateCart($userId, $cartData) {
        $sql = "UPDATE {$this->table} SET cart_data = :cd WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cd' => $cartData,
            ':id' => $userId
        ]);
    }

    public function updateProfile($userId, $data) {
        $sql = "UPDATE {$this->table} SET name = :name, phone = :phone, addresses = :addr WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':addr' => isset($data['addresses']) ? json_encode($data['addresses'], JSON_UNESCAPED_UNICODE) : null,
            ':id' => $userId
        ]);
    }


    public function deleteUser($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id AND role IN ('KITCHEN','CASHIER','WAITER')");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
