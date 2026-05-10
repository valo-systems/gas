<?php
require_once __DIR__ . '/../api/auth.php';

// If already signed in, redirect to dashboard.
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login | Gas @ Midway Mews</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
<link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg, #071A2C 0%, #003B73 100%); min-height: 100vh; }
  .login-card {
    max-width: 420px; margin: 8vh auto; background: #fff;
    border-radius: 16px; padding: 36px 32px; box-shadow: 0 20px 60px rgba(0,0,0,.25);
  }
  .login-brand { font-weight: 800; font-size: 1.4rem; color: #071A2C; }
  .login-brand .accent { color: #D90416; }
</style>
</head>
<body>

<div class="login-card">
  <div class="text-center mb-4">
    <div class="login-brand">Gas <span class="accent">@ Midway Mews</span></div>
    <p class="text-muted small mb-0">Staff sign-in</p>
  </div>

  <div id="loginStatus" class="alert d-none mb-3" role="alert"></div>

  <form id="loginForm" novalidate>
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input type="email" class="form-control" id="email" name="email" required autocomplete="username">
    </div>
    <div class="mb-3">
      <label class="form-label" for="password">Password</label>
      <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-brand-red w-100">
      <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
    </button>
  </form>

  <div class="text-center mt-3">
    <a href="../index.html" class="small text-muted">&larr; Back to website</a>
  </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function (e) {
  e.preventDefault();
  var status = document.getElementById('loginStatus');
  var data = Object.fromEntries(new FormData(e.target).entries());

  status.className = 'alert alert-info';
  status.textContent = 'Signing in…';

  fetch('../api/auth.php?action=login', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(data)
  }).then(function (r) {
    return r.json().then(function (j) { return { ok: r.ok, body: j }; });
  }).then(function (res) {
    if (res.ok && res.body.ok) {
      window.location.href = 'dashboard.php';
    } else {
      status.className = 'alert alert-danger';
      status.textContent = (res.body && res.body.error) || 'Sign-in failed.';
    }
  }).catch(function () {
    status.className = 'alert alert-danger';
    status.textContent = 'Could not reach the server. Is PHP running?';
  });
});
</script>
</body>
</html>
