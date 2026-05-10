<?php
require_once __DIR__ . '/../api/db.php';
$page_title = 'Prices';
$active_tab = 'prices';
require __DIR__ . '/_layout.php';

$rows = db()->query(
    'SELECT p.*, COALESCE(s.status, "available") AS stock_status
     FROM cylinder_prices p
     LEFT JOIN stock_status s ON s.cylinder_price_id = p.id
     ORDER BY p.display_order, p.id'
)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">Update cylinder prices, mark sizes as popular, or deactivate sizes you don't refill anymore.</p>
  <button class="btn btn-brand-red btn-sm" data-bs-toggle="modal" data-bs-target="#newPriceModal">
    <i class="bi bi-plus-lg me-1"></i>Add size
  </button>
</div>

<div class="bg-white rounded-xl shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Size</th><th>Price (R)</th><th>Order</th><th>Popular</th><th>Active</th><th>Stock</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr data-id="<?= (int)$r['id'] ?>">
            <td><input class="form-control form-control-sm" name="cylinder_size" value="<?= htmlspecialchars($r['cylinder_size']) ?>"></td>
            <td><input class="form-control form-control-sm" name="price" type="number" step="0.01" min="0" value="<?= htmlspecialchars($r['price']) ?>"></td>
            <td><input class="form-control form-control-sm" name="display_order" type="number" min="0" value="<?= (int)$r['display_order'] ?>" style="max-width:90px"></td>
            <td><input type="checkbox" name="is_popular" class="form-check-input" <?= $r['is_popular'] ? 'checked' : '' ?>></td>
            <td><input type="checkbox" name="is_active" class="form-check-input" <?= $r['is_active'] ? 'checked' : '' ?>></td>
            <td><span class="status-pill <?= htmlspecialchars($r['stock_status']) ?>"><?= htmlspecialchars($r['stock_status']) ?></span></td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-brand-red" data-action="save"><i class="bi bi-save me-1"></i>Save</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add size modal -->
<div class="modal fade" id="newPriceModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="newPriceForm">
      <div class="modal-header">
        <h5 class="modal-title">Add cylinder size</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Size</label>
          <input class="form-control" name="cylinder_size" required></div>
        <div class="mb-3"><label class="form-label">Price (R)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="price" required></div>
        <div class="mb-3"><label class="form-label">Display order</label>
          <input class="form-control" type="number" min="0" name="display_order" value="99"></div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_popular" id="np_pop">
          <label class="form-check-label" for="np_pop">Mark as popular</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand-red">Add</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('[data-action="save"]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var row = btn.closest('tr');
    var id = row.dataset.id;
    var body = {
      cylinder_size: row.querySelector('[name="cylinder_size"]').value,
      price: parseFloat(row.querySelector('[name="price"]').value),
      display_order: parseInt(row.querySelector('[name="display_order"]').value, 10),
      is_popular: row.querySelector('[name="is_popular"]').checked,
      is_active: row.querySelector('[name="is_active"]').checked
    };
    btn.disabled = true;
    fetch('../api/prices.php?id=' + id, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (r) { return r.ok; }).then(function (ok) {
      btn.disabled = false;
      btn.innerHTML = ok ? '<i class="bi bi-check-lg me-1"></i>Saved' : '<i class="bi bi-x-lg me-1"></i>Failed';
      if (ok) setTimeout(function () { btn.innerHTML = '<i class="bi bi-save me-1"></i>Save'; }, 1500);
    });
  });
});

document.getElementById('newPriceForm').addEventListener('submit', function (e) {
  e.preventDefault();
  var data = Object.fromEntries(new FormData(e.target).entries());
  data.is_popular = e.target.querySelector('[name="is_popular"]').checked;
  data.price = parseFloat(data.price);
  data.display_order = parseInt(data.display_order, 10);
  fetch('../api/prices.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  }).then(function (r) { return r.ok; }).then(function (ok) {
    if (ok) location.reload(); else alert('Could not add size.');
  });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
