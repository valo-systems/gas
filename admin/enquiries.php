<?php
require_once __DIR__ . '/../api/db.php';
$page_title = 'Enquiries';
$active_tab = 'enquiries';
require __DIR__ . '/_layout.php';

$rows = db()->query('SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 200')->fetchAll();
?>

<p class="text-muted mb-3">Messages submitted via the contact form on the public site.</p>

<div class="bg-white rounded-xl shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr><th>Name</th><th>Phone</th><th>Message</th><th>Status</th><th>Received</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No enquiries yet.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr data-id="<?= (int)$r['id'] ?>">
            <td><?= htmlspecialchars($r['full_name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['phone_number'] ?: '—') ?></td>
            <td style="max-width:420px;white-space:normal"><?= nl2br(htmlspecialchars($r['message'])) ?></td>
            <td><span class="status-pill <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            <td class="small text-muted text-nowrap"><?= htmlspecialchars($r['created_at']) ?></td>
            <td class="text-nowrap">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" data-action="read" title="Mark read"><i class="bi bi-eye"></i></button>
                <button class="btn btn-outline-secondary" data-action="closed" title="Close"><i class="bi bi-archive"></i></button>
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
    var id = btn.closest('tr').dataset.id;
    fetch('../api/enquiries.php?id=' + id, {
      method: 'PUT', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: btn.dataset.action })
    }).then(function (r) { if (r.ok) location.reload(); });
  });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
