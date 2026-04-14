<?php

namespace App\Models;

use PDO;

class Company extends BaseModel {
    protected $table = 'companies';

    public function findBySlugOrId($param) {
        if (is_numeric($param)) {
            $sql = "SELECT * FROM {$this->table} WHERE id = :p";
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE slug = :p";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $param]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($company && isset($company['schedule_config'])) {
            $company['schedule_config'] = json_decode($company['schedule_config']);
        }

        return $company;
    }

    public function getCategoriesWithProductsAndModifiers($companyId) {
        // Obtenemos categorías
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE company_id = :id ORDER BY sort_order ASC");
        $stmt->execute([':id' => $companyId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Poblamos productos y modificadores
        foreach ($categories as &$category) {
            $stmtProd = $this->db->prepare("SELECT * FROM products WHERE category_id = :cat_id AND is_available = 1");
            $stmtProd->execute([':cat_id' => $category['id']]);
            $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as &$product) {
                $product['modifier_groups'] = $this->getProductModifiers($product['id']);
            }
            $category['products'] = $products;
        }

        return $categories;
    }

    private function getProductModifiers($productId) {
        $sql = "SELECT mg.* 
                FROM modifier_groups mg
                JOIN product_modifiers pm ON mg.id = pm.modifier_group_id
                WHERE pm.product_id = :pid
                ORDER BY pm.sort_order ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $productId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($groups as &$group) {
            $stmtOpt = $this->db->prepare("SELECT * FROM modifier_options WHERE modifier_group_id = :gid");
            $stmtOpt->execute([':gid' => $group['id']]);
            $group['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $groups;
    }

    public function search($term) {
        $sql = "SELECT id, name, slug, logo_url, banner_url, is_open, average_rating FROM {$this->table} WHERE name LIKE :q LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':q' => "%$term%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
