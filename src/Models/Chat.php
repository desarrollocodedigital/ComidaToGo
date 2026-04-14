<?php

namespace App\Models;

use PDO;

class Chat extends BaseModel {
    protected $table = 'chats';

    public function getConversation($companyId, $userId) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE company_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$companyId, $userId]);
        $chat = $stmt->fetch(PDO::FETCH_ASSOC);

        $chatId = null;

        if ($chat) {
            $chatId = $chat['id'];
        } else {
            $insert = $this->db->prepare("INSERT INTO {$this->table} (company_id, user_id) VALUES (?, ?)");
            $insert->execute([$companyId, $userId]);
            $chatId = $this->db->lastInsertId();
        }

        $messagesStmt = $this->db->prepare("SELECT * FROM chat_messages WHERE chat_id = ? ORDER BY created_at ASC");
        $messagesStmt->execute([$chatId]);
        $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "chat_id" => $chatId,
            "messages" => $messages
        ];
    }

    public function sendMessage($chatId, $senderType, $message) {
        $stmt = $this->db->prepare("INSERT INTO chat_messages (chat_id, sender_type, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$chatId, $senderType, $message])) {
            return ["success" => true, "message_id" => $this->db->lastInsertId()];
        }
        return ["success" => false];
    }
}
