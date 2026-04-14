<?php

namespace App\Models;

use PDO;

class Category extends BaseModel {
    protected $table = 'categories';

    public function getCategoriesByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE company_id = :cid ORDER BY sort_order ASC, name ASC");
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCategory($data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (company_id, name, sort_order) VALUES (:cid, :keyname, :sort)");
        $stmt->execute([
            ':cid' => $data['company_id'],
            ':keyname' => $data['name'],
            ':sort' => $data['sort_order'] ?? 0
        ]);
        return $this->db->lastInsertId();
    }

    public function updateCategory($id, $data) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = :keyname, sort_order = :sort WHERE id = :id");
        return $stmt->execute([
            ':keyname' => $data['name'],
            ':sort' => $data['sort_order'] ?? 0,
            ':id' => $id
        ]);
    }
}
