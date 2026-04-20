<?php

namespace App\Models;

use PDO;

class Analytics extends BaseModel {
    
    public function getSalesSummary($companyId, $period) {
        $dateCondition = "1";
        $prevDateCondition = "1";
        
        switch($period) {
            case 'day': 
                $dateCondition = "DATE(created_at) = CURDATE()"; 
                $prevDateCondition = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week': 
                $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; 
                $prevDateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
                break;
            case 'month': 
                $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; 
                $prevDateCondition = "YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(CURDATE())";
                break;
            case 'year': 
                $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; 
                $prevDateCondition = "YEAR(created_at) = YEAR(CURDATE()) - 1";
                break;
        }

        // Ventas Actuales
        $stmtSales = $this->db->prepare("SELECT SUM(total_amount) as gross_sales, COUNT(id) as total_orders FROM orders WHERE company_id = ? AND status IN ('COMPLETED', 'READY') AND $dateCondition");
        $stmtSales->execute([$companyId]);
        $salesData = $stmtSales->fetch(PDO::FETCH_ASSOC);
        
        // Ventas Anteriores (para tendencia)
        $stmtPrev = $this->db->prepare("SELECT SUM(total_amount) as prev_sales FROM orders WHERE company_id = ? AND status IN ('COMPLETED', 'READY') AND $prevDateCondition");
        $stmtPrev->execute([$companyId]);
        $prevSales = (float)($stmtPrev->fetchColumn() ?? 0);

        $grossSales = (float)($salesData['gross_sales'] ?? 0);
        $totalOrders = (int)($salesData['total_orders'] ?? 0);
        
        // Calcular tendencia
        $salesTrend = 0;
        if ($prevSales > 0) {
            $salesTrend = (($grossSales - $prevSales) / $prevSales) * 100;
        } elseif ($grossSales > 0) {
            $salesTrend = 100; // Crecimiento del 100% si no había ventas previas
        }

        $stmtExp = $this->db->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE company_id = ? AND $dateCondition");
        $stmtExp->execute([$companyId]);
        $expData = $stmtExp->fetch(PDO::FETCH_ASSOC);
        
        $totalExpenses = (float)($expData['total_expenses'] ?? 0);
        $netProfit = $grossSales - $totalExpenses;
        $averageTicket = $totalOrders > 0 ? ($grossSales / $totalOrders) : 0;

        // Gastos y Utilidad Anterior (para tendencia real)
        $stmtExpPrev = $this->db->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE company_id = ? AND $prevDateCondition");
        $stmtExpPrev->execute([$companyId]);
        $prevExpenses = (float)($stmtExpPrev->fetchColumn() ?? 0);
        $prevNetProfit = $prevSales - $prevExpenses;

        $profitTrend = 0;
        if ($prevNetProfit != 0) {
            // Usamos diferencia absoluta para evitar errores con números negativos
            $profitTrend = ($netProfit > $prevNetProfit) ? 1 : -1;
        } else {
            $profitTrend = ($netProfit > 0) ? 1 : ($netProfit < 0 ? -1 : 0);
        }

        return [
            "gross_sales" => $grossSales,
            "total_expenses" => $totalExpenses,
            "net_profit" => $netProfit,
            "total_orders" => $totalOrders,
            "average_ticket" => round($averageTicket, 2),
            "sales_trend" => round($salesTrend, 1),
            "profit_trend" => $profitTrend,
            "prev_sales" => $prevSales
        ];
    }

    public function getTopProducts($companyId) {
        $query = "
            SELECT p.name, p.id, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.unit_price) as revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.company_id = ? AND o.status IN ('COMPLETED', 'READY')
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pedidos agrupados por hora del día
    public function getPeakHours($companyId, $period) {
        $dateCondition = "1";
        switch($period) {
            case 'day': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'week': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'month': $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; break;
            case 'year': $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; break;
        }
        $stmt = $this->db->prepare("
            SELECT HOUR(created_at) as hour, COUNT(id) as total_orders
            FROM orders
            WHERE company_id = ? AND status IN ('COMPLETED', 'READY', 'ACCEPTED') AND $dateCondition
            GROUP BY HOUR(created_at) ORDER BY hour ASC
        ");
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hours = array_fill(0, 24, 0);
        foreach ($rows as $row) { $hours[(int)$row['hour']] = (int)$row['total_orders']; }
        return $hours;
    }

    // Distribución de pedidos por tipo de servicio
    public function getOrderTypeBreakdown($companyId, $period) {
        $dateCondition = "1";
        switch($period) {
            case 'day': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'week': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'month': $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; break;
            case 'year': $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; break;
        }
        $stmt = $this->db->prepare("
            SELECT order_type, COUNT(id) as total, SUM(total_amount) as revenue
            FROM orders
            WHERE company_id = ? AND status IN ('COMPLETED', 'READY') AND $dateCondition
            GROUP BY order_type
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Desglose de gastos por categoría personalizada
    public function getExpenseBreakdown($companyId, $period) {
        $dateCondition = "1";
        switch($period) {
            case 'day': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'week': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'month': $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; break;
            case 'year': $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; break;
        }
        $stmt = $this->db->prepare("
            SELECT category, SUM(amount) as total, COUNT(id) as count
            FROM expenses
            WHERE company_id = ? AND $dateCondition
            GROUP BY category ORDER BY total DESC
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // GET /api.php/analytics/kitchen-efficiency?company_id=1&period=month
    public function getKitchenEfficiency($companyId, $period) {
        $dateCondition = "1";
        switch($period) {
            case 'day': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'week': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'month': $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; break;
            case 'year': $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; break;
        }

        $query = "
            SELECT AVG(TIMESTAMPDIFF(MINUTE, accepted_at, ready_at)) as avg_minutes
            FROM orders
            WHERE company_id = ? 
              AND accepted_at IS NOT NULL 
              AND ready_at IS NOT NULL
              AND $dateCondition
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            "avg_minutes" => round($row['avg_minutes'] ?? 0, 1)
        ];
    }

    // GET /api.php/analytics/customer-retention?company_id=1&period=month
    public function getCustomerRetention($companyId, $period) {
        $dateCondition = "1";
        switch($period) {
            case 'day': $dateCondition = "DATE(created_at) = CURDATE()"; break;
            case 'week': $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'month': $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"; break;
            case 'year': $dateCondition = "YEAR(created_at) = YEAR(CURDATE())"; break;
        }

        $query = "
            SELECT 
                SUM(IF(is_recurring > 0, 1, 0)) as recurring,
                SUM(IF(is_recurring = 0, 1, 0)) as 'new'
            FROM (
                SELECT o.id,
                (SELECT COUNT(*) FROM orders o2 
                 WHERE o2.customer_phone = o.customer_phone 
                 AND o2.company_id = o.company_id 
                 AND (o2.created_at < o.created_at OR (o2.created_at = o.created_at AND o2.id < o.id))) as is_recurring
                FROM orders o
                WHERE o.company_id = ? AND $dateCondition AND o.customer_phone != ''
            ) sub
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            "new" => (int)($row['new'] ?? 0),
            "recurring" => (int)($row['recurring'] ?? 0)
        ];
    }

    public function getRatingStats($companyId) {
        $query = "
            SELECT rating, COUNT(*) as count
            FROM reviews
            WHERE company_id = ?
            GROUP BY rating
            ORDER BY rating DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $counts = [
            5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0
        ];
        $total = 0;
        $sum = 0;

        foreach ($rows as $row) {
            $r = (int)$row['rating'];
            $c = (int)$row['count'];
            $counts[$r] = $c;
            $total += $c;
            $sum += ($r * $c);
        }

        $average = $total > 0 ? round($sum / $total, 2) : 0;

        return [
            "distribution" => $counts,
            "total_reviews" => $total,
            "average" => $average
        ];
    }
}
