<?php
require_once __DIR__ . '/../api/db.php';
$page_title = 'Reservations';
$active_tab = 'reservations';
require __DIR__ . '/_layout.php';

$filter = $_GET['status'] ?? '';
$allowed = ['pending', 'confirmed', 'collected', 'cancelled'];

$sql = 'SELECT * FROM reservations';
$params = [];
if (in_array($filter, $allowed, true)) {
    $sql .= ' WHERE status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$wa = db()->query("SELECT setting_value FROM business_settings WHERE setting_key='whatsapp_number'")->fetchColumn();
?>

<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="?" class="btn btn-sm <?= $filter === '' ? 'btn-brand-red' : 'btn-outline-dark' ?>">All</a>
  <?php foreach ($allowed as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter === $s ? 'btn-brand-red' : 'btn-outline-dark' ?>">
      <?= ucfirst($s) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Customer</th><th>Phone</th><th>Size</th><th>Type</th>
          <th>Collection</th><th>Notes</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No reservations match this filter.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr data-id="<?= (int)$r['id'] ?>">
            <td><?= htmlspecialchars($r['customer_name']) ?></td>
            <td><?= htmlspecialchars($r['phone_number']) ?></td>
            <td><strong><?= htmlspecialchars($r['cylinder_size']) ?></strong></td>
            <td><?= htmlspecialchars($r['request_type']) ?></td>
            <td><?= htmlspecialchars($r['preferred_collection_time'] ?: '—') ?></td>
            <td class="small"><?= htmlspecialchars(mb_strimwidth($r['notes'] ?? '', 0, 80, '…')) ?></td>
            <td><span class="status-pill <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            <td class="text-nowrap">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-success" data-action="confirmed" title="Confirm"><i class="bi bi-check-lg"></i></button>
                <button class="btn btn-outline-primary" data-action="collected" title="Mark collected"><i class="bi bi-bag-check"></i></button>
                <button class="btn btn-outline-secondary" data-action="cancelled" title="Cancel"><i class="bi bi-x-lg"></i></button>
                <?php
                  $cleanPhone = preg_replace('/\D+/', '', $r['phone_number']);
                  // South African local 0XX → 27XX
                  if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '0')) {
                      $cleanPhone = '27' . substr($cleanPhone, 1);
                  }
                  $msg = rawurlencode("Hi " . $r['customer_name'] . ", regarding your gas reservation for " . $r['cylinder_size'] . "…");
                ?>
                <a class="btn btn-outline-success" target="_blank"
                   href="https://wa.me/<?= htmlspecialchars($cleanPhone) ?>?text=<?= $msg ?>"
                   title="WhatsApp customer">
                   <i class="bi bi-whatsapp"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelectorAll('[data-action]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var row = btn.closest('tr');
    var id = row.dataset.id;
    var status = btn.dataset.action;
    if (!confirm('Set this reservation to "' + status + '"?')) return;

    fetch('../api/reservations.php?id=' + id, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: status })
    }).then(function (r) { return r.ok; }).then(function (ok) {
      if (ok) location.reload();
      else alert('Update failed.');
    });
  });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
