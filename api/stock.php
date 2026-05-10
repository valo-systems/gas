<?php
/**
 * Stock status endpoint.
 *
 *   GET   /api/stock.php                   — public: list stock per size
 *   PUT   /api/stock.php?id=X              — admin: update status/notes
 *                                            (id = cylinder_price_id)
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $sql = 'SELECT p.id AS cylinder_price_id, p.cylinder_size,
                       COALESCE(s.status, "available") AS status,
                       s.notes, s.updated_at
                FROM cylinder_prices p
                LEFT JOIN stock_status s ON s.cylinder_price_id = p.id
                WHERE p.is_active = 1
                ORDER BY p.display_order ASC';
        $rows = db()->query($sql)->fetchAll();
        foreach ($rows as &$r) {
            $r['cylinder_price_id'] = (int)$r['cylinder_price_id'];
        }
        json_response(['stock' => $rows]);
    }

    require_admin();

    if ($method === 'PUT' || $method === 'PATCH') {
        $cylinderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($cylinderId <= 0) {
            json_response(['error' => 'id (cylinder_price_id) is required'], 400);
        }

        $in = read_input();
        $status = $in['status'] ?? 'available';
        $allowed = ['available', 'low_stock', 'confirm_first', 'out_of_stock'];
        if (!in_array($status, $allowed, true)) {
            json_response(['error' => 'Invalid status'], 400);
        }
        $notes = clean_string($in['notes'] ?? '', 255);

        // Upsert: insert if missing, otherwise update.
        $stmt = db()->prepare(
            'INSERT INTO stock_status (cylinder_price_id, status, notes)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)'
        );
        // We don't have a unique index on cylinder_price_id by default — emulate upsert.
        $check = db()->prepare(
            'SELECT id FROM stock_status WHERE cylinder_price_id = ? LIMIT 1'
        );
        $check->execute([$cylinderId]);
        $existing = $check->fetchColumn();

        if ($existing) {
            $upd = db()->prepare(
                'UPDATE stock_status SET status = ?, notes = ? WHERE cylinder_price_id = ?'
            );
            $upd->execute([$status, $notes, $cylinderId]);
        } else {
            $ins = db()->prepare(
                'INSERT INTO stock_status (cylinder_price_id, status, notes) VALUES (?, ?, ?)'
            );
            $ins->execute([$cylinderId, $status, $notes]);
        }

        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    error_log('[stock] ' . $e->getMessage());
    json_response(['error' => 'Server error'], 500);
}
