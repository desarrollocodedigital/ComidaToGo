<?php

namespace App\Models;

use PDO;

class Expense extends BaseModel {
    protected $table = 'expenses';

    public function createExpense($data) {
        $stmtShift = $this->db->prepare("SELECT status FROM cash_register_shifts WHERE id = ?");
        $stmtShift->execute([$data['cash_register_shift_id']]);
        $shiftStatus = $stmtShift->fetchColumn();

        if ($shiftStatus !== 'OPEN') {
            return ["success" => false, "message" => "No se pueden registrar gastos en un turno de caja cerrado."];
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->table} (company_id, cash_register_shift_id, user_id, amount, category, description) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([
            $data['company_id'], 
            $data['cash_register_shift_id'], 
            $data['user_id'], 
            $data['amount'], 
            $data['category'] ?? 'OTHER', 
            $data['description']
        ])) {
            return ["success" => true, "expense_id" => $this->db->lastInsertId()];
        }
        
        return ["success" => false, "message" => "Error al registrar el gasto."];
    }
}
