<?php

namespace App\Models;

use PDO;
use Exception;

class Order extends BaseModel {
    protected $table = 'orders';

    public function getOrderWithItems($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($id);
        return $order;
    }

    public function getOrdersByCompany($companyId, $limit = 50) {
        $sql = "SELECT * FROM {$this->table} WHERE company_id = :cid ORDER BY created_at DESC LIMIT :limit";
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

        $sql = "UPDATE {$this->table} SET $fields WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
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

            $sql = "INSERT INTO {$this->table} (company_id, customer_name, customer_phone, customer_address, order_type, table_id, payment_method, cash_register_shift_id, total_amount, status, scheduled_at) 
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
}
