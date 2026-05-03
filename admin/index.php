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

// ── CSV Report Download Handler ──
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    $period = $_GET['period'] ?? 'all';
    $causeFilter = $_GET['cause'] ?? '';

    // Determine date range
    $dateCondition = '';
    $params = [];
    switch ($period) {
        case 'weekly':
            $dateCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            $periodLabel = 'Weekly';
            break;
        case 'monthly':
            $dateCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
            $periodLabel = 'Monthly';
            break;
        case 'yearly':
            $dateCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
            $periodLabel = 'Yearly';
            break;
        default:
            $periodLabel = 'All-Time';
    }

    // Optional cause filter
    $causeCondition = '';
    if (!empty($causeFilter)) {
        $causeCondition = 'AND cause = :cause';
        $params[':cause'] = $causeFilter;
    }

    $sql = "SELECT id, name, email, phone, amount, cause, payment_method, message, anonymous, created_at
            FROM donations WHERE 1=1 $dateCondition $causeCondition ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Generate filename
    $filename = 'hopehands_donations_' . strtolower($periodLabel);
    if (!empty($causeFilter)) $filename .= '_' . $causeFilter;
    $filename .= '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // CSV Header
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Amount (₹)', 'Cause', 'Payment Method', 'Message', 'Anonymous', 'Date']);
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['id'],
            $row['anonymous'] ? 'Anonymous' : $row['name'],
            $row['email'],
            $row['phone'],
            $row['amount'],
            ucfirst($row['cause']),
            strtoupper($row['payment_method']),
            $row['message'],
            $row['anonymous'] ? 'Yes' : 'No',
            date('d M Y, h:i A', strtotime($row['created_at']))
        ]);
    }
    fclose($output);
    exit;
}

// ── Delete Donation Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = intval($_POST['donation_id'] ?? 0);
    if ($deleteId > 0) {
        $stmt = $pdo->prepare("DELETE FROM donations WHERE id = :id");
        $stmt->execute([':id' => $deleteId]);
    }
    header('Location: index.php?msg=deleted');
    exit;
}

// ── Update Donation Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $updateId       = intval($_POST['donation_id'] ?? 0);
    $updateName     = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $updateEmail    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $updatePhone    = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $updateAmount   = floatval($_POST['amount'] ?? 0);
    $updateCause    = htmlspecialchars(trim($_POST['cause'] ?? 'general'), ENT_QUOTES, 'UTF-8');
    $updatePayment  = htmlspecialchars(trim($_POST['payment_method'] ?? 'upi'), ENT_QUOTES, 'UTF-8');
    $updateMessage  = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
    $updateAnonymous = isset($_POST['anonymous']) ? 1 : 0;

    if ($updateId > 0 && !empty($updateName) && filter_var($updateEmail, FILTER_VALIDATE_EMAIL) && $updateAmount >= 1) {
        $stmt = $pdo->prepare("
            UPDATE donations SET name = :name, email = :email, phone = :phone, amount = :amount,
                   cause = :cause, payment_method = :payment_method, message = :message, anonymous = :anonymous
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'           => $updateName,
            ':email'          => $updateEmail,
            ':phone'          => $updatePhone,
            ':amount'         => $updateAmount,
            ':cause'          => $updateCause,
            ':payment_method' => $updatePayment,
            ':message'        => $updateMessage,
            ':anonymous'      => $updateAnonymous,
            ':id'             => $updateId,
        ]);
    }
    header('Location: index.php?msg=updated');
    exit;
}

// ── Delete Contact Message Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    $deleteId = intval($_POST['message_id'] ?? 0);
    if ($deleteId > 0) {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = :id");
        $stmt->execute([':id' => $deleteId]);
    }
    header('Location: index.php?msg=msg_deleted');
    exit;
}

$flashMsg = $_GET['msg'] ?? '';

// Fetch stats
$totalDonations = $pdo->query("SELECT COALESCE(SUM(amount),0) as total FROM donations")->fetch()['total'];
$donationCount  = $pdo->query("SELECT COUNT(*) as cnt FROM donations")->fetch()['cnt'];
$messageCount   = $pdo->query("SELECT COUNT(*) as cnt FROM contacts")->fetch()['cnt'];

// Fetch cause-wise aggregation
$causeStats = $pdo->query("
    SELECT cause,
           COUNT(*) as donor_count,
           COALESCE(SUM(amount), 0) as total_amount
    FROM donations
    GROUP BY cause
    ORDER BY total_amount DESC
")->fetchAll();

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
  <style>
    /* ── Admin Modal ── */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      padding: var(--sp-lg);
    }
    .modal-overlay.active { display: flex; }

    .modal-box {
      background: var(--clr-surface);
      border: 1px solid var(--clr-border);
      border-radius: var(--radius-xl);
      width: 100%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      padding: var(--sp-2xl);
      position: relative;
      animation: modalIn 0.3s var(--ease-out);
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(20px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-close {
      position: absolute;
      top: var(--sp-lg); right: var(--sp-lg);
      width: 36px; height: 36px;
      border-radius: 50%;
      background: rgba(255,255,255,0.06);
      border: 1px solid var(--clr-border);
      color: var(--clr-text);
      font-size: var(--fs-lg);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: var(--transition);
    }
    .modal-close:hover { background: var(--clr-danger); border-color: var(--clr-danger); }

    .modal-title {
      font-size: var(--fs-xl);
      font-weight: 700;
      margin-bottom: var(--sp-xl);
    }

    .modal-form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: var(--sp-md);
    }
    .modal-form-grid .full { grid-column: 1 / -1; }

    /* Action Buttons in table */
    .action-btns { display: flex; gap: var(--sp-xs); }
    .action-btn {
      padding: 4px 10px;
      border-radius: var(--radius-sm);
      font-size: var(--fs-xs);
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      border: 1px solid transparent;
    }
    .action-btn-edit {
      background: rgba(16,185,129,0.1);
      color: var(--clr-primary-light);
      border-color: rgba(16,185,129,0.2);
    }
    .action-btn-edit:hover { background: var(--clr-primary); color: #fff; }
    .action-btn-delete {
      background: rgba(239,68,68,0.1);
      color: var(--clr-danger);
      border-color: rgba(239,68,68,0.2);
    }
    .action-btn-delete:hover { background: var(--clr-danger); color: #fff; }

    /* Flash message */
    .flash-msg {
      padding: var(--sp-md) var(--sp-lg);
      border-radius: var(--radius-md);
      font-size: var(--fs-sm);
      font-weight: 500;
      margin-bottom: var(--sp-lg);
      display: flex;
      align-items: center;
      gap: var(--sp-sm);
      animation: modalIn 0.3s var(--ease-out);
    }
    .flash-msg-success {
      background: rgba(34,197,94,0.1);
      border: 1px solid rgba(34,197,94,0.3);
      color: var(--clr-success);
    }
  </style>
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

      <?php if ($flashMsg === 'deleted'): ?>
        <div class="flash-msg flash-msg-success">✅ Donation deleted successfully.</div>
      <?php elseif ($flashMsg === 'updated'): ?>
        <div class="flash-msg flash-msg-success">✅ Donation updated successfully.</div>
      <?php elseif ($flashMsg === 'msg_deleted'): ?>
        <div class="flash-msg flash-msg-success">✅ Message deleted successfully.</div>
      <?php endif; ?>

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

      <!-- Download Reports -->
      <div class="table-wrapper" style="margin-bottom:var(--sp-2xl)">
        <div class="table-header">📊 Download Donation Reports</div>
        <div style="padding:var(--sp-xl)">
          <p style="color:var(--clr-text-muted);font-size:var(--fs-sm);margin-bottom:var(--sp-lg)">Download CSV reports of all donations filtered by time period. You can also download cause-specific reports from the breakdown section below.</p>
          <div style="display:flex;gap:var(--sp-md);flex-wrap:wrap">
            <a href="?download=csv&period=weekly" class="btn btn-outline" style="font-size:var(--fs-sm)">📅 This Week</a>
            <a href="?download=csv&period=monthly" class="btn btn-outline" style="font-size:var(--fs-sm)">🗓️ This Month</a>
            <a href="?download=csv&period=yearly" class="btn btn-outline" style="font-size:var(--fs-sm)">📆 This Year</a>
            <a href="?download=csv&period=all" class="btn btn-primary" style="font-size:var(--fs-sm)">⬇️ All Time</a>
          </div>
        </div>
      </div>

      <!-- Cause-wise Breakdown -->
      <div class="table-wrapper" style="margin-bottom:var(--sp-2xl)">
        <div class="table-header">🎯 Donations by Cause</div>
        <div style="padding:var(--sp-xl)">
          <?php if (empty($causeStats)): ?>
            <p style="text-align:center;color:var(--clr-text-muted);padding:var(--sp-lg)">No donations recorded yet.</p>
          <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:var(--sp-lg)">
              <?php foreach ($causeStats as $cs): ?>
                <?php
                  $causeLabel  = ucfirst($cs['cause']);
                  $causeTotal  = $cs['total_amount'];
                  $causeDonors = $cs['donor_count'];
                  $percentage  = $totalDonations > 0 ? round(($causeTotal / $totalDonations) * 100, 1) : 0;
                ?>
                <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);padding:var(--sp-lg);transition:var(--transition)" onmouseover="this.style.borderColor='rgba(16,185,129,0.3)'" onmouseout="this.style.borderColor='var(--clr-border)'">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--sp-sm)">
                    <span class="badge" style="font-size:var(--fs-sm);padding:4px 14px"><?= htmlspecialchars($causeLabel) ?></span>
                    <span style="font-size:var(--fs-xs);color:var(--clr-text-dim)"><?= $percentage ?>% of total</span>
                  </div>
                  <div style="font-size:var(--fs-3xl);font-weight:800;background:var(--grad-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:var(--sp-xs)">₹<?= number_format($causeTotal, 0) ?></div>
                  <div style="font-size:var(--fs-sm);color:var(--clr-text-muted);margin-bottom:var(--sp-md)"><?= $causeDonors ?> donation<?= $causeDonors !== 1 ? 's' : '' ?></div>
                  <div class="progress-bar" style="margin-bottom:var(--sp-md)"><div class="progress-fill" style="width:<?= $percentage ?>%"></div></div>
                  <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                    <a href="?download=csv&period=all&cause=<?= urlencode($cs['cause']) ?>" class="btn btn-outline" style="font-size:var(--fs-xs);padding:0.4rem 1rem">⬇️ All</a>
                    <a href="?download=csv&period=monthly&cause=<?= urlencode($cs['cause']) ?>" class="btn btn-outline" style="font-size:var(--fs-xs);padding:0.4rem 1rem">🗓️ Monthly</a>
                    <a href="?download=csv&period=weekly&cause=<?= urlencode($cs['cause']) ?>" class="btn btn-outline" style="font-size:var(--fs-xs);padding:0.4rem 1rem">📅 Weekly</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
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
                <th>Cause</th><th>Payment</th><th>Date</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($donations)): ?>
                <tr><td colspan="8" style="text-align:center;padding:var(--sp-xl)">No donations yet.</td></tr>
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
                    <td>
                      <div class="action-btns">
                        <button type="button" class="action-btn action-btn-edit" onclick='openEditModal(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️ Edit</button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete donation #<?= $d['id'] ?>? This cannot be undone.')">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="donation_id" value="<?= $d['id'] ?>">
                          <button type="submit" class="action-btn action-btn-delete">🗑️ Delete</button>
                        </form>
                      </div>
                    </td>
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
                <th>Message</th><th>Date</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($contacts)): ?>
                <tr><td colspan="7" style="text-align:center;padding:var(--sp-xl)">No messages yet.</td></tr>
              <?php else: ?>
                <?php foreach ($contacts as $c): ?>
                  <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= htmlspecialchars($c['subject']) ?></td>
                    <td style="max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($c['message']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></td>
                    <td>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete message #<?= $c['id'] ?> from <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>? This cannot be undone.')">
                        <input type="hidden" name="action" value="delete_message">
                        <input type="hidden" name="message_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="action-btn action-btn-delete">🗑️ Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
  <!-- Edit Donation Modal -->
  <div class="modal-overlay" id="editModal">
    <div class="modal-box">
      <button class="modal-close" onclick="closeEditModal()">&times;</button>
      <div class="modal-title">✏️ Edit Donation</div>
      <form method="POST" id="editForm">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="donation_id" id="editId">
        <div class="modal-form-grid">
          <div class="form-group">
            <label for="editName">Full Name *</label>
            <input type="text" id="editName" name="name" required>
          </div>
          <div class="form-group">
            <label for="editEmail">Email *</label>
            <input type="email" id="editEmail" name="email" required>
            <div class="error-msg">Please enter a valid email address (e.g. name@domain.com)</div>
          </div>
          <div class="form-group">
            <label for="editPhone">Phone</label>
            <input type="tel" id="editPhone" name="phone">
          </div>
          <div class="form-group">
            <label for="editAmount">Amount (₹) *</label>
            <input type="number" id="editAmount" name="amount" min="1" required>
          </div>
          <div class="form-group">
            <label for="editCause">Cause</label>
            <select id="editCause" name="cause">
              <option value="general">General Fund</option>
              <option value="education">Education</option>
              <option value="healthcare">Healthcare</option>
              <option value="water">Clean Water</option>
              <option value="food">Food & Hunger</option>
              <option value="environment">Environment</option>
              <option value="empowerment">Women Empowerment</option>
            </select>
          </div>
          <div class="form-group">
            <label for="editPayment">Payment Method</label>
            <select id="editPayment" name="payment_method">
              <option value="upi">UPI</option>
              <option value="card">Credit / Debit Card</option>
              <option value="netbanking">Net Banking</option>
              <option value="wallet">Digital Wallet</option>
            </select>
          </div>
          <div class="form-group full">
            <label for="editMessage">Message</label>
            <textarea id="editMessage" name="message" rows="3"></textarea>
          </div>
          <div class="form-group full" style="display:flex;align-items:center;gap:var(--sp-sm)">
            <input type="checkbox" id="editAnonymous" name="anonymous" style="width:18px;height:18px;accent-color:var(--clr-primary)">
            <label for="editAnonymous" style="margin:0;cursor:pointer">Anonymous donation</label>
          </div>
        </div>
        <div style="display:flex;gap:var(--sp-md);margin-top:var(--sp-lg)">
          <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">💾 Save Changes</button>
          <button type="button" class="btn btn-outline" onclick="closeEditModal()" style="flex:1;justify-content:center">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function openEditModal(d) {
      // Clear any previous errors
      document.querySelectorAll('#editForm .form-group').forEach(g => g.classList.remove('has-error'));

      document.getElementById('editId').value       = d.id;
      document.getElementById('editName').value     = d.name;
      document.getElementById('editEmail').value    = d.email;
      document.getElementById('editPhone').value    = d.phone || '';
      document.getElementById('editAmount').value   = d.amount;
      document.getElementById('editCause').value    = d.cause;
      document.getElementById('editPayment').value  = d.payment_method;
      document.getElementById('editMessage').value  = d.message || '';
      document.getElementById('editAnonymous').checked = d.anonymous == 1;
      document.getElementById('editModal').classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.remove('active');
      document.body.style.overflow = '';
    }

    // ── Edit form email validation ──
    const editEmail = document.getElementById('editEmail');
    const editForm  = document.getElementById('editForm');

    // Real-time: show error on blur if invalid
    editEmail.addEventListener('blur', () => {
      if (editEmail.value.trim() && !emailRegex.test(editEmail.value)) {
        editEmail.closest('.form-group').classList.add('has-error');
      }
    });

    // Real-time: clear error as user types a valid email
    editEmail.addEventListener('input', () => {
      if (emailRegex.test(editEmail.value)) {
        editEmail.closest('.form-group').classList.remove('has-error');
      }
    });

    // Prevent form submission if email is invalid
    editForm.addEventListener('submit', (e) => {
      // Clear previous errors
      editForm.querySelectorAll('.form-group').forEach(g => g.classList.remove('has-error'));

      let valid = true;

      // Name validation
      const editName = document.getElementById('editName');
      if (!editName.value.trim()) {
        editName.closest('.form-group').classList.add('has-error');
        valid = false;
      }

      // Email validation
      if (!editEmail.value.trim() || !emailRegex.test(editEmail.value)) {
        editEmail.closest('.form-group').classList.add('has-error');
        valid = false;
      }

      // Amount validation
      const editAmount = document.getElementById('editAmount');
      if (!editAmount.value || parseFloat(editAmount.value) < 1) {
        editAmount.closest('.form-group').classList.add('has-error');
        valid = false;
      }

      if (!valid) e.preventDefault();
    });

    // Close modal on overlay click
    document.getElementById('editModal').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) closeEditModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeEditModal();
    });

    // Auto-dismiss flash messages after 4 seconds
    document.querySelectorAll('.flash-msg').forEach(el => {
      setTimeout(() => {
        el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        setTimeout(() => el.remove(), 400);
      }, 4000);
    });
  </script>
</body>
</html>
