<?php

namespace App\Models;

use PDO;

class Analytics extends BaseModel {
    
    public function getSalesSummary($companyId, $period) {
        $dateCondition = "1";
        switch($period) {
            case 'day': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'week': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'month': $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; break;
            case 'year': $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; break;
        }

        $stmtSales = $this->db->prepare("SELECT SUM(total_amount) as gross_sales, COUNT(id) as total_orders FROM orders WHERE company_id = ? AND status IN ('COMPLETED', 'READY') AND $dateCondition");
        $stmtSales->execute([$companyId]);
        $salesData = $stmtSales->fetch(PDO::FETCH_ASSOC);
        
        $grossSales = (float)($salesData['gross_sales'] ?? 0);
        $totalOrders = (int)($salesData['total_orders'] ?? 0);
        
        $stmtExp = $this->db->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE company_id = ? AND $dateCondition");
        $stmtExp->execute([$companyId]);
        $expData = $stmtExp->fetch(PDO::FETCH_ASSOC);
        
        $totalExpenses = (float)($expData['total_expenses'] ?? 0);
        $netProfit = $grossSales - $totalExpenses;
        $averageTicket = $totalOrders > 0 ? ($grossSales / $totalOrders) : 0;

        return [
            "gross_sales" => $grossSales,
            "total_expenses" => $totalExpenses,
            "net_profit" => $netProfit,
            "total_orders" => $totalOrders,
            "average_ticket" => round($averageTicket, 2)
        ];
    }

    public function getTopProducts($companyId) {
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
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
