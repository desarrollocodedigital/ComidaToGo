<?php

namespace App\Controllers;

use App\Models\Expense;

class ExpenseController {
    private $model;

    public function __construct() {
        $this->model = new Expense();
    }

    // POST /api.php/expenses
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['company_id'], $input['cash_register_shift_id'], $input['user_id'], $input['amount'], $input['description'])) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos obligatorios para registrar la salida."]);
            return;
        }

        $result = $this->model->createExpense($input);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode([
                "message" => "Gasto registrado exitosamente.",
                "expense_id" => $result['expense_id']
            ]);
        } else {
            http_response_code(400); // Bad Request o Internal Server Error según corresponda.
            echo json_encode(["message" => $result['message']]);
        }
    }
}

