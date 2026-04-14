<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class ProductController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByCompany($companyId) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE c.company_id = :cid 
                ORDER BY c.sort_order ASC, p.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $companyId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Traer los grupos de modificadores asignados a cada producto
        foreach ($products as &$product) {
            $stmtMods = $this->db->prepare("SELECT modifier_group_id FROM product_modifiers WHERE product_id = :pid");
            $stmtMods->execute([':pid' => $product['id']]);
            $product['modifier_group_ids'] = $stmtMods->fetchAll(PDO::FETCH_COLUMN);
        }

        echo json_encode($products);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['category_id'], $input['name'], $input['price'])) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos requeridos"]);
            return;
        }

        $sql = "INSERT INTO products (category_id, name, description, price, image_url, is_available) 
                VALUES (:cid, :keyname, :descr, :price, :img, :avail)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid' => $input['category_id'],
            ':keyname' => $input['name'],
            ':descr' => $input['description'] ?? '',
            ':price' => $input['price'],
            ':img' => $input['image_url'] ?? '',
            ':avail' => isset($input['is_available']) ? $input['is_available'] : 1
        ]);
        
        $productId = $this->db->lastInsertId();

        if (isset($input['modifier_group_ids']) && is_array($input['modifier_group_ids'])) {
            $stmtMod = $this->db->prepare("INSERT IGNORE INTO product_modifiers (product_id, modifier_group_id) VALUES (:pid, :gid)");
            foreach ($input['modifier_group_ids'] as $gid) {
                $stmtMod->execute([':pid' => $productId, ':gid' => $gid]);
            }
        }

        echo json_encode(["id" => $productId, "message" => "Producto creado"]);
    }

    public function update($id, $input) {
        if (!isset($input['name'], $input['price'])) {
            http_response_code(400);
            return;
        }

        $sql = "UPDATE products SET 
                category_id = :cid, 
                name = :keyname, 
                description = :descr, 
                price = :price, 
                image_url = :img, 
                is_available = :avail 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid' => $input['category_id'],
            ':keyname' => $input['name'],
            ':descr' => $input['description'] ?? '',
            ':price' => $input['price'],
            ':img' => $input['image_url'] ?? '',
            ':avail' => isset($input['is_available']) ? $input['is_available'] : 1,
            ':id' => $id
        ]);

        if (isset($input['modifier_group_ids']) && is_array($input['modifier_group_ids'])) {
            $this->db->prepare("DELETE FROM product_modifiers WHERE product_id = :pid")->execute([':pid' => $id]);
            $stmtMod = $this->db->prepare("INSERT INTO product_modifiers (product_id, modifier_group_id) VALUES (:pid, :gid)");
            foreach ($input['modifier_group_ids'] as $gid) {
                $stmtMod->execute([':pid' => $id, ':gid' => $gid]);
            }
        }

        echo json_encode(["message" => "Producto actualizado"]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["message" => "Producto eliminado"]);
    }
}
