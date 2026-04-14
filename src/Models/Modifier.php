<?php

namespace App\Models;

use PDO;
use Exception;

class Modifier extends BaseModel {
    
    public function getGroupsByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM modifier_groups WHERE company_id = :cid ORDER BY name ASC");
        $stmt->execute([':cid' => $companyId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($groups as &$group) {
            $stmtOpt = $this->db->prepare("SELECT * FROM modifier_options WHERE modifier_group_id = :gid ORDER BY price_adjustment ASC, name ASC");
            $stmtOpt->execute([':gid' => $group['id']]);
            $group['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $groups;
    }

    public function createGroup($data) {
        $stmt = $this->db->prepare("INSERT INTO modifier_groups (company_id, name, min_selection, max_selection) VALUES (:cid, :keyname, :min, :max)");
        $stmt->execute([
            ':cid' => $data['company_id'],
            ':keyname' => $data['name'],
            ':min' => $data['min_selection'] ?? 0,
            ':max' => $data['max_selection'] ?? 1
        ]);
        
        $groupId = $this->db->lastInsertId();

        if (isset($data['options']) && is_array($data['options'])) {
            $stmtOpt = $this->db->prepare("INSERT INTO modifier_options (modifier_group_id, name, price_adjustment) VALUES (:gid, :name, :price)");
            foreach ($data['options'] as $opt) {
                if (!empty(trim($opt['name']))) {
                    $stmtOpt->execute([
                        ':gid' => $groupId,
                        ':name' => $opt['name'],
                        ':price' => $opt['price_adjustment'] ?? 0
                    ]);
                }
            }
        }

        return $groupId;
    }

    public function updateGroup($id, $data) {
        $stmt = $this->db->prepare("UPDATE modifier_groups SET name = :keyname, min_selection = :min, max_selection = :max WHERE id = :id");
        $stmt->execute([
            ':keyname' => $data['name'],
            ':min' => $data['min_selection'] ?? 0,
            ':max' => $data['max_selection'] ?? 1,
            ':id' => $id
        ]);

        if (isset($data['options']) && is_array($data['options'])) {
            $this->db->prepare("DELETE FROM modifier_options WHERE modifier_group_id = :gid")->execute([':gid' => $id]);
            
            $stmtOpt = $this->db->prepare("INSERT INTO modifier_options (modifier_group_id, name, price_adjustment) VALUES (:gid, :name, :price)");
            foreach ($data['options'] as $opt) {
                if (!empty(trim($opt['name']))) {
                    $stmtOpt->execute([
                        ':gid' => $id,
                        ':name' => $opt['name'],
                        ':price' => $opt['price_adjustment'] ?? 0
                    ]);
                }
            }
        }
        return true;
    }

    public function deleteGroup($id) {
        $stmt = $this->db->prepare("DELETE FROM modifier_groups WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function createOption($data) {
        $stmt = $this->db->prepare("INSERT INTO modifier_options (modifier_group_id, name, price_adjustment) VALUES (:gid, :keyname, :price)");
        $stmt->execute([
            ':gid' => $data['modifier_group_id'],
            ':keyname' => $data['name'],
            ':price' => $data['price_adjustment'] ?? 0.00
        ]);
        return $this->db->lastInsertId();
    }

    public function updateOption($id, $data) {
        $stmt = $this->db->prepare("UPDATE modifier_options SET name = :keyname, price_adjustment = :price WHERE id = :id");
        return $stmt->execute([
            ':keyname' => $data['name'],
            ':price' => $data['price_adjustment'] ?? 0.00,
            ':id' => $id
        ]);
    }

    public function deleteOption($id) {
        $stmt = $this->db->prepare("DELETE FROM modifier_options WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function assignGroupToProduct($productId, $groupId, $sortOrder = 0) {
        try {
            $stmt = $this->db->prepare("INSERT IGNORE INTO product_modifiers (product_id, modifier_group_id, sort_order) VALUES (:pid, :gid, :sort)");
            $stmt->execute([
                ':pid' => $productId,
                ':gid' => $groupId,
                ':sort' => $sortOrder
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeGroupFromProduct($productId, $groupId) {
        $stmt = $this->db->prepare("DELETE FROM product_modifiers WHERE product_id = :pid AND modifier_group_id = :gid");
        return $stmt->execute([
            ':pid' => $productId,
            ':gid' => $groupId
        ]);
    }
}
