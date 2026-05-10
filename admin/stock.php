<?php
require_once __DIR__ . '/../api/db.php';
$page_title = 'Stock';
$active_tab = 'stock';
require __DIR__ . '/_layout.php';

$rows = db()->query(
    'SELECT p.id AS cylinder_price_id, p.cylinder_size, p.price,
            COALESCE(s.status, "available") AS status, s.notes, s.updated_at
     FROM cylinder_prices p
     LEFT JOIN stock_status s ON s.cylinder_price_id = p.id
     WHERE p.is_active = 1
     ORDER BY p.display_order'
)->fetchAll();
?>

<p class="text-muted mb-3">
  Mark sizes as available, low stock, or out of stock. Changes go live on the public site immediately.
</p>

<div class="bg-white rounded-xl shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr><th>Size</th><th>Price</th><th>Status</th><th>Notes</th><th>Updated</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr data-id="<?= (int)$r['cylinder_price_id'] ?>">
            <td><strong><?= htmlspecialchars($r['cylinder_size']) ?></strong></td>
            <td>R<?= number_format((float)$r['price'], 0) ?></td>
            <td>
              <select class="form-select form-select-sm" name="status">
                <?php
                  $statusLabels = [
                    'available'     => 'Available today',
                    'low_stock'     => 'Low stock',
                    'confirm_first' => 'Confirm first',
                    'out_of_stock'  => 'Out of stock',
                  ];
                  foreach ($statusLabels as $s => $lbl): ?>
                  <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input class="form-control form-control-sm" name="notes" maxlength="255" value="<?= htmlspecialchars($r['notes'] ?? '') ?>"></td>
            <td class="small text-muted"><?= htmlspecialchars($r['updated_at'] ?? '—') ?></td>
            <td><button class="btn btn-sm btn-brand-red" data-action="save"><i class="bi bi-save me-1"></i>Save</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelectorAll('[data-action="save"]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var row = btn.closest('tr');
    var id = row.dataset.id;
    var body = {
      status: row.querySelector('[name="status"]').value,
      notes:  row.querySelector('[name="notes"]').value
    };
    btn.disabled = true;
    fetch('../api/stock.php?id=' + id, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (r) { return r.ok; }).then(function (ok) {
      btn.disabled = false;
      btn.innerHTML = ok ? '<i class="bi bi-check-lg me-1"></i>Saved'
                         : '<i class="bi bi-x-lg me-1"></i>Failed';
      if (ok) setTimeout(function () { btn.innerHTML = '<i class="bi bi-save me-1"></i>Save'; }, 1500);
    });
  });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
