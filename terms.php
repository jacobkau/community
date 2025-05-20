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
  <title>Terms of Service</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->

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
        <h1 class="mb-4">Terms of Service</h1>
        <p>Effective date: Monday 12th May, 2025</p>

        <h2>1. Acceptance of Terms</h2>
        <p>
          By accessing or using Bits Catholic Portal., you agree to be bound by these Terms.
        </p>

        <h2>2. Your Obligations</h2>
        <ul>
          <li>Provide accurate registration information.</li>
          <li>Maintain confidentiality of your password.</li>
          <li>Comply with all applicable laws and regulations.</li>
        </ul>

        <h2>3. User Content</h2>
        <p>
          You retain ownership of content you post, but grant us a license to display and distribute it on our platform.
        </p>

        <h2>4. Prohibited Conduct</h2>
        <p>
          Users must not post unlawful, harassing, or infringing content.
        </p>

        <h2>5. Disclaimer of Warranties</h2>
        <p>
          Our service is provided “as is” without warranties of any kind.
        </p>

        <h2>6. Limitation of Liability</h2>
        <p>
          We won’t be liable for indirect, incidental, or punitive damages arising out of your use of the service.
        </p>

        <h2>7. Modifications</h2>
        <p>
          We reserve the right to modify these Terms at any time. Continued use after changes constitutes acceptance.
        </p>

        <h2>8. Governing Law</h2>
        <p>
          These Terms are governed by the laws of {{Your Country/State}}.
        </p>

        <h2>9. Contact</h2>
        <p>
          Questions? Email us at <a href="mailto:Bits Catholic Portal.">Bits Catholic Portal.</a>.
        </p>

        <hr>
        <p class="small text-muted">
          Adapted from standard ToS templates :contentReference[oaicite:1]{index=1}.
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