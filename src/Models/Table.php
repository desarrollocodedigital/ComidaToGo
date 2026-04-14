<?php

namespace App\Models;

use PDO;

class Table extends BaseModel {
    protected $table = 'restaurant_tables';

    public function getTablesByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE company_id = ? ORDER BY id ASC");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTable($companyId, $name, $capacity) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (company_id, name, capacity) VALUES (?, ?, ?)");
        if ($stmt->execute([$companyId, $name, $capacity])) {
            return ["success" => true, "id" => $this->db->lastInsertId()];
        }
        return ["success" => false];
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}
