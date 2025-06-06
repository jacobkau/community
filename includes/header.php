<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Toastr (optional) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- GLightbox -->
    <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet" />

    <link rel="shortcut icon" href="logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./assets/css/style.css">

    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <title>Bits Catholic Portal | Forum</title>
</head>

<body data-theme="light" style="margin-bottom:3vh;">
    <div class="fixed-top d-flex justify-content-center" style="z-index: 1030;">
        <div class="col-12 col-md-11 col-lg-10 col-xl-9 px-3 px-md-4 mx-auto">
            <nav class="navbar navbar-expand-lg py-2" data-bs-theme="light" style="border-radius:19px;" aria-label="Main navigation">
                <div class="container-fluid px-3 px-md-4">
                    <!-- Mobile sidebar toggle button -->
                    <button class="btn btn-primary d-lg-none me-2" type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasProfile"
                        aria-controls="offcanvasProfile">
                        <i class="bi bi-list"></i>
                    </button>

                    <!-- Brand logo -->
                    <a class="navbar-brand d-flex align-items-center" href="#">
                        <img src="logo.jpg" alt="logo" class="img-fluid me-2" style="height: 40px; width: auto;border-radius:5px">
                        <span class="theme-text fw-semibold">Bits Catholic Portal Community</span>
                    </a>

                    <!-- Main navbar toggle -->
                    <button class="navbar-toggler" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#navbarMain"
                        aria-controls="navbarMain"
                        aria-expanded="false"
                        aria-label="Toggle navigation">
                        <span><i class="fas fa-bars"></i></span>
                    </button>

                    <!-- Main navbar content -->
                    <div class="collapse navbar-collapse" id="navbarMain">
                        <ul class="navbar-nav me-auto" >
                            <li class="nav-item"s>
                                <a class="nav-link active" href="/community/index.php" >
                                     <span style="color:#fbfbfb"><i class="bi bi-house-door me-1"></i>Home</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link theme-text" href="/bits-catholic-portal/public/contact.php">
                                   <span style="color:#fbfbfb"><i class="bi bi-envelope me-1"></i> Contact Us</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/community/privacy.php">
                                     <span style="color:#fbfbfb"><i class="bi bi-shield-lock me-1"></i> Privacy Policy</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/community/terms.php">
                                     <span style="color:#fbfbfb"><i class="bi bi-file-earmark-text me-1"></i> Terms</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/bits-catholic-portal/admin/site.php">
                                     <span style="color:#fbfbfb"><i class="bi bi-globe me-1"></i> Portal Homepage</span>
                                </a>
                            </li>
                        </ul>

                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item mx-2">
                                <a class="nav-link theme-toggle px-2" onclick="toggleTheme()" title="Toggle Theme" role="button">
                                    <span class="theme-icon">🌓</span>
                                </a>
                            </li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item ms-3">
                                    <a class="nav-link theme-text px-3 py-1 rounded" href="logout.php"
                                        style="border: 1px solid var(--accent-color);">
                                         <span style="color:#fbfbfb"> <i class="fa-solid fa-power-off"></i> Logout</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item mx-2">
                                    <a class="nav-link theme-text px-3 py-1 rounded text-light" href="login.php">
                                        Login
                                    </a>
                                </li>
                                <li class="nav-item ms-2">
                                    <a class="nav-link theme-text px-3 py-1 rounded"
                                        style="background-color: var(--accent-color); color: white !important;"
                                        href="register.php">
                                        Register
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <!-- Mobile sidebar offcanvas -->
    <div class="offcanvas offcanvas-start" style="margin-top: 10vh; color:var(--text-color)" tabindex="-1" id="offcanvasProfile" aria-labelledby="offcanvasProfileLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title d-flex align-items-center" id="offcanvasProfileLabel">
                <i class="bi bi-person-circle me-2"></i> Profile
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-lg-none">
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
                    WHERE id = ?
                ");
                $stmt->execute([$user_id, $user_id, $user_id]);
                $userData = $stmt->fetch();
            }
            ?>

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
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</body>

</html>