<?php
// Ensure $title is set
$title = $title ?? 'Peer Tech Konnect Blog';

// Ensure $content is set
$content = $content ?? '';

// Function to safely output URLs
function url($path) {
    return htmlspecialchars('/blog-project/public/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="../../favicons/favicon3.png" type="image/x-icon">
    <link rel="manifest" href="/blog-project/public/site.webmanifest">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    
    <style>
        .navbar-brand img {
            height: 30px; /* Adjust this value to match your logo's height */
            margin-right: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/blog-project/public/">
                <img src="../../favicons/favicon3.png" alt="Blog Logo" class="d-inline-block align-top">
                Peer Tech Konnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/blog-project/public/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/blog-project/public/index.php?post=about">About</a>
                    </li>
                </ul>
                <form class="d-flex" action="/blog-project/public/search.php" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search" aria-label="Search">
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?= $content ?>
                <?php
                // Add "Back to Home" button for individual posts and search results
                if (($post_slug ?? '') !== 'welcome' && ($post_slug ?? '') !== 'about') {
                    echo '<a href="/blog-project/public/" class="btn btn-primary mt-3">Back to Home</a>';
                }
                ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Peer Tech Konnect. All rights reserved.</p>
            <ul class="footer-links">
                <li><a href="<?= url('') ?>">Home</a></li>
                <li><a href="<?= url('index.php?post=about') ?>">About</a></li>
                <li><a href="<?= url('index.php?post=contact') ?>">Contact</a></li>
            </ul>
            <div class="footer-social">
                <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
