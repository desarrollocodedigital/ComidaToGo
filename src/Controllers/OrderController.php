<?php

namespace App\Controllers;

use App\Models\Order;

class OrderController {
    private $model;

    public function __construct() {
        $this->model = new Order();
    }

    // GET /api.php/orders/{id}
    public function getOne($id) {
        $order = $this->model->getOrderWithItems($id);

        if (!$order) {
            http_response_code(404);
            return;
        }

        echo json_encode($order);
    }
    
    // GET /api.php/orders/customer?phone=...
    public function getByCustomer() {
        $phone = $_GET['phone'] ?? null;
        if (!$phone) {
            http_response_code(400);
            echo json_encode(["message" => "Teléfono requerido"]);
            return;
        }

        $orders = $this->model->getOrdersByPhone($phone);
        echo json_encode($orders);
    }

    // GET /api.php/orders?company_id=...
    public function getByCompany($companyId) {
        $orders = $this->model->getOrdersByCompany($companyId);
        echo json_encode($orders);
    }

    // PUT /api.php/orders/{id}
    public function update($id, $data) {
        if (!isset($data['status'])) {
            http_response_code(400);
            return;
        }

        $allowedStatuses = ['PENDING', 'ACCEPTED', 'REJECTED', 'READY', 'COMPLETED', 'CANCELLED'];
        if (!in_array($data['status'], $allowedStatuses)) {
             http_response_code(400);
             echo json_encode(["message" => "Estado inválido"]);
             return;
        }

        $success = $this->model->updateOrderData($id, $data);

        if ($success) {
            echo json_encode(["message" => "Orden actualizada"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar la orden"]);
        }
    }

    public function create() {
        $rawInput = file_get_contents('php://input');
        error_log("Raw Input: " . $rawInput);
        $input = json_decode($rawInput, true);

        // Validación básica
        if (!isset($input['company_id'], $input['items'], $input['order_type'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos", "received" => $input]);
            return;
        }

        // Validar si el negocio está abierto
        $companyModel = new \App\Models\Company();
        $company = $companyModel->findBySlugOrId($input['company_id']);
        
        if (!$company) {
            http_response_code(404);
            echo json_encode(["message" => "Negocio no encontrado"]);
            return;
        }

        if (!$company['is_open']) {
            http_response_code(403);
            $msg = $company['status_info']['message'] ?? "El negocio está cerrado en este momento.";
            echo json_encode([
                "message" => "No se pueden realizar pedidos: " . $msg,
                "status_info" => $company['status_info'] ?? null
            ]);
            return;
        }

        $result = $this->model->createOrderWithItems($input);

        if ($result['success']) {
            echo json_encode([
                "message" => "Pedido creado con éxito", 
                "order_id" => $result['order_id'],
                "total" => $result['total']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear pedido: " . $result['error']]);
        }
    }
}

