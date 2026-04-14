<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class ModifierController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getGroupsByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM modifier_groups WHERE company_id = :cid ORDER BY name ASC");
        $stmt->execute([':cid' => $companyId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch options for these groups
        foreach ($groups as &$group) {
            $stmtOpt = $this->db->prepare("SELECT * FROM modifier_options WHERE modifier_group_id = :gid ORDER BY price_adjustment ASC, name ASC");
            $stmtOpt->execute([':gid' => $group['id']]);
            $group['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($groups);
    }

    public function createGroup() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['company_id'], $input['name'])) {
            http_response_code(400);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO modifier_groups (company_id, name, min_selection, max_selection) VALUES (:cid, :keyname, :min, :max)");
        $stmt->execute([
            ':cid' => $input['company_id'],
            ':keyname' => $input['name'],
            ':min' => $input['min_selection'] ?? 0,
            ':max' => $input['max_selection'] ?? 1
        ]);
        
        $groupId = $this->db->lastInsertId();

        // Handle nested options
        if (isset($input['options']) && is_array($input['options'])) {
            $stmtOpt = $this->db->prepare("INSERT INTO modifier_options (modifier_group_id, name, price_adjustment) VALUES (:gid, :name, :price)");
            foreach ($input['options'] as $opt) {
                if (!empty(trim($opt['name']))) {
                    $stmtOpt->execute([
                        ':gid' => $groupId,
                        ':name' => $opt['name'],
                        ':price' => $opt['price_adjustment'] ?? 0
                    ]);
                }
            }
        }

        echo json_encode(["id" => $groupId, "message" => "Grupo de modificadores creado"]);
    }

    public function updateGroup($id, $input) {
        if (!isset($input['name'])) {
            http_response_code(400);
            return;
        }

        $stmt = $this->db->prepare("UPDATE modifier_groups SET name = :keyname, min_selection = :min, max_selection = :max WHERE id = :id");
        $stmt->execute([
            ':keyname' => $input['name'],
            ':min' => $input['min_selection'] ?? 0,
            ':max' => $input['max_selection'] ?? 1,
            ':id' => $id
        ]);

        // Handle nested options (Sync: DELETE ALL then INSERT)
        if (isset($input['options']) && is_array($input['options'])) {
            $this->db->prepare("DELETE FROM modifier_options WHERE modifier_group_id = :gid")->execute([':gid' => $id]);
            
            $stmtOpt = $this->db->prepare("INSERT INTO modifier_options (modifier_group_id, name, price_adjustment) VALUES (:gid, :name, :price)");
            foreach ($input['options'] as $opt) {
                if (!empty(trim($opt['name']))) {
                    $stmtOpt->execute([
                        ':gid' => $id,
                        ':name' => $opt['name'],
                        ':price' => $opt['price_adjustment'] ?? 0
                    ]);
                }
            }
        }

        echo json_encode(["message" => "Grupo de modificadores actualizado"]);
    }

    public function deleteGroup($id) {
        $stmt = $this->db->prepare("DELETE FROM modifier_groups WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["message" => "Grupo de modificadores eliminado"]);
    }

    // --- OPTIONS ---

    public function createOption() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['modifier_group_id'], $input['name'])) {
            http_response_code(400);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO modifier_options (modifier_group_id, name, price_adjustment) VALUES (:gid, :keyname, :price)");
        $stmt->execute([
            ':gid' => $input['modifier_group_id'],
            ':keyname' => $input['name'],
            ':price' => $input['price_adjustment'] ?? 0.00
        ]);

        echo json_encode(["id" => $this->db->lastInsertId(), "message" => "Opción creada"]);
    }

    public function updateOption($id, $input) {
        if (!isset($input['name'])) {
            http_response_code(400);
            return;
        }

        $stmt = $this->db->prepare("UPDATE modifier_options SET name = :keyname, price_adjustment = :price WHERE id = :id");
        $stmt->execute([
            ':keyname' => $input['name'],
            ':price' => $input['price_adjustment'] ?? 0.00,
            ':id' => $id
        ]);

        echo json_encode(["message" => "Opción actualizada"]);
    }

    public function deleteOption($id) {
        $stmt = $this->db->prepare("DELETE FROM modifier_options WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["message" => "Opción eliminada"]);
    }

    // --- PIPES (Product to Group Assignment) ---
    
    public function assignGroupToProduct($productId, $input) {
        if (!isset($input['modifier_group_id'])) {
            http_response_code(400);
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT IGNORE INTO product_modifiers (product_id, modifier_group_id, sort_order) VALUES (:pid, :gid, :sort)");
            $stmt->execute([
                ':pid' => $productId,
                ':gid' => $input['modifier_group_id'],
                ':sort' => $input['sort_order'] ?? 0
            ]);
            echo json_encode(["message" => "Asignado"]);
        } catch (Exception $e) {
            http_response_code(500);
        }
    }

    public function removeGroupFromProduct($productId, $groupId) {
        $stmt = $this->db->prepare("DELETE FROM product_modifiers WHERE product_id = :pid AND modifier_group_id = :gid");
        $stmt->execute([
            ':pid' => $productId,
            ':gid' => $groupId
        ]);
        echo json_encode(["message" => "Desasignado"]);
    }
}
