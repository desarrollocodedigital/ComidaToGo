<?php

namespace App\Models;

use PDO;
use Exception;

class Order extends BaseModel {
    protected $table = 'orders';

    public function getOrderWithItems($id) {
        $stmt = $this->db->prepare("SELECT o.*, t.name as table_name, t.table_number 
                                    FROM {$this->table} o 
                                    LEFT JOIN restaurant_tables t ON o.table_id = t.id 
                                    WHERE o.id = :id");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($id);
        return $order;
    }

    public function getOrdersByCompany($companyId, $limit = 50) {
        $sql = "SELECT o.*, t.name as table_name, t.table_number 
                FROM {$this->table} o 
                LEFT JOIN restaurant_tables t ON o.table_id = t.id 
                WHERE o.company_id = :cid 
                ORDER BY o.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        return $orders;
    }

    public function getOrdersByPhone($phone, $limit = 20) {
        $sql = "SELECT o.*, c.name as company_name, c.logo_url as company_logo,
                (SELECT COUNT(*) FROM reviews r WHERE r.order_id = o.id) as has_review
                FROM {$this->table} o 
                JOIN companies c ON o.company_id = c.id 
                WHERE o.customer_phone = :phone 
                ORDER BY o.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':phone', $phone);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
            $order['has_review'] = (bool)$order['has_review'];
        }

        return $orders;
    }

    private function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :oid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':oid' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        return $items;
    }

    public function updateOrderData($id, $data) {
        $fields = "status = :status";
        $params = [':status' => $data['status'], ':id' => $id];

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

        // Registrar timestamps de eficacia de cocina
        if ($data['status'] === 'ACCEPTED') {
            $fields .= ", accepted_at = NOW()";
        } elseif ($data['status'] === 'READY') {
            $fields .= ", ready_at = NOW()";
        }

        if (array_key_exists('rejection_reason', $data)) {
            $fields .= ", rejection_reason = :reason";
            $params[':reason'] = $data['rejection_reason'];
        }

        $sql = "UPDATE {$this->table} SET $fields WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        // Liberar mesa si el pedido finaliza
        if ($result && in_array($data['status'], ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
            $stmtOrder = $this->db->prepare("SELECT table_id FROM {$this->table} WHERE id = :id AND order_type = 'DINE_IN'");
            $stmtOrder->execute([':id' => $id]);
            $orderRec = $stmtOrder->fetch(PDO::FETCH_ASSOC);
            if ($orderRec && $orderRec['table_id']) {
                $stmtTable = $this->db->prepare("UPDATE restaurant_tables SET status = 'AVAILABLE' WHERE id = :tid");
                $stmtTable->execute([':tid' => $orderRec['table_id']]);
            }
        }
        
        return $result;
    }

    public function createOrderWithItems($input) {
        $this->db->beginTransaction();
        try {
            $totalAmount = 0;
            $orderItemsData = [];

            foreach ($input['items'] as $item) {
                $stmt = $this->db->prepare("SELECT price, name FROM products WHERE id = :id");
                $stmt->execute([':id' => $item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Producto ID {$item['product_id']} no encontrado");
                }

                $itemTotal = $product['price'];
                $selectedModifiers = [];
                if (isset($item['modifiers']) && is_array($item['modifiers'])) {
                    foreach ($item['modifiers'] as $modId) {
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
                    'unit_price' => $itemTotal,
                    'selected_modifiers' => json_encode($modifierData)
                ];
            }

            $sql = "INSERT INTO {$this->table} (company_id, customer_name, customer_phone, customer_address, customer_references, order_type, table_id, payment_method, cash_register_shift_id, total_amount, status, scheduled_at, accepted_at) 
                    VALUES (:cid, :cname, :cphone, :caddr, :cref, :otype, :tid, :pm, :crsid, :total, :status, :sched, :accepted_at)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cid' => $input['company_id'],
                ':cname' => $input['customer_name'] ?? 'Cliente',
                ':cphone' => $input['customer_phone'] ?? '',
                ':caddr' => $input['customer_address'] ?? '',
                ':cref' => $input['customer_references'] ?? '',
                ':otype' => $input['order_type'],
                ':tid' => $input['table_id'] ?? null,
                ':pm' => $input['payment_method'] ?? 'CASH',
                ':crsid' => $input['cash_register_shift_id'] ?? null,
                ':total' => $totalAmount,
                ':status' => $input['status'] ?? 'PENDING',
                ':sched' => $input['scheduled_at'] ?? null,
                ':accepted_at' => (isset($input['status']) && $input['status'] === 'ACCEPTED') ? date('Y-m-d H:i:s') : null
            ]);
            
            $orderId = $this->db->lastInsertId();

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

            // Ocupar la mesa si es para comer en el local
            if ($input['order_type'] === 'DINE_IN' && !empty($input['table_id'])) {
                $stmtTable = $this->db->prepare("UPDATE restaurant_tables SET status = 'OCCUPIED' WHERE id = :tid");
                $stmtTable->execute([':tid' => $input['table_id']]);
            }

            $this->db->commit();
            
            return [
                "success" => true,
                "order_id" => $orderId,
                "total" => $totalAmount
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }

    public function appendItemsToOrder($orderId, $newItems) {
        $this->db->beginTransaction();
        try {
            $orderStmt = $this->db->prepare("SELECT total_amount, status FROM {$this->table} WHERE id = :id FOR UPDATE");
            $orderStmt->execute([':id' => $orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception("Orden no encontrada");
            }

            if (in_array($order['status'], ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
                throw new Exception("No se pueden agregar productos a una orden finalizada (Estado: {$order['status']})");
            }

            $additionalTotal = 0;
            $orderItemsData = [];

            foreach ($newItems as $item) {
                $stmt = $this->db->prepare("SELECT price, name FROM products WHERE id = :id");
                $stmt->execute([':id' => $item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Producto ID {$item['product_id']} no encontrado");
                }

                $itemTotal = $product['price'];
                $selectedModifiers = [];
                if (isset($item['modifiers']) && is_array($item['modifiers'])) {
                    foreach ($item['modifiers'] as $modId) {
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
                $additionalTotal += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $itemTotal,
                    'selected_modifiers' => json_encode($modifierData)
                ];
            }

            // Actualizar total de la orden y resetear estado a ACCEPTED para que vuelva a cocina
            $newTotal = $order['total_amount'] + $additionalTotal;
            $updateStmt = $this->db->prepare("UPDATE {$this->table} SET total_amount = :total, status = 'ACCEPTED', accepted_at = NOW() WHERE id = :id");
            $updateStmt->execute([':total' => $newTotal, ':id' => $orderId]);

            // Resetear flag de adición anterior en items existentes
            $resetStmt = $this->db->prepare("UPDATE order_items SET is_addition = 0 WHERE order_id = :oid");
            $resetStmt->execute([':oid' => $orderId]);

            // Insertar nuevos ítems con is_addition = 1
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, selected_modifiers, is_addition) 
                        VALUES (:oid, :pid, :qty, :price, :mods, 1)";
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
            
            return [
                "success" => true,
                "additional_total" => $additionalTotal,
                "new_total" => $newTotal
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }
}
