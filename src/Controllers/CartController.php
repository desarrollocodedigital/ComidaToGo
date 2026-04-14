<?php

namespace App\Controllers;

use App\Models\User;

class CartController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function sync() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['user_id'], $input['cart_data'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos"]);
            return;
        }

        // El cart_data debe venir como string JSON desde el frontend
        // pero aquí nos aseguramos de guardarlo como tal
        $cartData = is_array($input['cart_data']) ? json_encode($input['cart_data']) : $input['cart_data'];

        $result = $this->userModel->updateCart($input['user_id'], $cartData);

        if ($result) {
            echo json_encode(["message" => "Carrito sincronizado"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al sincronizar carrito"]);
        }
    }
}
