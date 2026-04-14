<?php

namespace App\Models;

use PDO;

class Product extends BaseModel {
    protected $table = 'products';

    public function getProductsByCompany($companyId) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                JOIN categories c ON p.category_id = c.id 
                WHERE c.company_id = :cid 
                ORDER BY c.sort_order ASC, p.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $companyId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$product) {
            $stmtMods = $this->db->prepare("SELECT modifier_group_id FROM product_modifiers WHERE product_id = :pid");
            $stmtMods->execute([':pid' => $product['id']]);
            $product['modifier_group_ids'] = $stmtMods->fetchAll(PDO::FETCH_COLUMN);
        }

        return $products;
    }

    public function createProduct($data) {
        $sql = "INSERT INTO {$this->table} (category_id, name, description, price, image_url, is_available) 
                VALUES (:cid, :keyname, :descr, :price, :img, :avail)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid' => $data['category_id'],
            ':keyname' => $data['name'],
            ':descr' => $data['description'] ?? '',
            ':price' => $data['price'],
            ':img' => $data['image_url'] ?? '',
            ':avail' => isset($data['is_available']) ? $data['is_available'] : 1
        ]);
        
        $productId = $this->db->lastInsertId();

        if (isset($data['modifier_group_ids']) && is_array($data['modifier_group_ids'])) {
            $stmtMod = $this->db->prepare("INSERT IGNORE INTO product_modifiers (product_id, modifier_group_id) VALUES (:pid, :gid)");
            foreach ($data['modifier_group_ids'] as $gid) {
                $stmtMod->execute([':pid' => $productId, ':gid' => $gid]);
            }
        }

        return $productId;
    }

    public function updateProduct($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                category_id = :cid, 
                name = :keyname, 
                description = :descr, 
                price = :price, 
                image_url = :img, 
                is_available = :avail 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid' => $data['category_id'],
            ':keyname' => $data['name'],
            ':descr' => $data['description'] ?? '',
            ':price' => $data['price'],
            ':img' => $data['image_url'] ?? '',
            ':avail' => isset($data['is_available']) ? $data['is_available'] : 1,
            ':id' => $id
        ]);

        if (isset($data['modifier_group_ids']) && is_array($data['modifier_group_ids'])) {
            $this->db->prepare("DELETE FROM product_modifiers WHERE product_id = :pid")->execute([':pid' => $id]);
            $stmtMod = $this->db->prepare("INSERT INTO product_modifiers (product_id, modifier_group_id) VALUES (:pid, :gid)");
            foreach ($data['modifier_group_ids'] as $gid) {
                $stmtMod->execute([':pid' => $id, ':gid' => $gid]);
            }
        }
        return true;
    }

    public function deleteProduct($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
