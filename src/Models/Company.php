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

        if ($company) {
            // Asegurar que el config sea un objeto para getIsOpenNow
            if (isset($company['schedule_config'])) {
                $company['schedule_config'] = is_string($company['schedule_config']) ? json_decode($company['schedule_config']) : $company['schedule_config'];
            }
            
            // Recalcular estado real-time basado en horario y Timezone
            $statusInfo = $this->getIsOpenNow($company);
            $company['is_open'] = $statusInfo['is_open'] ? 1 : 0;
            $company['status_info'] = $statusInfo;
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
            $stmtProd = $this->db->prepare("SELECT * FROM products WHERE category_id = :cat_id AND is_available = 1 ORDER BY is_featured DESC, name ASC");
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

    public function search($term, $type = 'negocios', $lat = null, $lng = null, $state = null) {
        $distanceSelect = "";
        $order = "name ASC";
        $whereClauses = [];
        $params = [':q' => "%$term%"];

        if ($lat !== null && $lng !== null) {
            // Usar ST_Distance_Sphere (Línea recta exacta para mayor claridad)
            $distanceSelect = ", (ST_Distance_Sphere(POINT(:lng, :lat), POINT(c.longitude, c.latitude)) / 1000) AS distance";
            $order = "distance ASC";
            $params[':lat'] = $lat;
            $params[':lng'] = $lng;
        }

        if ($state) {
            $whereClauses[] = "c.state LIKE :state";
            $params[':state'] = "%$state%";
        }

        if ($type === 'platillos') {
            $whereBase = "(p.name LIKE :q OR cat.name LIKE :q)";
            if ($whereClauses) {
                $whereBase .= " AND " . implode(" AND ", $whereClauses);
            }
            
            $sql = "SELECT p.*, c.name as company_name, c.slug as company_slug, c.logo_url as company_logo {$distanceSelect}
                    FROM products p
                    JOIN categories cat ON p.category_id = cat.id
                    JOIN {$this->table} c ON cat.company_id = c.id
                    WHERE {$whereBase}
                    ORDER BY {$order}
                    LIMIT 30";
        } else {
            $whereBase = "(c.name LIKE :q OR c.category LIKE :q OR cat.name LIKE :q)";
            if ($whereClauses) {
                $whereBase .= " AND " . implode(" AND ", $whereClauses);
            }

            $sql = "SELECT c.id, c.name, c.slug, c.logo_url, c.banner_url, c.is_open, c.average_rating, c.description, c.category, c.address, c.timezone, c.schedule_config, c.status_mode, c.state {$distanceSelect}
                    FROM {$this->table} c
                    LEFT JOIN categories cat ON c.id = cat.company_id
                    WHERE {$whereBase}
                    GROUP BY c.id
                    ORDER BY {$order}
                    LIMIT 20";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            if (isset($result['schedule_config'])) {
                // Decodificar para que getIsOpenNow pueda leerlo
                $config = is_string($result['schedule_config']) ? json_decode($result['schedule_config']) : $result['schedule_config'];
                $result['schedule_config'] = $config;
                // Calcular estado real-time
                $statusInfo = $this->getIsOpenNow($result);
                $result['is_open'] = $statusInfo['is_open'] ? 1 : 0;
                $result['status_info'] = $statusInfo;
            }
        }

        return $results;
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                name = :name,
                description = :description,
                address = :address,
                latitude = :latitude,
                longitude = :longitude,
                timezone = :timezone,
                status_mode = :status_mode,
                logo_url = :logo_url,
                banner_url = :banner_url,
                is_open = :is_open,
                schedule_config = :schedule_config,
                category = :category,
                state = :state,
                printer_width = :printer_width
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':address' => $data['address'] ?? null,
            ':latitude' => $data['latitude'] ?? null,
            ':longitude' => $data['longitude'] ?? null,
            ':timezone' => $data['timezone'] ?? 'America/Mexico_City',
            ':status_mode' => $data['status_mode'] ?? 'AUTO',
            ':logo_url' => $data['logo_url'] ?? null,
            ':banner_url' => $data['banner_url'] ?? null,
            ':is_open' => $data['is_open'] ? 1 : 0,
            ':schedule_config' => is_string($data['schedule_config']) ? $data['schedule_config'] : json_encode($data['schedule_config']),
            ':category' => $data['category'] ?? 'Restaurante',
            ':state' => $data['state'] ?? null,
            ':printer_width' => $data['printer_width'] ?? '80',
            ':id' => $id
        ]);
    }

    public function getIsOpenNow($company) {
        $mode = $company['status_mode'] ?? 'AUTO';
        
        if ($mode === 'OPEN') {
            return [
                'is_open' => true,
                'status' => 'OPEN',
                'message' => 'Abierto ahora'
            ];
        }
        
        if ($mode === 'CLOSED') {
            return [
                'is_open' => false,
                'status' => 'CLOSED',
                'message' => 'Cerrado temporalmente'
            ];
        }

        // Lógica AUTO
        try {
            $tz = new \DateTimeZone($company['timezone'] ?: 'America/Mexico_City');
            $now = new \DateTime('now', $tz);
            $day = strtolower($now->format('D')); // mon, tue...
            $currentTime = $now->format('H:i');

            $schedule = $company['schedule_config'];
            if (is_string($schedule)) $schedule = json_decode($schedule);

            if (!isset($schedule->$day) || ($schedule->$day->closed ?? false)) {
                // Buscar el siguiente día abierto
                return [
                    'is_open' => false,
                    'status' => 'CLOSED',
                    'message' => 'Cerrado hoy'
                ];
            }

            $open = $schedule->$day->open ?? '00:00';
            $close = $schedule->$day->close ?? '23:59';

            if ($currentTime < $open) {
                return [
                    'is_open' => false,
                    'status' => 'OPENING_SOON',
                    'next_opening' => $open,
                    'message' => "Abre a las {$open}"
                ];
            }

            if ($currentTime > $close) {
                return [
                    'is_open' => false,
                    'status' => 'CLOSED',
                    'message' => 'Cerrado por hoy'
                ];
            }

            return [
                'is_open' => true,
                'status' => 'OPEN',
                'message' => 'Abierto ahora'
            ];
        } catch (\Exception $e) {
            return [
                'is_open' => false,
                'status' => 'ERROR',
                'message' => 'Horario no disponible'
            ];
        }
    }
}
