<?php

class Session {

    // ── Roles ──────────────────────────────────────────────
    const ROL_ADMIN   = '10'; // Administrador total
    const ROL_QUIMICA = '0';  // Operador de producción / química
    const ROL_ALMACEN = '6';  // Operador de almacén
    const ROL_ADMON   = '9';  // Administración / finanzas

    // ── Franquicias especiales ─────────────────────────────
    const FRANQUICIA_MATRIZ = 'CWO';
    const FRANQUICIA_PLAYA  = 'PDC';

    // ── Propiedades ────────────────────────────────────────
    public static string $franquicia;
    public static string $username;
    public static string $rol;

    // ── Init ───────────────────────────────────────────────
    public static function init(): void {

        $a = trim($_GET['a'] ?? '');
        $b = trim($_GET['b'] ?? '');
        $c = trim($_GET['c'] ?? '');

        if ($a === '' || $b === '' || $c === '') {
			self::unauthorized();
		}

        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $a)) {
            self::unauthorized();
        }

        if (preg_match('/[<>"\';&]/', $b)) {
            self::unauthorized();
        }

        if (!preg_match('/^[0-9]+$/', $c)) {
            self::unauthorized();
        }

        if (!in_array($c, [self::ROL_ADMIN, self::ROL_QUIMICA, self::ROL_ALMACEN, self::ROL_ADMON])) {
            self::unauthorized();
        }

        self::$franquicia = strtoupper($a);
        self::$username   = $b;
        self::$rol        = $c;
    }

    // ── Tipo de franquicia ─────────────────────────────────

    public static function isCWO(): bool {
        return self::$franquicia === self::FRANQUICIA_MATRIZ;
    }

    public static function isPlaya(): bool {
        return self::$franquicia === self::FRANQUICIA_PLAYA;
    }

    public static function isFranquicia(): bool {
        return !self::isCWO() && !self::isPlaya();
    }

    // ── Permisos por tipo de almacén ───────────────────────

    public static function puedeGestionarCatalogo(): bool {
        return self::isCWO();
    }

    public static function puedeVerReportesGlobales(): bool {
        return self::isCWO();
    }

    public static function puedeTransferirACWO(): bool {
        return self::isPlaya();
    }

    public static function puedeVenderAFranquicia(): bool {
        return self::isCWO();
    }

    // Producción — solo CWO con rol admin o química
    public static function puedeProducir(): bool {
        return self::isCWO() && (self::isAdmin() || self::isQuimica());
    }

    // Ver recetas — solo CWO admin o química
    public static function puedeVerRecetas(): bool {
        return self::isCWO() && (self::isAdmin() || self::isQuimica());
    }

    // Ajuste inventario — solo admin en cualquier almacén
    public static function puedeAjustarInventario(): bool {
        return self::isAdmin();
    }

    // Consumo interno — admin y almacén en cualquier franquicia
    public static function puedeConsumoInterno(): bool {
        return self::isAdmin() || self::isAlmacen();
    }

    // Ver costos — solo CWO admin o admon
    public static function puedeVerCostos(): bool {
        return self::isCWO() && (self::isAdmin() || self::isAdmon());
    }

    // Modificar costos — solo CWO admin
    public static function puedeModificarCostos(): bool {
        return self::isCWO() && self::isAdmin();
    }

    // ── Guards de rol ──────────────────────────────────────

    public static function requireAdmin(): void {
        self::requireRol([self::ROL_ADMIN]);
    }

    public static function requireQuimica(): void {
        self::requireRol([self::ROL_ADMIN, self::ROL_QUIMICA]);
    }

    public static function requireAdmon(): void {
        self::requireRol([self::ROL_ADMIN, self::ROL_ADMON]);
    }

    public static function requireAlmacen(): void {
        self::requireRol([self::ROL_ADMIN, self::ROL_ALMACEN]);
    }

    public static function requireAny(): void {
        self::requireRol([self::ROL_ADMIN, self::ROL_QUIMICA, self::ROL_ALMACEN, self::ROL_ADMON]);
    }

    // ── Guards combinados rol + franquicia ─────────────────

    public static function requireCWO(): void {
        if (!self::isCWO()) self::forbidden();
    }

    public static function requireCWOAdmin(): void {
        if (!self::isCWO() || !self::isAdmin()) self::forbidden();
    }

    public static function requireCatalogo(): void {
        if (!self::puedeGestionarCatalogo()) self::forbidden();
    }

    public static function requireProduccion(): void {
        if (!self::puedeProducir()) self::forbidden();
    }

    // ── Checks de rol ──────────────────────────────────────

    public static function isAdmin(): bool {
        return self::$rol === self::ROL_ADMIN;
    }

    public static function isQuimica(): bool {
        return self::$rol === self::ROL_QUIMICA;
    }

    public static function isAlmacen(): bool {
        return self::$rol === self::ROL_ALMACEN;
    }

    public static function isAdmon(): bool {
        return self::$rol === self::ROL_ADMON;
    }

    // ── Helpers de respuesta ───────────────────────────────

    public static function unauthorized(): never {
        http_response_code(401);
        if (self::isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'No autorizado']);
        } else {
            header('Location: /almacenes/error.php?code=401');
        }
        exit;
    }

    public static function forbidden(): never {
        http_response_code(403);
        if (self::isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Sin permisos']);
        } else {
            header('Location: /almacenes/error.php?code=403');
        }
        exit;
    }

    private static function requireRol(array $roles): void {
        if (!in_array(self::$rol, $roles, true)) {
            self::forbidden();
        }
    }

    private static function isAjax(): bool {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}