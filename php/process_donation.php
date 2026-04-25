<?php
/**
 * Process Donation — Validates, sanitises, and stores donation data.
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../donate.html');
    exit;
}

// Sanitise inputs
$name           = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email          = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone          = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$amount         = floatval($_POST['amount'] ?? 0);
$cause          = htmlspecialchars(trim($_POST['cause'] ?? 'general'), ENT_QUOTES, 'UTF-8');
$payment_method = htmlspecialchars(trim($_POST['payment_method'] ?? 'upi'), ENT_QUOTES, 'UTF-8');
$message        = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
$anonymous      = isset($_POST['anonymous']) ? 1 : 0;

// Validate required fields
$errors = [];
if (empty($name))                          $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($amount < 1)                           $errors[] = 'Donation amount must be at least ₹1.';

if (!empty($errors)) {
    // Redirect back with error (simple approach)
    header('Location: ../donate.html?error=' . urlencode(implode(' ', $errors)));
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO donations (name, email, phone, amount, cause, payment_method, message, anonymous)
        VALUES (:name, :email, :phone, :amount, :cause, :payment_method, :message, :anonymous)
    ");
    $stmt->execute([
        ':name'           => $name,
        ':email'          => $email,
        ':phone'          => $phone,
        ':amount'         => $amount,
        ':cause'          => $cause,
        ':payment_method' => $payment_method,
        ':message'        => $message,
        ':anonymous'      => $anonymous,
    ]);

    header('Location: ../thankyou.html');
    exit;
} catch (PDOException $e) {
    header('Location: ../donate.html?error=' . urlencode('Something went wrong. Please try again.'));
    exit;
}
