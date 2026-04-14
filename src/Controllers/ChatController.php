<?php

namespace App\Controllers;

use App\Models\Chat;

class ChatController {
    private $model;

    public function __construct() {
        $this->model = new Chat();
    }

    // GET /api.php/chat?company_id=1&user_id=5
    public function getConversation() {
        $company_id = $_GET['company_id'] ?? null;
        $user_id = $_GET['user_id'] ?? null;

        if (!$company_id || !$user_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Faltan parámetros de empresa y usuario']);
            return;
        }

        $result = $this->model->getConversation($company_id, $user_id);
        echo json_encode($result);
    }

    // POST /api.php/chat/message
    public function sendMessage() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $chat_id = $input['chat_id'] ?? null;
        $sender_type = $input['sender_type'] ?? null; // 'CUSTOMER' o 'COMPANY'
        $message = $input['message'] ?? null;

        if (!$chat_id || !$sender_type || !$message) {
            http_response_code(400);
            echo json_encode(['message' => 'Faltan datos del mensaje.']);
            return;
        }

        $result = $this->model->sendMessage($chat_id, $sender_type, $message);

        if ($result['success']) {
            http_response_code(201);
            echo json_encode([
                "message_id" => $result['message_id'],
                "status" => "sent"
            ]);
        } else {
             http_response_code(500);
        }
    }
}

