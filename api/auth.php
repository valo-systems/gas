<?php
/**
 * Admin authentication endpoint and shared session helpers.
 *
 *   POST  /api/auth.php?action=login   { email, password }
 *   POST  /api/auth.php?action=logout
 *   GET   /api/auth.php?action=me
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

/** Require an authenticated admin or return 401 JSON. */
function require_admin(): array {
    if (empty($_SESSION['user_id'])) {
        json_response(['error' => 'Authentication required'], 401);
    }
    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'admin',
    ];
}

/** For admin pages — redirect to login instead of returning JSON. */
function require_admin_page(): array {
    if (empty($_SESSION['user_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'admin',
    ];
}

// Only run the routing block when this file is the entrypoint.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'auth.php') {
    return;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($action === 'login' && $method === 'POST') {
        $input = read_input();
        $email = strtolower(clean_string($input['email'] ?? '', 150));
        $password = (string)($input['password'] ?? '');

        if ($email === '' || $password === '') {
            json_response(['error' => 'Email and password are required'], 400);
        }

        $stmt = db()->prepare(
            'SELECT id, full_name, email, password_hash, role, is_active
             FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
            // Same message either way, to avoid leaking which emails exist.
            json_response(['error' => 'Invalid email or password'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        json_response([
            'ok'   => true,
            'user' => [
                'id'    => (int)$user['id'],
                'name'  => $user['full_name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
    }

    if ($action === 'logout' && $method === 'POST') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'],
                $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        json_response(['ok' => true]);
    }

    if ($action === 'me' && $method === 'GET') {
        if (empty($_SESSION['user_id'])) {
            json_response(['authenticated' => false]);
        }
        json_response([
            'authenticated' => true,
            'user' => [
                'id'    => (int)$_SESSION['user_id'],
                'name'  => $_SESSION['user_name'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'role'  => $_SESSION['user_role'] ?? 'admin',
            ],
        ]);
    }

    json_response(['error' => 'Unknown action'], 404);

} catch (Throwable $e) {
    error_log('[auth] ' . $e->getMessage());
    json_response(['error' => 'Server error'], 500);
}
