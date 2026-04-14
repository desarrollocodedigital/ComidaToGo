<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class CompanyController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api.php/tenant/{slug_or_id}
    public function getTenant($param) {
        if (is_numeric($param)) {
            $sql = "SELECT * FROM companies WHERE id = :p";
        } else {
            $sql = "SELECT * FROM companies WHERE slug = :p";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $param]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            http_response_code(404);
            echo json_encode(["message" => "Empresa no encontrada"]);
            return;
        }

        // Parsear JSON fields
        if(isset($company['schedule_config'])) {
            $company['schedule_config'] = json_decode($company['schedule_config']);
        }

        // 2. Obtener Categorías
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE company_id = :id ORDER BY sort_order ASC");
        $stmt->bindParam(':id', $company['id']);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Poblar Productos por Categoría
        foreach ($categories as &$category) {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE category_id = :cat_id AND is_available = 1");
            $stmt->bindParam(':cat_id', $category['id']);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Poblar Modificadores para cada producto (costoso pero necesario para MVP)
            // Una optimización sería traer todos los modificadores de la company y mapear en memoria
            foreach ($products as &$product) {
                $product['modifier_groups'] = $this->getProductModifiers($product['id']);
            }
            
            $category['products'] = $products;
        }

        $company['menu'] = $categories;

        echo json_encode($company);
    }

    private function getProductModifiers($productId) {
        // Obtener grupos vinculados al producto
        // modifier_groups table is company-level, we link via product_modifiers pivot
        $sql = "SELECT mg.* 
                FROM modifier_groups mg
                JOIN product_modifiers pm ON mg.id = pm.modifier_group_id
                WHERE pm.product_id = :pid
                ORDER BY pm.sort_order ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($groups as &$group) {
            // Obtener opciones para el grupo
            $stmtOpt = $this->db->prepare("SELECT * FROM modifier_options WHERE modifier_group_id = :gid");
            $stmtOpt->bindParam(':gid', $group['id']);
            $stmtOpt->execute();
            $group['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $groups;
    }

    // GET /api/search?q=...
    public function search($queryParams) {
        $q = isset($queryParams['q']) ? $queryParams['q'] : '';
        // Búsqueda simple por nombre
        $sql = "SELECT id, name, slug, logo_url, banner_url, is_open, average_rating FROM companies WHERE name LIKE :q LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $searchTerm = "%$q%";
        $stmt->bindParam(':q', $searchTerm);
        $stmt->execute();
        
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
