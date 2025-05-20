<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="shortcut icon" href="logo.jpg" type="image/x-icon">

    <link rel="stylesheet" href="./assets/css/style.css">
    <!-- Add inside <head> section -->
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <title>Bits Catholic Portal | Forum</title>
</head>

<body data-theme="light" style="margin-bottom:3vh;">
    <div class="fixed-top d-flex justify-content-center" style=" z-index: 1030;">

        <div class="col-12 col-md-11 col-lg-10 col-xl-9 px-3 px-md-4 mx-auto">
            <nav class="navbar navbar-expand-lg py-2 " data-bs-theme="light" style=" border-radius:19px;">
                <div class="container-fluid px-3 px-md-4">
                    <button class="btn btn-primary d-lg-none" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasProfile" aria-controls="offcanvasProfile">
                        <i class="bi bi-list"></i>
                    </button>
                    <a class="navbar-brand d-flex align-items-center" href="#">
                        <img src="logo.jpg" alt="logo" class="img-fluid me-2" style="height: 40px; width: auto;border-radius:5px">
                        <span class="theme-text fw-semibold">Bits Catholic Portal Community</span>
                    </a>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a class="nav-link active" id="nav-link" href="/community/index.php">
                                    <i class="bi bi-house-door"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" id="nav-link" href="/bits-catholic-portal/public/contact.php">
                                    <i class="bi bi-envelope"></i> Contact Us
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" id="nav-link" href="/community/privacy.php">
                                    <i class="bi bi-shield-lock"></i> Privacy Policy
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" id="nav-link" href="/community/terms.php">
                                    <i class="bi bi-file-earmark-text"></i> Terms and Conditions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" id="nav-link" href="/bits-catholic-portal/admin/site.php">
                                    <i class="bi bi-globe"></i> Portal Homepage
                                </a>
                            </li>
                        </ul>
                    </div>
                    <button class="navbar-toggler" style="color:white !important" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span><i class="fas fa-bars"></i></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item mx-2">
                                <a class="nav-link theme-toggle px-2" onclick="toggleTheme()" title="Toggle Theme" role="button">
                                    <span class="theme-icon-container">
                                        <span class="theme-icon">🌓</span>
                                    </span>
                                </a>
                            </li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item ms-3">
                                    <a class="nav-link theme-text px-3 py-1 rounded" id="nav-link" href="logout.php"
                                        style="border: 1px solid var(--accent-color);">Logout</a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item mx-2">
                                    <a class="nav-link theme-text px-3 py-1 rounded text-light" id="nav-link" href="login.php">Login</a>
                                </li>
                                <li class="nav-item ms-2">
                                    <a class="nav-link theme-text px-3 py-1 rounded" id="nav-link" style="background-color: var(--accent-color); color: white !important;" href="register.php">Register</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>