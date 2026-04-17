<?php

namespace App\Controllers;

use App\Models\User;

class UserController {
    private $model;

    public function __construct() {
        $this->model = new User();
    }

    // GET /api.php/users?company_id=X
    public function getByCompany($companyId) {
        $users = $this->model->getUsersByCompany($companyId);
        echo json_encode($users);
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

        $result = $this->model->createUser($input);

        if ($result['success']) {
            echo json_encode([
                "message" => "Empleado creado",
                "user_id" => $result['user_id']
            ]);
        } else {
            http_response_code(isset($result['message']) && strpos($result['message'], 'registrado') !== false ? 409 : 500);
            echo json_encode(["message" => $result['message']]);
        }
    }

    // DELETE /api.php/users/{id}
    public function delete($id) {
        if ($this->model->deleteUser($id)) {
            echo json_encode(["message" => "Empleado eliminado"]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Empleado no encontrado o no se puede eliminar (solo staff)"]);
        }
    }

    // PUT /api.php/users/status
    public function toggleStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['id'], $input['active'])) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos (id, active)"]);
            return;
        }

        if ($this->model->toggleStatus($input['id'], $input['active'])) {
            echo json_encode(["message" => "Estado actualizado"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar estado"]);
        }
    }

    // PUT o POST /api.php/users/profile
    public function updateProfile() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Falta ID de usuario para actualizar el perfil"]);
            return;
        }

        if ($this->model->updateProfile($input['id'], $input)) {
            echo json_encode(["message" => "Perfil actualizado exitosamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar el perfil"]);
        }
    }
}
