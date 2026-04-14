<?php

namespace App\Controllers;

use App\Models\Modifier;

class ModifierController {
    private $model;

    public function __construct() {
        $this->model = new Modifier();
    }

    public function getGroupsByCompany($companyId) {
        $groups = $this->model->getGroupsByCompany($companyId);
        echo json_encode($groups);
    }

    public function createGroup() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['company_id'], $input['name'])) {
            http_response_code(400);
            return;
        }

        $id = $this->model->createGroup($input);
        echo json_encode(["id" => $id, "message" => "Grupo de modificadores creado"]);
    }

    public function updateGroup($id, $input) {
        if (!isset($input['name'])) {
            http_response_code(400);
            return;
        }

        $this->model->updateGroup($id, $input);
        echo json_encode(["message" => "Grupo de modificadores actualizado"]);
    }

    public function deleteGroup($id) {
        $this->model->deleteGroup($id);
        echo json_encode(["message" => "Grupo de modificadores eliminado"]);
    }

    // --- OPTIONS ---

    public function createOption() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['modifier_group_id'], $input['name'])) {
            http_response_code(400);
            return;
        }

        $id = $this->model->createOption($input);
        echo json_encode(["id" => $id, "message" => "Opción creada"]);
    }

    public function updateOption($id, $input) {
        if (!isset($input['name'])) {
            http_response_code(400);
            return;
        }

        $this->model->updateOption($id, $input);
        echo json_encode(["message" => "Opción actualizada"]);
    }

    public function deleteOption($id) {
        $this->model->deleteOption($id);
        echo json_encode(["message" => "Opción eliminada"]);
    }

    // --- PIPES (Product to Group Assignment) ---
    
    public function assignGroupToProduct($productId, $input) {
        if (!isset($input['modifier_group_id'])) {
            http_response_code(400);
            return;
        }

        $success = $this->model->assignGroupToProduct($productId, $input['modifier_group_id'], $input['sort_order'] ?? 0);
        if ($success) {
            echo json_encode(["message" => "Asignado"]);
        } else {
            http_response_code(500);
        }
    }

    public function removeGroupFromProduct($productId, $groupId) {
        $this->model->removeGroupFromProduct($productId, $groupId);
        echo json_encode(["message" => "Desasignado"]);
    }
}

