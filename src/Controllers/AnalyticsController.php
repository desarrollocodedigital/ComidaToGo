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
        $period = $_GET['period'] ?? 'month';

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
        if (!$company_id) { http_response_code(400); return; }
        $results = $this->model->getTopProducts($company_id);
        echo json_encode(["top_products" => $results]);
    }

    // GET /api.php/analytics/peak-hours?company_id=1&period=week
    public function getPeakHours() {
        $company_id = $_GET['company_id'] ?? null;
        $period = $_GET['period'] ?? 'week';
        if (!$company_id) { http_response_code(400); return; }
        $data = $this->model->getPeakHours($company_id, $period);
        echo json_encode(["hours" => $data]);
    }

    // GET /api.php/analytics/order-types?company_id=1&period=month
    public function getOrderTypeBreakdown() {
        $company_id = $_GET['company_id'] ?? null;
        $period = $_GET['period'] ?? 'month';
        if (!$company_id) { http_response_code(400); return; }
        $data = $this->model->getOrderTypeBreakdown($company_id, $period);
        echo json_encode(["breakdown" => $data]);
    }

    // GET /api.php/analytics/expense-breakdown?company_id=1&period=month
    public function getExpenseBreakdown() {
        $company_id = $_GET['company_id'] ?? null;
        $period = $_GET['period'] ?? 'month';
        if (!$company_id) { http_response_code(400); return; }
        $data = $this->model->getExpenseBreakdown($company_id, $period);
        echo json_encode(["breakdown" => $data]);
    }

    // GET /api.php/analytics/kitchen-efficiency?company_id=1&period=month
    public function getKitchenEfficiency() {
        $company_id = $_GET['company_id'] ?? null;
        $period = $_GET['period'] ?? 'month';
        if (!$company_id) { http_response_code(400); return; }
        $data = $this->model->getKitchenEfficiency($company_id, $period);
        echo json_encode($data);
    }

    // GET /api.php/analytics/customer-retention?company_id=1&period=month
    public function getCustomerRetention() {
        $company_id = $_GET['company_id'] ?? null;
        $period = $_GET['period'] ?? 'month';
        if (!$company_id) { http_response_code(400); return; }
        $data = $this->model->getCustomerRetention($company_id, $period);
        echo json_encode($data);
    }
}

