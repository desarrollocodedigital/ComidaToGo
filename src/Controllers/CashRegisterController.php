<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class CashRegisterController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // POST /api.php/cash-register/open
    public function open() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Asumimos que la autenticación ya validó la empresa y usuario.
        // Por ahora pasamos los IDs en el body para la demo.
        $company_id = $input['company_id'] ?? null;
        $user_id = $input['user_id'] ?? null;
        $starting_cash = $input['starting_cash'] ?? 0;

        if (!$company_id || !$user_id) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos obligatorios (company_id, user_id)"]);
            return;
        }

        // Verificar si ya hay una caja abierta
        $checkStmt = $this->db->prepare("SELECT id FROM cash_register_shifts WHERE company_id = ? AND status = 'OPEN'");
        $checkStmt->execute([$company_id]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(["message" => "Ya existe un turno de caja abierto para esta empresa."]);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO cash_register_shifts (company_id, opened_by_user_id, starting_cash) VALUES (?, ?, ?)");
        if ($stmt->execute([$company_id, $user_id, $starting_cash])) {
            http_response_code(201);
            echo json_encode(["message" => "Caja abierta exitosamente", "shift_id" => $this->db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al abrir la caja."]);
        }
    }

    // POST /api.php/cash-register/close
    public function close() {
        $input = json_decode(file_get_contents('php://input'), true);

        $shift_id = $input['shift_id'] ?? null;
        $user_id = $input['user_id'] ?? null;
        $actual_ending_cash = $input['actual_ending_cash'] ?? null;

        if (!$shift_id || !$user_id || $actual_ending_cash === null) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos obligatorios para el cierre."]);
            return;
        }

        // Obtener el estado actual y calcular expected_ending_cash
        // 1. Obtener fondo inicial
        $stmtShift = $this->db->prepare("SELECT starting_cash FROM cash_register_shifts WHERE id = ? AND status = 'OPEN'");
        $stmtShift->execute([$shift_id]);
        $shift = $stmtShift->fetch(PDO::FETCH_ASSOC);

        if (!$shift) {
            http_response_code(404);
            echo json_encode(["message" => "Turno no encontrado o ya está cerrado."]);
            return;
        }

        $starting_cash = (float)$shift['starting_cash'];

        // 2. Sumar Ventas en Efectivo vinculadas a este turno
        $stmtSales = $this->db->prepare("SELECT SUM(total_amount) as total_cash_sales FROM orders WHERE cash_register_shift_id = ? AND payment_method = 'CASH' AND status IN ('COMPLETED', 'READY')");
        $stmtSales->execute([$shift_id]);
        $sales = $stmtSales->fetch(PDO::FETCH_ASSOC);
        $cash_sales = (float)($sales['total_cash_sales'] ?? 0);

        // 3. Restar Salidas de Efectivo
        $stmtExpenses = $this->db->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE cash_register_shift_id = ?");
        $stmtExpenses->execute([$shift_id]);
        $expensesRows = $stmtExpenses->fetch(PDO::FETCH_ASSOC);
        $expenses = (float)($expensesRows['total_expenses'] ?? 0);

        $expected_ending_cash = $starting_cash + $cash_sales - $expenses;
        $discrepancy_amount = (float)$actual_ending_cash - $expected_ending_cash;

        // Actualizar el turno
        $updateStmt = $this->db->prepare("UPDATE cash_register_shifts SET status = 'CLOSED', closed_by_user_id = ?, closed_at = CURRENT_TIMESTAMP, expected_ending_cash = ?, actual_ending_cash = ?, discrepancy_amount = ? WHERE id = ?");
        
        if ($updateStmt->execute([$user_id, $expected_ending_cash, $actual_ending_cash, $discrepancy_amount, $shift_id])) {
            echo json_encode([
                "message" => "Caja cerrada exitosamente.",
                "summary" => [
                    "starting_cash" => $starting_cash,
                    "cash_sales" => $cash_sales,
                    "expenses" => $expenses,
                    "expected_ending_cash" => $expected_ending_cash,
                    "actual_ending_cash" => $actual_ending_cash,
                    "discrepancy_amount" => $discrepancy_amount
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al cerrar la caja."]);
        }
    }

    // GET /api.php/cash-register/status?company_id=1
    public function getActiveStatus() {
        $company_id = $_GET['company_id'] ?? null;
        if (!$company_id) {
            http_response_code(400);
            echo json_encode(["message" => "Se requiere company_id."]);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM cash_register_shifts WHERE company_id = ? AND status = 'OPEN' LIMIT 1");
        $stmt->execute([$company_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($shift) {
            // Obtener ventas actuales (Live)
            $stmtSales = $this->db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE cash_register_shift_id = ? AND payment_method = 'CASH' AND status IN ('COMPLETED', 'READY')");
            $stmtSales->execute([$shift['id']]);
            $cash_sales = (float)$stmtSales->fetchColumn();

            // Obtener gastos actuales (Live)
            $stmtExp = $this->db->prepare("SELECT SUM(amount) as total FROM expenses WHERE cash_register_shift_id = ?");
            $stmtExp->execute([$shift['id']]);
            $expenses = (float)$stmtExp->fetchColumn();

            $expected_cash = (float)$shift['starting_cash'] + $cash_sales - $expenses;

            echo json_encode([
                "has_active_shift" => true, 
                "shift" => $shift,
                "metrics" => [
                    "cash_sales" => $cash_sales,
                    "expenses" => $expenses,
                    "expected_cash" => $expected_cash
                ]
            ]);
        } else {
            echo json_encode(["has_active_shift" => false]);
        }
    }
}
