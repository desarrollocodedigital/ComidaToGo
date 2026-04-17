<?php
namespace App\Models;
use PDO;

class ExpenseCategory extends BaseModel {
    protected $table = 'expense_categories';

    public function getByCompany($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE company_id = ? ORDER BY name ASC");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCustom($data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (company_id, name, icon) VALUES (?, ?, ?)");
        if ($stmt->execute([$data['company_id'], $data['name'], $data['icon'] ?? 'Wallet'])) {
            return $this->db->lastInsertId();
        }
        return false;
    }
}
