<?php

namespace App\Controllers;

class UploadController {
    public function upload() {
        if (!isset($_FILES['image'])) {
            http_response_code(400);
            echo json_encode(["message" => "No se recibió ninguna imagen."]);
            return;
        }

        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

        // Eliminar imagen anterior si se proporciona
        if (isset($_POST['old_url']) && !empty($_POST['old_url'])) {
            $oldUrl = $_POST['old_url'];
            if (strpos($oldUrl, '/uploads/') !== false) {
                $oldPath = __DIR__ . '/../../public' . $oldUrl;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["message" => "Formato no permitido. Solo JPG, JPEG y PNG."]);
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(500);
            echo json_encode(["message" => "Error al subir el archivo."]);
            return;
        }

        // Crear directorio si no existe (doble verificación)
        $uploadDir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('prod_') . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Retornar la URL relativa para guardar en la BD
            $relativeUrl = '/uploads/' . $fileName;
            echo json_encode([
                "message" => "Imagen subida con éxito",
                "url" => $relativeUrl
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "No se pudo guardar la imagen en el servidor."]);
        }
    }
}
