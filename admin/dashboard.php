<?php
require_once __DIR__ . '/../api/db.php';
$page_title = 'Dashboard';
$active_tab = 'dashboard';
require __DIR__ . '/_layout.php';

// Counts
$stats = [
    'pending'      => (int)db()->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn(),
    'confirmed_today' => (int)db()->query(
        "SELECT COUNT(*) FROM reservations WHERE status='confirmed' AND DATE(updated_at)=CURDATE()"
    )->fetchColumn(),
    'low_stock'    => (int)db()->query("SELECT COUNT(*) FROM stock_status WHERE status='low_stock'")->fetchColumn(),
    'out_of_stock' => (int)db()->query("SELECT COUNT(*) FROM stock_status WHERE status='out_of_stock'")->fetchColumn(),
    'last_price_update' => db()->query(
        "SELECT MAX(updated_at) FROM cylinder_prices WHERE is_active = 1"
    )->fetchColumn(),
    'new_enquiries' => (int)db()->query("SELECT COUNT(*) FROM enquiries WHERE status='new'")->fetchColumn(),
];

// Recent reservations
$recent = db()->query(
    "SELECT * FROM reservations ORDER BY created_at DESC LIMIT 6"
)->fetchAll();
?>

<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-3">
    <div class="stat-card">
      <div class="label">Pending reservations</div>
      <div class="value"><?= $stats['pending'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="stat-card" style="border-left-color:#1f8a3d">
      <div class="label">Confirmed today</div>
      <div class="value"><?= $stats['confirmed_today'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="stat-card" style="border-left-color:#FFC107">
      <div class="label">Sizes low on stock</div>
      <div class="value"><?= $stats['low_stock'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="stat-card" style="border-left-color:#A80010">
      <div class="label">Sizes out of stock</div>
      <div class="value"><?= $stats['out_of_stock'] ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card" style="border-left-color:#003B73">
      <div class="label">New enquiries</div>
      <div class="value"><?= $stats['new_enquiries'] ?></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card" style="border-left-color:#003B73">
      <div class="label">Last price update</div>
      <div class="value" style="font-size:1.1rem;font-weight:600">
        <?= $stats['last_price_update'] ? htmlspecialchars($stats['last_price_update']) : '—' ?>
      </div>
    </div>
  </div>
</div>

<div class="d-flex align-items-center justify-content-between mt-4 mb-3">
  <h2 class="h5 mb-0">Recent reservations</h2>
  <a href="reservations.php" class="btn btn-sm btn-outline-dark">See all</a>
</div>

<div class="bg-white rounded-xl shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Customer</th>
          <th>Phone</th>
          <th>Size</th>
          <th>Type</th>
          <th>Collection</th>
          <th>Status</th>
          <th>Received</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No reservations yet.</td></tr>
        <?php else: foreach ($recent as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['customer_name']) ?></td>
            <td><?= htmlspecialchars($r['phone_number']) ?></td>
            <td><strong><?= htmlspecialchars($r['cylinder_size']) ?></strong></td>
            <td><?= htmlspecialchars($r['request_type']) ?></td>
            <td><?= htmlspecialchars($r['preferred_collection_time'] ?: '—') ?></td>
            <td><span class="status-pill <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            <td class="small text-muted"><?= htmlspecialchars($r['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
