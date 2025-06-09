<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../config/db.php';

$userData = null;

if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $stmt = $pdo->prepare("
    SELECT username, email, profile_pic,
           (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS post_count,
           (SELECT COUNT(*) FROM comments WHERE user_id = ?) AS comment_count
    FROM users
    WHERE user_id = ?
  ");
  $stmt->execute([$user_id, $user_id, $user_id]);
  $userData = $stmt->fetch();
}
?>

<!-- Sidebar -->
<nav id="profileSidebar" class="col-lg-12 col-md-4 col-sm-1 position-fixed vh-100 d-none d-lg-block"
  style="width: 300px; top: 6vh; padding: 20px;">

  <!-- User Info -->
  <div class="text-center mb-4">
    <?php if (!empty($userData['profile_pic'])): ?>
      <img src="<?= htmlspecialchars($userData['profile_pic']) ?>"
        alt="<?= htmlspecialchars($userData['username']) ?>'s avatar"
        class="rounded-circle mb-2 border border-2"
        style="width: 80px; height: 80px; object-fit: cover;">
    <?php else: ?>
      <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-2 border border-2"
        style="width: 80px; height: 80px; font-size: 32px;">
        <?= strtoupper(substr($userData['username'] ?? 'U', 0, 1)) ?>
      </div>
    <?php endif; ?>

    <h5 class="mb-0 fw-semibold" style="color:var(--text-color)"><?= htmlspecialchars($userData['username'] ?? 'Guest') ?></h5>
    <small class="text-muted"><?= htmlspecialchars($userData['email'] ?? '') ?></small>
    <small class="text-muted"><?= htmlspecialchars($userData['bio'] ?? '') ?></small>

  </div>

  <hr>

  <!-- Navigation Links -->
  <ul class="nav nav-pills flex-column mb-auto">
    <li class="nav-item sidebar-links">
      <a href="/bits-catholic-portal/public/profile.php" class="nav-link text-dark">
        <i class="bi bi-person-circle me-2"></i> Profile Overview
      </a>
    </li>
    <li>
      <a href="/bits-catholic-portal/public/profile_settings.php" class="nav-link side-bar text-dark">
        <i class="bi bi-pencil-square me-2"></i> Edit Profile
      </a>
    </li>
    <li class="nav-item">
      <a href="mains.php#posts" class="nav-link text-dark">
        <i class="bi bi-file-earmark-text me-2"></i> My Participation
      </a>
    </li>
    <li>
      <a href="following.php" class="nav-link text-dark">
        <i class="bi bi-people me-2"></i> Following
      </a>
    </li>
    <li>
      <a href="settings.php" class="nav-link text-dark">
        <i class="bi bi-gear me-2"></i> Account Settings
      </a>
    </li>
    <li>
      <a href="/community/notification.php" class="nav-link text-dark">
        <i class="bi bi-bell me-2"></i> Notifications
      </a>
    </li>
    <li>
      <a href="/community/message.php" class="nav-link text-dark">
        <i class="bi bi-envelope me-2"></i> Messages
      </a>
    </li>
    <li>
      <a href="/community/profile.php" class="nav-link text-dark">
        <i class="bi bi-person me-2"></i> View Profile
      </a>
    </li>
  </ul>

  <hr>

  <!-- Logout -->
  <div class="mt-auto">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="/logout" class="btn btn-outline-danger w-100">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
      </a>
    <?php endif ?>
  </div>

</nav>
