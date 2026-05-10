<?php
/**
 * Reservation endpoint.
 *
 *   POST  /api/reservations.php           — public: create a reservation
 *   GET   /api/reservations.php           — admin: list reservations
 *   PUT   /api/reservations.php?id=X      — admin: update status
 *   DELETE /api/reservations.php?id=X     — admin: delete (rare)
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'POST') {
        $in = read_input();

        $name  = clean_string($in['customer_name'] ?? '', 100);
        $phone = clean_string($in['phone_number'] ?? '', 30);
        $size  = clean_string($in['cylinder_size'] ?? '', 20);
        $type  = clean_string($in['request_type']  ?? 'refill', 30);
        $when  = clean_string($in['preferred_collection_time'] ?? '', 100);
        $notes = clean_string($in['notes'] ?? '', 1000);

        if ($name === '' || $phone === '' || $size === '') {
            json_response(['error' => 'Name, phone, and cylinder size are required'], 400);
        }

        $allowedTypes = ['refill', 'exchange', 'availability_check'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'refill';
        }

        // Light spam guard: phone must contain at least 7 digits.
        if (preg_match_all('/\d/', $phone) < 7) {
            json_response(['error' => 'Phone number looks invalid'], 400);
        }

        $stmt = db()->prepare(
            'INSERT INTO reservations
             (customer_name, phone_number, cylinder_size, request_type,
              preferred_collection_time, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([$name, $phone, $size, $type, $when, $notes]);

        json_response([
            'ok' => true,
            'id' => (int)db()->lastInsertId(),
            'message' => 'Your gas collection request has been received. ' .
                         'Please wait for confirmation before coming if stock is limited.',
        ], 201);
    }

    // Read/write below requires admin.
    require_admin();

    if ($method === 'GET') {
        $status = $_GET['status'] ?? '';
        $sql = 'SELECT * FROM reservations';
        $params = [];
        $allowed = ['pending', 'confirmed', 'collected', 'cancelled'];
        if (in_array($status, $allowed, true)) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 200';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        json_response(['reservations' => $stmt->fetchAll()]);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) json_response(['error' => 'id is required'], 400);

        $in = read_input();
        $status = $in['status'] ?? '';
        $allowed = ['pending', 'confirmed', 'collected', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            json_response(['error' => 'Invalid status'], 400);
        }

        $stmt = db()->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        json_response(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) json_response(['error' => 'id is required'], 400);
        db()->prepare('DELETE FROM reservations WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    error_log('[reservations] ' . $e->getMessage());
    json_response(['error' => 'Server error'], 500);
}
