<?php
/**
 * Customer enquiries endpoint.
 *
 *   POST  /api/enquiries.php            — public: submit message
 *   GET   /api/enquiries.php            — admin: list
 *   PUT   /api/enquiries.php?id=X       — admin: update status
 *   DELETE /api/enquiries.php?id=X      — admin: delete
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'POST') {
        $in = read_input();

        $name    = clean_string($in['full_name']    ?? '', 100);
        $phone   = clean_string($in['phone_number'] ?? '', 30);
        $message = clean_string($in['message']      ?? '', 2000);

        if ($message === '') {
            json_response(['error' => 'Message is required'], 400);
        }
        // Honeypot field — bots tend to fill every input.
        if (!empty($in['website'])) {
            json_response(['ok' => true]); // pretend success
        }

        $stmt = db()->prepare(
            'INSERT INTO enquiries (full_name, phone_number, message, status)
             VALUES (?, ?, ?, "new")'
        );
        $stmt->execute([$name, $phone, $message]);
        json_response(['ok' => true, 'id' => (int)db()->lastInsertId()], 201);
    }

    require_admin();

    if ($method === 'GET') {
        $stmt = db()->query(
            'SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 200'
        );
        json_response(['enquiries' => $stmt->fetchAll()]);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) json_response(['error' => 'id is required'], 400);

        $in = read_input();
        $status = $in['status'] ?? '';
        if (!in_array($status, ['new', 'read', 'closed'], true)) {
            json_response(['error' => 'Invalid status'], 400);
        }
        db()->prepare('UPDATE enquiries SET status = ? WHERE id = ?')
            ->execute([$status, $id]);
        json_response(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) json_response(['error' => 'id is required'], 400);
        db()->prepare('DELETE FROM enquiries WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    error_log('[enquiries] ' . $e->getMessage());
    json_response(['error' => 'Server error'], 500);
}
