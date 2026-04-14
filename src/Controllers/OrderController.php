<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class OrderController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api.php/orders/{id}
    public function getOne($id) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            return;
        }

        // Obtener items
        $sqlItems = "SELECT oi.*, p.name as product_name 
                     FROM order_items oi 
                     JOIN products p ON oi.product_id = p.id 
                     WHERE oi.order_id = :oid";
        $stmtItems = $this->db->prepare($sqlItems);
        $stmtItems->execute([':oid' => $id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            if ($item['selected_modifiers']) {
                $decoded = json_decode($item['selected_modifiers'], true);
                if (is_array($decoded) && isset($decoded['options'])) {
                    $modText = implode(', ', $decoded['options']);
                    if (!empty($decoded['instructions'])) {
                        $modText .= "\n📝 " . $decoded['instructions'];
                    }
                    $item['modifiers'] = $modText;
                } else if (is_array($decoded)) {
                    $item['modifiers'] = implode(', ', $decoded);
                }
            }
        }
        $order['items'] = $items;

        echo json_encode($order);
    }
    
    // GET /api.php/orders?company_id=...
    public function getByCompany($companyId) {
        // Traer ordenes recientes (ej: últimas 24h o activas)
        $sql = "SELECT * FROM orders WHERE company_id = :cid ORDER BY created_at DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $companyId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Poblar items para cada orden
        // TODO: Optimizar con JOIN único si escala
        foreach ($orders as &$order) {
            $sqlItems = "SELECT oi.*, p.name as product_name 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = :oid";
            $stmtItems = $this->db->prepare($sqlItems);
            $stmtItems->execute([':oid' => $order['id']]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar JSON de modifier names si agregamos eso, por ahora solo raw
            // En order_items guardé selected_modifiers como JSON string ["Queso", "Salsa"]
            foreach ($items as &$item) {
                if ($item['selected_modifiers']) {
                    $decoded = json_decode($item['selected_modifiers'], true);
                    if (is_array($decoded) && isset($decoded['options'])) {
                        // Nuevo formato
                        $modText = implode(', ', $decoded['options']);
                        if (!empty($decoded['instructions'])) {
                            $modText .= "\n📝 " . $decoded['instructions'];
                        }
                        $item['modifiers'] = $modText;
                    } else if (is_array($decoded)) {
                        // Viejo formato (solo array)
                        $item['modifiers'] = implode(', ', $decoded);
                    }
                }
            }
            $order['items'] = $items;
        }

        echo json_encode($orders);
    }

    // PUT /api.php/orders/{id}
    public function update($id, $data) {
        if (!isset($data['status'])) {
            http_response_code(400);
            return;
        }

        $allowedStatuses = ['PENDING', 'ACCEPTED', 'REJECTED', 'READY', 'COMPLETED', 'CANCELLED'];
        if (!in_array($data['status'], $allowedStatuses)) {
             http_response_code(400);
             echo json_encode(["message" => "Estado inválido"]);
             return;
        }

        $fields = "status = :status";
        $params = [':status' => $data['status'], ':id' => $id];

        // Si se envía tiempo estimado (en minutos)
        if (isset($data['estimated_minutes']) && is_numeric($data['estimated_minutes'])) {
            $minutes = intval($data['estimated_minutes']);
            $completionTime = date('Y-m-d H:i:s', strtotime("+$minutes minutes"));
            $fields .= ", estimated_completion_time = :time";
            $params[':time'] = $completionTime;
        }

        if (array_key_exists('payment_method', $data)) {
            $fields .= ", payment_method = :pm";
            $params[':pm'] = $data['payment_method'];
        }

        if (array_key_exists('cash_register_shift_id', $data)) {
            $fields .= ", cash_register_shift_id = :crsid";
            $params[':crsid'] = $data['cash_register_shift_id'];
        }

        $sql = "UPDATE orders SET $fields WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        echo json_encode(["message" => "Orden actualizada"]);
    }


    public function create() {
        $rawInput = file_get_contents('php://input');
        error_log("Raw Input: " . $rawInput);
        $input = json_decode($rawInput, true);

        // Validación básica
        if (!isset($input['company_id'], $input['items'], $input['order_type'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos", "received" => $input]);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Calcular total real en el backend
            $totalAmount = 0;
            $orderItemsData = []; // Para guardar y luego insertar

            foreach ($input['items'] as $item) {
                // Obtener precio base del producto
                $stmt = $this->db->prepare("SELECT price, name FROM products WHERE id = :id");
                $stmt->execute([':id' => $item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Producto ID {$item['product_id']} no encontrado");
                }

                $itemTotal = $product['price'];
                
                // Sumar modificadores si existen
                $selectedModifiers = [];
                if (isset($item['modifiers']) && is_array($item['modifiers'])) {
                    foreach ($item['modifiers'] as $modId) {
                        // $modId debería ser ID de modifier_options
                        $stmtMod = $this->db->prepare("SELECT price_adjustment, name FROM modifier_options WHERE id = :id");
                        $stmtMod->execute([':id' => $modId]);
                        $mod = $stmtMod->fetch(PDO::FETCH_ASSOC);
                        if ($mod) {
                            $itemTotal += $mod['price_adjustment'];
                            $selectedModifiers[] = $mod['name'];
                        }
                    }
                }

                $modifierData = [
                    'options' => $selectedModifiers,
                    'instructions' => $item['special_instructions'] ?? ''
                ];

                $lineTotal = $itemTotal * $item['quantity'];
                $totalAmount += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $itemTotal, // Precio unitario con modificadores incluidos
                    'selected_modifiers' => json_encode($modifierData)
                ];
            }

            // 2. Insertar Orden
            $sql = "INSERT INTO orders (company_id, customer_name, customer_phone, customer_address, order_type, table_id, payment_method, cash_register_shift_id, total_amount, status, scheduled_at) 
                    VALUES (:cid, :cname, :cphone, :caddr, :otype, :tid, :pm, :crsid, :total, 'PENDING', :sched)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cid' => $input['company_id'],
                ':cname' => $input['customer_name'] ?? 'Cliente',
                ':cphone' => $input['customer_phone'] ?? '',
                ':caddr' => $input['customer_address'] ?? '',
                ':otype' => $input['order_type'],
                ':tid' => $input['table_id'] ?? null,
                ':pm' => $input['payment_method'] ?? 'CASH',
                ':crsid' => $input['cash_register_shift_id'] ?? null,
                ':total' => $totalAmount,
                ':sched' => $input['scheduled_at'] ?? null
            ]);
            
            $orderId = $this->db->lastInsertId();

            // 3. Insertar Items
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, selected_modifiers) 
                        VALUES (:oid, :pid, :qty, :price, :mods)";
            $stmtItem = $this->db->prepare($sqlItem);

            foreach ($orderItemsData as $itemData) {
                $stmtItem->execute([
                    ':oid' => $orderId,
                    ':pid' => $itemData['product_id'],
                    ':qty' => $itemData['quantity'],
                    ':price' => $itemData['unit_price'],
                    ':mods' => $itemData['selected_modifiers']
                ]);
            }

            $this->db->commit();
            
            echo json_encode([
                "message" => "Pedido creado con éxito", 
                "order_id" => $orderId,
                "total" => $totalAmount
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Error al crear pedido: " . $e->getMessage()]);
        }
    }
}
