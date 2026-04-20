<?php

namespace App\Models;

use PDO;
use Exception;

class Review extends BaseModel {
    protected $table = 'reviews';

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (company_id, order_id, user_name, rating, comment) 
                VALUES (:cid, :oid, :uname, :rating, :comment)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':cid' => $data['company_id'],
            ':oid' => $data['order_id'],
            ':uname' => $data['user_name'] ?? 'Cliente Anónimo',
            ':rating' => $data['rating'],
            ':comment' => $data['comment'] ?? null
        ]);

        if ($result) {
            $this->updateCompanyAverage($data['company_id']);
        }

        return $result;
    }

    public function updateCompanyAverage($companyId) {
        // Calcular nuevo promedio
        $sql = "SELECT AVG(rating) as average, COUNT(*) as total FROM {$this->table} WHERE company_id = :cid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $companyId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stats) {
            $average = round($stats['average'], 2);
            $updateSql = "UPDATE companies SET average_rating = :avg WHERE id = :cid";
            $updateStmt = $this->db->prepare($updateSql);
            return $updateStmt->execute([
                ':avg' => $average,
                ':cid' => $companyId
            ]);
        }
        return false;
    }

    public function getByOrder($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE order_id = :oid");
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
