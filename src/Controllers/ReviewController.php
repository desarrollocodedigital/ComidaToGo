<?php

namespace App\Controllers;

use App\Models\Review;
use App\Models\Order;

class ReviewController {
    private $model;
    private $orderModel;

    public function __construct() {
        $this->model = new Review();
        $this->orderModel = new Order();
    }

    // POST /api.php/reviews
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validación básica
        if (!isset($input['order_id'], $input['rating'])) {
            http_response_code(400);
            echo json_encode(["message" => "order_id y rating son requeridos"]);
            return;
        }

        // Obtener la orden para validar estado y company_id
        $order = $this->orderModel->find($input['order_id']);
        if (!$order) {
            http_response_code(404);
            echo json_encode(["message" => "Pedido no encontrado"]);
            return;
        }

        if ($order['status'] !== 'COMPLETED') {
            http_response_code(400);
            echo json_encode(["message" => "Solo puedes calificar pedidos completados"]);
            return;
        }

        // Validar si ya existe una reseña para este pedido
        $existing = $this->model->getByOrder($input['order_id']);
        if ($existing) {
            http_response_code(400);
            echo json_encode(["message" => "Este pedido ya ha sido calificado"]);
            return;
        }

        // Preparar datos para el modelo
        $reviewData = [
            'company_id' => $order['company_id'],
            'order_id' => $input['order_id'],
            'user_name' => $order['customer_name'] ?? 'Cliente',
            'rating' => intval($input['rating']),
            'comment' => $input['comment'] ?? null
        ];

        $success = $this->model->create($reviewData);

        if ($success) {
            echo json_encode(["message" => "¡Gracias por tu calificación!"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al guardar la calificación"]);
        }
    }
}
