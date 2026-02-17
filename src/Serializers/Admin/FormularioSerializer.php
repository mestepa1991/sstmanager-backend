<?php
namespace App\Serializers\Admin;

class FormularioSerializer
{
    public static function readJsonBody(): array
    {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function toInt01($value): int
    {
        if ($value === true || $value === 1 || $value === "1" || $value === "true") return 1;
        return 0;
    }

    public static function ok($data = null, int $status = 200): void
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "status" => $status,
            "data"   => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $status = 400, $details = null): void
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "status"  => $status,
            "error"   => $message,
            "details" => $details
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
