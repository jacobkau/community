<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// User status
$user_id = $_SESSION['user_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Privacy Policy</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<?php include __DIR__ . "/includes/header.php" ?>

<body data-theme="light">
  <div class="row" style="margin-top: 5vh;">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 p-0"
      style=" border-color: var(--border-color); color: var(--text-color);">
      <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    </div>

    <main class="col-12 col-lg-9 px-md-4 pt-4">
      <div class="col-12 col-md-11 col-lg-10 col-xl-9" style="background-color: var(--card-bg); border-color: var(--border-color);padding:40px;border-radius:10px">
        <h1 class="mb-4">Privacy Policy</h1>
        <p>Effective date: Monday 12th May, 2025</p>

        <h2>1. Introduction</h2>
        <p>
          Welcome to Bits Catholic Portal. We value your privacy and are committed to protecting your personal data.
        </p>

        <h2>2. Data We Collect</h2>
        <ul>
          <li><strong>Personal Data:</strong> Name, Email, Username.</li>
          <li><strong>Usage Data:</strong> Pages visited, IP address, browser type.</li>
          <li><strong>Cookies & Tracking:</strong> Session cookies and analytics.</li>
        </ul>

        <h2>3. How We Use Your Data</h2>
        <ul>
          <li>To provide and maintain our service.</li>
          <li>To notify you about changes or updates.</li>
          <li>To improve user experience.</li>
        </ul>

        <h2>4. Third-Party Services</h2>
        <p>
          We may employ third-party companies for analytics, email delivery, and hosting. These providers have access only to the data they need to perform their functions and are bound by confidentiality agreements.
        </p>

        <h2>5. Your Rights</h2>
        <p>
          You can request access, correction, or deletion of your personal data at any time by contacting us at <a href="mailto:info@bitscatholicportal.co.ke">Bits Catholic Portal.</a>.
        </p>

        <h2>6. Changes to This Policy</h2>
        <p>
          We may update this policy periodically. We’ll notify you of changes by posting the new Privacy Policy here.
        </p>

        <hr>
        <p class="small text-muted">
          This template is based on standard examples :contentReference[oaicite:0]{index=0}.
        </p>
      </div>

      </main>
        <aside class="col-lg-3 order-lg-last">
            <div class="d-none d-lg-block position-fixed vh-100 end-0 p-3"
                style="width: 400px; top: 2vh; z-index: 100;margin-right:7vh; overflow-y: auto;">
                <!-- Right sidebar content from right.php -->
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>

            <!-- Mobile version (hidden on lg and up) -->
            <div class="d-lg-none">
                <?php include __DIR__ . '/includes/right.php'; ?>
            </div>
        </aside>
    </div>
    <?php include __DIR__ . '/includes/footer.php';
    require_once __DIR__ . "/post_functions.php"; ?>
</body>

</html>