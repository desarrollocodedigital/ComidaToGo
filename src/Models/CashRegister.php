<?php

namespace App\Models;

use PDO;
use Exception;

class CashRegister extends BaseModel {
    protected $table = 'cash_register_shifts';

    public function getActiveShift($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE company_id = ? AND status = 'OPEN' LIMIT 1");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function openShift($companyId, $userId, $startingCash) {
        if ($this->getActiveShift($companyId)) {
            return ["success" => false, "message" => "Ya existe un turno de caja abierto para esta empresa."];
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->table} (company_id, opened_by_user_id, starting_cash) VALUES (?, ?, ?)");
        if ($stmt->execute([$companyId, $userId, $startingCash])) {
            return ["success" => true, "shift_id" => $this->db->lastInsertId()];
        }
        
        return ["success" => false, "message" => "Error al abrir la caja."];
    }

    public function closeShift($shiftId, $userId, $actualEndingCash) {
        $stmtShift = $this->db->prepare("SELECT starting_cash FROM {$this->table} WHERE id = ? AND status = 'OPEN'");
        $stmtShift->execute([$shiftId]);
        $shift = $stmtShift->fetch(PDO::FETCH_ASSOC);

        if (!$shift) {
            return ["success" => false, "message" => "Turno no encontrado o ya está cerrado."];
        }

        $startingCash = (float)$shift['starting_cash'];

        $stmtSales = $this->db->prepare("SELECT SUM(total_amount) as total_cash_sales FROM orders WHERE cash_register_shift_id = ? AND payment_method = 'CASH' AND status IN ('COMPLETED', 'READY')");
        $stmtSales->execute([$shiftId]);
        $sales = $stmtSales->fetch(PDO::FETCH_ASSOC);
        $cashSales = (float)($sales['total_cash_sales'] ?? 0);

        $stmtExpenses = $this->db->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE cash_register_shift_id = ?");
        $stmtExpenses->execute([$shiftId]);
        $expensesRows = $stmtExpenses->fetch(PDO::FETCH_ASSOC);
        $expenses = (float)($expensesRows['total_expenses'] ?? 0);

        $expectedEndingCash = $startingCash + $cashSales - $expenses;
        $discrepancyAmount = (float)$actualEndingCash - $expectedEndingCash;

        $updateStmt = $this->db->prepare("UPDATE {$this->table} SET status = 'CLOSED', closed_by_user_id = ?, closed_at = CURRENT_TIMESTAMP, expected_ending_cash = ?, actual_ending_cash = ?, discrepancy_amount = ? WHERE id = ?");
        
        if ($updateStmt->execute([$userId, $expectedEndingCash, $actualEndingCash, $discrepancyAmount, $shiftId])) {
            return [
                "success" => true,
                "summary" => [
                    "starting_cash" => $startingCash,
                    "cash_sales" => $cashSales,
                    "expenses" => $expenses,
                    "expected_ending_cash" => $expectedEndingCash,
                    "actual_ending_cash" => $actualEndingCash,
                    "discrepancy_amount" => $discrepancyAmount
                ]
            ];
        }

        return ["success" => false, "message" => "Error al cerrar la caja."];
    }

    public function getStatusWithMetrics($companyId) {
        $shift = $this->getActiveShift($companyId);
        
        if ($shift) {
            $stmtSales = $this->db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE cash_register_shift_id = ? AND payment_method = 'CASH' AND status IN ('COMPLETED', 'READY')");
            $stmtSales->execute([$shift['id']]);
            $cashSales = (float)$stmtSales->fetchColumn();

            $stmtExp = $this->db->prepare("SELECT SUM(amount) as total FROM expenses WHERE cash_register_shift_id = ?");
            $stmtExp->execute([$shift['id']]);
            $expenses = (float)$stmtExp->fetchColumn();

            $expectedCash = (float)$shift['starting_cash'] + $cashSales - $expenses;

            return [
                "has_active_shift" => true, 
                "shift" => $shift,
                "metrics" => [
                    "cash_sales" => $cashSales,
                    "expenses" => $expenses,
                    "expected_cash" => $expectedCash
                ]
            ];
        }

        return ["has_active_shift" => false];
    }
}
