<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? '$PUMPVILLE') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .title-font { font-family: 'Space Grotesk', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(16px); }
        .nav-link { transition: color 0.15s; }
        .nav-link:hover { color: #34d399; }
        .nav-link.active { color: #34d399; border-bottom: 2px solid #34d399; }
        .pixel-img { image-rendering: pixelated; image-rendering: crisp-edges; }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen">

<nav class="border-b border-white/10 bg-zinc-900/80 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-x-3">
            <div class="w-9 h-9 bg-emerald-500 rounded-xl flex items-center justify-center text-xl shadow-lg shadow-emerald-500/30">🐸</div>
            <span class="title-font text-xl font-semibold tracking-tight">$PUMPVILLE</span>
        </a>
        <div class="flex items-center gap-x-1">
            <?php
            $page = currentPage();
            $navLinks = [
                'index.php'      => 'Token',
                'guides.php'     => 'Guides',
                'posts.php'      => 'Posts',
                'industries.php' => 'Industries',
            ];
            foreach ($navLinks as $href => $label):
                $active = ($page === $href) ? 'active text-emerald-400' : 'text-zinc-400';
            ?>
                <a href="<?= $href ?>" class="nav-link <?= $active ?> px-4 py-2 rounded-lg text-sm font-medium hover:bg-white/5"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-6 py-8">

