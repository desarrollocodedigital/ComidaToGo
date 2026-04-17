<?php

namespace App\Controllers;

use App\Models\Auth;

class AuthController {
    private $model;

    public function __construct() {
        $this->model = new Auth();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['name'], $input['email'], $input['password'], $input['role'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos"]);
            return;
        }

        if ($this->model->userExists($input['email'])) {
            http_response_code(409); // Conflict
            echo json_encode(["message" => "El email ya está registrado"]);
            return;
        }

        $result = $this->model->registerUserAndCompany($input);

        if ($result['success']) {
            echo json_encode([
                "message" => "Usuario registrado",
                "user" => $result['user']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al registrar: " . $result['error']]);
        }
    }

    public function login() {
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);

            if (!isset($input['email'], $input['password'])) {
                http_response_code(400);
                echo json_encode(["message" => "Faltan credenciales"]);
                return;
            }

            $result = $this->model->attemptLogin($input['email'], $input['password']);

            if ($result['success']) {
                echo json_encode([
                    "message" => "Login exitoso",
                    "user" => $result['user']
                ]);
            } else {
                http_response_code(401);
                $msg = $result['message'] ?? "Credenciales inválidas";
                echo json_encode(["message" => $msg]);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(["message" => "Internal Server Error", "error" => $e->getMessage()]);
        }
    }
}

