<?php
/**
 * Shared admin layout. Include this from each admin page after
 * setting $page_title and $active_tab, then close with _layout_end.php.
 */
require_once __DIR__ . '/../api/auth.php';
$user = require_admin_page();

$active_tab = $active_tab ?? '';
$page_title = $page_title ?? 'Admin';

function nav_link($key, $href, $label, $icon, $active) {
    $cls = $key === $active ? 'active' : '';
    echo '<a href="' . htmlspecialchars($href) . '" class="' . $cls . '">' .
         '<i class="bi bi-' . $icon . ' me-2"></i>' . htmlspecialchars($label) . '</a>';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title) ?> | Gas @ Midway Mews Admin</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
<link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="brand">Gas <span class="accent">@ Midway Mews</span></div>
    <div class="small text-white-50 mb-3">
      Signed in as <strong class="text-white"><?= htmlspecialchars($user['name']) ?></strong>
    </div>
    <?php
      nav_link('dashboard',    'dashboard.php',    'Dashboard',    'speedometer2', $active_tab);
      nav_link('reservations', 'reservations.php', 'Reservations', 'bookmark-check', $active_tab);
      nav_link('prices',       'prices.php',       'Prices',       'cash-coin',    $active_tab);
      nav_link('stock',        'stock.php',        'Stock',        'box-seam',     $active_tab);
      nav_link('enquiries',    'enquiries.php',    'Enquiries',    'envelope',     $active_tab);
      nav_link('settings',     'settings.php',     'Settings',     'gear',         $active_tab);
    ?>
    <hr class="border-white-50 my-3">
    <a href="../index.html" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>View site</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a>
  </aside>
  <main class="admin-main">
    <h1><?= htmlspecialchars($page_title) ?></h1>
