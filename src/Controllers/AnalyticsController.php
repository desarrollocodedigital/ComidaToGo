<?php

namespace App\Controllers;

use App\Models\Analytics;

class AnalyticsController {
    private $model;

    public function __construct() {
        $this->model = new Analytics();
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

        $metrics = $this->model->getSalesSummary($company_id, $period);

        echo json_encode([
            "period" => $period,
            "metrics" => $metrics
        ]);
    }

    // GET /api.php/analytics/top-products?company_id=1
    public function getTopProducts() {
        $company_id = $_GET['company_id'] ?? null;
        if (!$company_id) {
            http_response_code(400);
            return;
        }

        $results = $this->model->getTopProducts($company_id);
        echo json_encode(["top_products" => $results]);
    }
}

