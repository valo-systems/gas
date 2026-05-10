<?php
/**
 * Cylinder prices endpoint.
 *
 *   GET   /api/prices.php             — public list of active prices + stock
 *   GET   /api/prices.php?all=1       — admin: include inactive rows
 *   POST  /api/prices.php             — admin: create new price row
 *   PUT   /api/prices.php?id=X        — admin: update price/popular/active flag
 *   DELETE /api/prices.php?id=X       — admin: deactivate (soft delete)
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $isAdmin = !empty($_GET['all']) && !empty($_SESSION['user_id']);
        $where = $isAdmin ? '' : 'WHERE p.is_active = 1';

        $sql = "SELECT p.id, p.cylinder_size, p.price, p.is_popular,
                       p.display_order, p.is_active, p.updated_at,
                       COALESCE(s.status, 'available') AS stock_status,
                       s.notes AS stock_notes
                FROM cylinder_prices p
                LEFT JOIN stock_status s ON s.cylinder_price_id = p.id
                $where
                ORDER BY p.display_order ASC, p.id ASC";

        $rows = db()->query($sql)->fetchAll();
        // Cast numeric fields for cleaner JSON.
        foreach ($rows as &$r) {
            $r['id']            = (int)$r['id'];
            $r['price']         = (float)$r['price'];
            $r['is_popular']    = (bool)$r['is_popular'];
            $r['display_order'] = (int)$r['display_order'];
            $r['is_active']     = (bool)$r['is_active'];
        }
        json_response(['prices' => $rows]);
    }

    // All write operations require admin.
    require_admin();

    if ($method === 'POST') {
        $in = read_input();
        $size  = clean_string($in['cylinder_size'] ?? '', 20);
        $price = isset($in['price']) ? (float)$in['price'] : 0.0;
        $popular = !empty($in['is_popular']);
        $order = isset($in['display_order']) ? (int)$in['display_order'] : 99;

        if ($size === '' || $price <= 0) {
            json_response(['error' => 'cylinder_size and price are required'], 400);
        }

        $stmt = db()->prepare(
            'INSERT INTO cylinder_prices (cylinder_size, price, is_popular, display_order, is_active)
             VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$size, $price, $popular ? 1 : 0, $order]);
        $id = (int)db()->lastInsertId();

        $stmt = db()->prepare(
            'INSERT INTO stock_status (cylinder_price_id, status) VALUES (?, "available")'
        );
        $stmt->execute([$id]);

        json_response(['ok' => true, 'id' => $id], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) json_response(['error' => 'id is required'], 400);

        $in = read_input();
        $fields = [];
        $params = [];

        if (array_key_exists('cylinder_size', $in)) {
            $fields[] = 'cylinder_size = ?';
            $params[] = clean_string($in['cylinder_size'], 20);
        }
        if (array_key_exists('price', $in)) {
            $fields[] = 'price = ?';
            $params[] = (float)$in['price'];
        }
        if (array_key_exists('is_popular', $in)) {
            $fields[] = 'is_popular = ?';
            $params[] = !empty($in['is_popular']) ? 1 : 0;
        }
        if (array_key_exists('display_order', $in)) {
            $fields[] = 'display_order = ?';
            $params[] = (int)$in['display_order'];
        }
        if (array_key_exists('is_active', $in)) {
            $fields[] = 'is_active = ?';
            $params[] = !empty($in['is_active']) ? 1 : 0;
        }

        if (!$fields) json_response(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $sql = 'UPDATE cylinder_prices SET ' . implode(', ', $fields) . ' WHERE id = ?';
        db()->prepare($sql)->execute($params);

        json_response(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) json_response(['error' => 'id is required'], 400);

        // Soft delete = mark inactive.
        $stmt = db()->prepare('UPDATE cylinder_prices SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    error_log('[prices] ' . $e->getMessage());
    json_response(['error' => 'Server error'], 500);
}
