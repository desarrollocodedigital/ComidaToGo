<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class ChatController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api.php/chat?company_id=1&user_id=5
    // Obtener la conversación actual o crearla si no existe
    public function getConversation() {
        $company_id = $_GET['company_id'] ?? null;
        $user_id = $_GET['user_id'] ?? null;
        // order_id opcional

        if (!$company_id || !$user_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Faltan parámetros de empresa y usuario']);
            return;
        }

        // 1. Buscar si hay chat abierto
        $stmt = $this->db->prepare("SELECT id FROM chats WHERE company_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$company_id, $user_id]);
        $chat = $stmt->fetch(PDO::FETCH_ASSOC);

        $chat_id = null;

        if ($chat) {
            $chat_id = $chat['id'];
        } else {
            // 2. Crear chat nuevo
            $insert = $this->db->prepare("INSERT INTO chats (company_id, user_id) VALUES (?, ?)");
            $insert->execute([$company_id, $user_id]);
            $chat_id = $this->db->lastInsertId();
        }

        // 3. Obtener mensajes
        $messagesStmt = $this->db->prepare("SELECT * FROM chat_messages WHERE chat_id = ? ORDER BY created_at ASC");
        $messagesStmt->execute([$chat_id]);
        $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "chat_id" => $chat_id,
            "messages" => $messages
        ]);
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

        $stmt = $this->db->prepare("INSERT INTO chat_messages (chat_id, sender_type, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$chat_id, $sender_type, $message])) {
            http_response_code(201);
            echo json_encode([
                "message_id" => $this->db->lastInsertId(),
                "status" => "sent"
            ]);
        } else {
             http_response_code(500);
        }
    }
}
