<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class ExpenseController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // POST /api.php/expenses
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        $company_id = $input['company_id'] ?? null;
        $cash_register_shift_id = $input['cash_register_shift_id'] ?? null;
        $user_id = $input['user_id'] ?? null;
        $amount = $input['amount'] ?? null;
        $category = $input['category'] ?? 'OTHER';
        $description = $input['description'] ?? '';

        if (!$company_id || !$cash_register_shift_id || !$user_id || !$amount || !$description) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos obligatorios para registrar la salida."]);
            return;
        }

        // Verificar que el turno de caja siga abierto
        $stmtShift = $this->db->prepare("SELECT status FROM cash_register_shifts WHERE id = ?");
        $stmtShift->execute([$cash_register_shift_id]);
        $shiftStatus = $stmtShift->fetchColumn();

        if ($shiftStatus !== 'OPEN') {
            http_response_code(400);
            echo json_encode(["message" => "No se pueden registrar gastos en un turno de caja cerrado."]);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO expenses (company_id, cash_register_shift_id, user_id, amount, category, description) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$company_id, $cash_register_shift_id, $user_id, $amount, $category, $description])) {
            http_response_code(201);
            echo json_encode([
                "message" => "Gasto registrado exitosamente.",
                "expense_id" => $this->db->lastInsertId()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al registrar el gasto."]);
        }
    }
}
