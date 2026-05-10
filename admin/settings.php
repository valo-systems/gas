<?php
require_once __DIR__ . '/../api/db.php';
$page_title = 'Settings';
$active_tab = 'settings';
require __DIR__ . '/_layout.php';

$rows = db()->query('SELECT setting_key, setting_value FROM business_settings')->fetchAll();
$settings = [];
foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];

$fields = [
    'business_name'   => ['Business name',     'text'],
    'primary_phone'   => ['Primary phone',     'tel'],
    'secondary_phone' => ['Secondary phone',   'tel'],
    'whatsapp_number' => ['WhatsApp number (international, no +)', 'text'],
    'whatsapp_alt'    => ['WhatsApp alt number', 'text'],
    'address'         => ['Physical address',  'text'],
    'trading_hours'   => ['Trading hours',     'text'],
    'latitude'        => ['Latitude (decimal, e.g. -25.98688)',   'text'],
    'longitude'       => ['Longitude (decimal, e.g. 28.11176)',   'text'],
    'google_maps_url' => ['Google Maps directions URL', 'url'],
];
?>

<p class="text-muted mb-3">These values are pulled into every page on the public site.</p>

<form id="settingsForm" class="form-card" style="max-width:680px">
  <div id="settingsStatus" class="alert d-none mb-3"></div>
  <?php foreach ($fields as $key => [$label, $type]): ?>
    <div class="mb-3">
      <label class="form-label" for="<?= $key ?>"><?= htmlspecialchars($label) ?></label>
      <input type="<?= $type ?>" class="form-control" id="<?= $key ?>" name="<?= $key ?>"
             value="<?= htmlspecialchars($settings[$key] ?? '') ?>">
    </div>
  <?php endforeach; ?>
  <button type="submit" class="btn btn-brand-red"><i class="bi bi-save me-1"></i>Save changes</button>
</form>

<script>
document.getElementById('settingsForm').addEventListener('submit', function (e) {
  e.preventDefault();
  var data = Object.fromEntries(new FormData(e.target).entries());
  var status = document.getElementById('settingsStatus');
  status.className = 'alert alert-info';
  status.textContent = 'Saving…';
  fetch('../api/settings.php', {
    method: 'PUT', credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  }).then(function (r) { return r.ok; }).then(function (ok) {
    status.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
    status.textContent = ok ? 'Settings saved.' : 'Could not save.';
  });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
