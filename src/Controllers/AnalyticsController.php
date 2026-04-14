<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class AnalyticsController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api.php/analytics/sales?company_id=1&period=month
    public function getSalesSummary() {
        $company_id = $_GET['company_id'] ?? null;
        $period = $_GET['period'] ?? 'month'; // 'day', 'week', 'month', 'year'

        if (!$company_id) {
            http_response_code(400);
            echo json_encode(["message" => "Falta company_id"]);
            return;
        }

        // Determinar rango de fechas basado en MySQL
        $dateCondition = "1";
        switch($period) {
            case 'day': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'week': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break; // Inicio Lunes
            case 'month': $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; break;
            case 'year': $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; break;
        }

        // 1. Obtener Ventas Totales (Brutas)
        $stmtSales = $this->db->prepare("SELECT SUM(total_amount) as gross_sales, COUNT(id) as total_orders FROM orders WHERE company_id = ? AND status IN ('COMPLETED', 'READY') AND $dateCondition");
        $stmtSales->execute([$company_id]);
        $salesData = $stmtSales->fetch(PDO::FETCH_ASSOC);
        
        $grossSales = (float)($salesData['gross_sales'] ?? 0);
        $totalOrders = (int)($salesData['total_orders'] ?? 0);
        
        // 2. Obtener Gastos/Salidas del mismo periodo
        $stmtExp = $this->db->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE company_id = ? AND $dateCondition");
        $stmtExp->execute([$company_id]);
        $expData = $stmtExp->fetch(PDO::FETCH_ASSOC);
        
        $totalExpenses = (float)($expData['total_expenses'] ?? 0);

        // 3. Calcular Métrica "Real Profit" (Ganancia Neta Base)
        $netProfit = $grossSales - $totalExpenses;
        $averageTicket = $totalOrders > 0 ? ($grossSales / $totalOrders) : 0;

        echo json_encode([
            "period" => $period,
            "metrics" => [
                "gross_sales" => $grossSales,
                "total_expenses" => $totalExpenses,
                "net_profit" => $netProfit,
                "total_orders" => $totalOrders,
                "average_ticket" => round($averageTicket, 2)
            ]
        ]);
    }

    // GET /api.php/analytics/top-products?company_id=1
    public function getTopProducts() {
        $company_id = $_GET['company_id'] ?? null;
        if (!$company_id) {
            http_response_code(400);
            return;
        }

        // Platos más vendidos históricamente o en el mes actual
        $query = "
            SELECT p.name, p.id, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.company_id = ? AND o.status IN ('COMPLETED', 'READY')
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$company_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["top_products" => $results]);
    }
}
