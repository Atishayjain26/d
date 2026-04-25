<?php
/**
 * Admin Dashboard — View all donations and contact messages.
 * Basic HTTP authentication for security.
 */

// ── Simple Auth ──
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'hopehands2026';

if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $ADMIN_USER ||
    $_SERVER['PHP_AUTH_PW'] !== $ADMIN_PASS) {
    header('WWW-Authenticate: Basic realm="HopeHands Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied.';
    exit;
}

require_once __DIR__ . '/../php/config.php';
$pdo = getDBConnection();

// Fetch stats
$totalDonations = $pdo->query("SELECT COALESCE(SUM(amount),0) as total FROM donations")->fetch()['total'];
$donationCount  = $pdo->query("SELECT COUNT(*) as cnt FROM donations")->fetch()['cnt'];
$messageCount   = $pdo->query("SELECT COUNT(*) as cnt FROM contacts")->fetch()['cnt'];

// Fetch recent donations
$donations = $pdo->query("SELECT * FROM donations ORDER BY created_at DESC LIMIT 50")->fetchAll();

// Fetch recent messages
$contacts = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — HopeHands</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <header class="admin-header">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between">
      <div>
        <a href="../index.html" class="nav-logo" style="margin-bottom:var(--sp-xs)">
          <div class="logo-icon">🤲</div>
          <span>Hope<span style="color:var(--clr-primary-light)">Hands</span></span>
        </a>
        <p class="admin-title" style="margin-top:var(--sp-sm)">Admin Dashboard</p>
      </div>
      <a href="../index.html" class="btn btn-outline">← Back to Site</a>
    </div>
  </header>

  <main class="section" style="padding-top:var(--sp-2xl)">
    <div class="container">

      <!-- Stats -->
      <div class="admin-stats">
        <div class="admin-stat-card">
          <div class="label">Total Donations</div>
          <div class="value">₹<?= number_format($totalDonations, 0) ?></div>
        </div>
        <div class="admin-stat-card">
          <div class="label">Number of Donors</div>
          <div class="value"><?= $donationCount ?></div>
        </div>
        <div class="admin-stat-card">
          <div class="label">Contact Messages</div>
          <div class="value"><?= $messageCount ?></div>
        </div>
      </div>

      <!-- Donations Table -->
      <div class="table-wrapper">
        <div class="table-header">💰 Recent Donations</div>
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th><th>Name</th><th>Email</th><th>Amount</th>
                <th>Cause</th><th>Payment</th><th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($donations)): ?>
                <tr><td colspan="7" style="text-align:center;padding:var(--sp-xl)">No donations yet.</td></tr>
              <?php else: ?>
                <?php foreach ($donations as $d): ?>
                  <tr>
                    <td><?= $d['id'] ?></td>
                    <td><?= $d['anonymous'] ? '<em>Anonymous</em>' : htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['email']) ?></td>
                    <td><strong>₹<?= number_format($d['amount'], 0) ?></strong></td>
                    <td><span class="badge"><?= htmlspecialchars($d['cause']) ?></span></td>
                    <td><?= htmlspecialchars($d['payment_method']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($d['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Contacts Table -->
      <div class="table-wrapper">
        <div class="table-header">✉️ Contact Messages</div>
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th><th>Name</th><th>Email</th><th>Subject</th>
                <th>Message</th><th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($contacts)): ?>
                <tr><td colspan="6" style="text-align:center;padding:var(--sp-xl)">No messages yet.</td></tr>
              <?php else: ?>
                <?php foreach ($contacts as $c): ?>
                  <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= htmlspecialchars($c['subject']) ?></td>
                    <td style="max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($c['message']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</body>
</html>
