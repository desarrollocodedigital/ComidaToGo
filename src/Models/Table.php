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

    public function createTable($companyId, $name, $capacity, $tableNumber = null) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (company_id, name, capacity, table_number) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$companyId, $name, $capacity, $tableNumber])) {
            return ["success" => true, "id" => $this->db->lastInsertId()];
        }
        return ["success" => false];
    }

    public function updateTable($id, $name, $capacity, $tableNumber = null) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = ?, capacity = ?, table_number = ? WHERE id = ?");
        return $stmt->execute([$name, $capacity, $tableNumber, $id]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}
