<?php
/**
 * Business settings endpoint (key/value).
 *
 *   GET   /api/settings.php           — public: list non-sensitive settings
 *   PUT   /api/settings.php           — admin: update one or more keys
 *                                       Body: { "key1": "value1", ... }
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $rows = db()->query(
            'SELECT setting_key, setting_value, updated_at FROM business_settings'
        )->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
        json_response(['settings' => $out]);
    }

    require_admin();

    if ($method === 'PUT' || $method === 'PATCH' || $method === 'POST') {
        $in = read_input();
        if (!$in) json_response(['error' => 'No fields to update'], 400);

        $stmt = db()->prepare(
            'INSERT INTO business_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        $allowedKeys = [
            'business_name', 'primary_phone', 'secondary_phone',
            'whatsapp_number', 'whatsapp_alt', 'address',
            'trading_hours', 'google_maps_url',
            'latitude', 'longitude',
        ];

        $updated = [];
        foreach ($in as $key => $val) {
            if (!in_array($key, $allowedKeys, true)) continue;
            $stmt->execute([$key, clean_string((string)$val, 1000)]);
            $updated[] = $key;
        }
        json_response(['ok' => true, 'updated' => $updated]);
    }

    json_response(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    error_log('[settings] ' . $e->getMessage());
    json_response(['error' => 'Server error'], 500);
}
