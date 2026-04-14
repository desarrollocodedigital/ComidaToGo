<?php

namespace App\Controllers;

use App\Models\CashRegister;

class CashRegisterController {
    private $model;

    public function __construct() {
        $this->model = new CashRegister();
    }

    // POST /api.php/cash-register/open
    public function open() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $company_id = $input['company_id'] ?? null;
        $user_id = $input['user_id'] ?? null;
        $starting_cash = $input['starting_cash'] ?? 0;

        if (!$company_id || !$user_id) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos obligatorios (company_id, user_id)"]);
            return;
        }

        $result = $this->model->openShift($company_id, $user_id, $starting_cash);

        if ($result['success']) {
            http_response_code(201);
            echo json_encode(["message" => "Caja abierta exitosamente", "shift_id" => $result['shift_id']]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => $result['message']]);
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

        $result = $this->model->closeShift($shift_id, $user_id, $actual_ending_cash);

        if ($result['success']) {
            echo json_encode([
                "message" => "Caja cerrada exitosamente.",
                "summary" => $result['summary']
            ]);
        } else {
            http_response_code(400); // Or 404 / 500 depending on exact message
            echo json_encode(["message" => $result['message']]);
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

        $status = $this->model->getStatusWithMetrics($company_id);
        echo json_encode($status);
    }
}

