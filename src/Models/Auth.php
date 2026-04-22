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
            // Verificar si la cuenta está activa
            if (isset($user['active']) && (int)$user['active'] === 0) {
                return [
                    "success" => false,
                    "inactive" => true,
                    "message" => "Cuenta suspendida, por favor ponerse en contacto con el dueño del negocio"
                ];
            }
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

    /**
     * Valida un ID Token de Google y autentica/registra al usuario.
     */
    public function handleGoogleLogin($idToken, $extra = []) {
        try {
            $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID');
            $client = new \Google\Client(['client_id' => $clientId]);
            
            $payload = $client->verifyIdToken($idToken);
            
            if (!$payload) {
                return ["success" => false, "message" => "Token de Google inválido"];
            }
 
            $email = $payload['email'];
            $name = $payload['name'];
 
            // 1. Buscar si el usuario ya existe
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
            if (!$user) {
                // 2. Registrar usuario nuevo
                $role = $extra['role'] ?? 'CUSTOMER';
                $randomPass = bin2hex(random_bytes(16));
                $hash = password_hash($randomPass, PASSWORD_DEFAULT);
                
                $this->db->beginTransaction();
                try {
                    $sql = "INSERT INTO {$this->table} (name, email, password, role) VALUES (:name, :email, :pass, :role)";
                    $stmtIns = $this->db->prepare($sql);
                    $stmtIns->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':pass' => $hash,
                        ':role' => $role
                    ]);
                    $userId = $this->db->lastInsertId();
 
                    $companyId = null;
                    if ($role === 'OWNER' && isset($extra['company_name'], $extra['company_slug'])) {
                        $sqlComp = "INSERT INTO companies (name, slug, owner_id, is_open) VALUES (:cname, :cslug, :oid, 0)";
                        $stmtComp = $this->db->prepare($sqlComp);
                        $stmtComp->execute([
                            ':cname' => $extra['company_name'],
                            ':cslug' => $extra['company_slug'],
                            ':oid' => $userId
                        ]);
                        $companyId = $this->db->lastInsertId();
                    }
 
                    $this->db->commit();
 
                    $user = [
                        "id" => $userId,
                        "name" => $name,
                        "email" => $email,
                        "role" => $role,
                        "company_id" => $companyId,
                        "phone" => null,
                        "addresses" => "[]",
                        "cart_data" => null
                    ];
                } catch (Exception $e) {
                    $this->db->rollBack();
                    throw $e;
                }
            }

            // 3. Verificar si está activo (en caso de que sea usuario existente)
            if (isset($user['active']) && (int)$user['active'] === 0) {
                return [
                    "success" => false,
                    "message" => "Cuenta suspendida. Contacte al administrador."
                ];
            }

            // 4. Preparar respuesta de sesión (mismo formato que login tradicional)
            $companyId = null;
            if ($user['role'] === 'OWNER') {
                $stmtC = $this->db->prepare("SELECT id FROM companies WHERE owner_id = :uid LIMIT 1");
                $stmtC->execute([':uid' => $user['id']]);
                $company = $stmtC->fetch(PDO::FETCH_ASSOC);
                if ($company) $companyId = $company['id'];
            }

            return [
                "success" => true,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "role" => $user['role'],
                    "company_id" => $companyId,
                    "phone" => $user['phone'] ?? null,
                    "addresses" => is_string($user['addresses']) ? json_decode($user['addresses'], true) : ($user['addresses'] ?? []),
                    "cart_data" => $user['cart_data'] ?? null
                ]
            ];

        } catch (Exception $e) {
            return ["success" => false, "message" => "Error en proceso Google: " . $e->getMessage()];
        }
    }
}
