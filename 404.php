<?php
declare(strict_types=1);

$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/404.php'));
if ($basePath === '/') {
    $basePath = '';
}

http_response_code(404);
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e7ebef">
    <meta name="robots" content="noindex, nofollow">
    <base href="<?= htmlspecialchars($basePath . '/', ENT_QUOTES, 'UTF-8') ?>">
    <title>Page not found — Bexxy Care and Clean</title>
    <link rel="icon" type="image/png" href="image/bexxy-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="Bexxy Care and Clean home">
            <img src="image/download.png" alt="Bexxy Care and Clean" width="480" height="140">
        </a>
        <a class="nav-cta" href="index.php">Back to home <span aria-hidden="true">↗</span></a>
    </header>

    <main class="not-found-page page-shell">
        <div class="not-found-art" aria-hidden="true">
            <span class="not-found-sparkle">✳</span>
            <span class="not-found-number">404</span>
            <span class="not-found-orbit"></span>
        </div>
        <div class="not-found-copy">
            <div class="eyebrow">Looks like this page needs a little tidying</div>
            <h1>We can’t find<br><span class="headline-accent">that page.</span></h1>
            <p>The link may be out of date, or the page may have moved. Let’s get you back to a clean start.</p>
            <div class="hero-actions">
                <a class="button button-primary" href="index.php">Go to homepage <span aria-hidden="true">↗</span></a>
                <a class="text-link" href="booking.php">Book a cleaning</a>
            </div>
        </div>
    </main>

    <footer class="not-found-footer page-shell">
        <p>© 2026 Bexxy Care and Clean</p>
        <a href="privacy.php">Privacy notice</a>
    </footer>
</body>
</html>
