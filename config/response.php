<?php

class Response {

    public static function ok(mixed $data, string $msg = 'OK'): void {
        self::send(true, $msg, $data);
    }

    public static function error(string $msg, int $code = 400): void {
        http_response_code($code);
        self::send(false, $msg, null);
    }

    private static function send(bool $ok, string $msg, mixed $data): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => $ok,
            'message' => $msg,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}