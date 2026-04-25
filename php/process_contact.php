<?php
/**
 * Process Contact Form — Validates, sanitises, and stores contact messages.
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contact.html');
    exit;
}

$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

$errors = [];
if (empty($name))                                $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Valid email is required.';
if (empty($subject))                             $errors[] = 'Subject is required.';
if (empty($message))                             $errors[] = 'Message is required.';

if (!empty($errors)) {
    header('Location: ../contact.html?error=' . urlencode(implode(' ', $errors)));
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO contacts (name, email, subject, message)
        VALUES (:name, :email, :subject, :message)
    ");
    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message,
    ]);

    header('Location: ../contact.html?success=1');
    exit;
} catch (PDOException $e) {
    header('Location: ../contact.html?error=' . urlencode('Something went wrong. Please try again.'));
    exit;
}
