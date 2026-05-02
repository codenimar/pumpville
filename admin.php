<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';
$action = $_GET['action'] ?? 'dashboard';

// ==================== LOGOUT ====================
if ($action === 'logout') {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ==================== LOGIN ====================
if (!isAdminLoggedIn()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
        if ($_POST['pin'] === ADMIN_PIN) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Incorrect PIN. Please try again.';
        }
    }
    // Show login page
    $pageTitle = 'Admin Login · $PUMPVILLE';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .title-font { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm px-6">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-emerald-500 rounded-2xl flex items-center justify-center text-4xl mx-auto mb-4 shadow-lg shadow-emerald-500/30">🐸</div>
            <h1 class="title-font text-2xl font-semibold">Admin Panel</h1>
            <p class="text-zinc-400 text-sm mt-1">Enter your PIN to continue</p>
        </div>
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-xl p-3 mb-4 text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <input type="password" name="pin" placeholder="Enter PIN"
                       class="w-full bg-zinc-800 border border-white/10 rounded-xl px-4 py-3 text-center text-2xl tracking-[0.5em] focus:outline-none focus:border-emerald-500 transition-colors"
                       autofocus autocomplete="off" maxlength="10">
            </div>
            <button type="submit"
                    class="w-full bg-emerald-500 hover:bg-emerald-400 rounded-xl py-3 font-semibold transition-colors">
                Login
            </button>
        </form>
        <p class="text-center mt-6 text-xs text-zinc-600"><a href="index.php" class="hover:text-zinc-400 transition-colors">← Back to site</a></p>
    </div>
</body>
</html>
    <?php
    exit;
}

// ==================== HANDLE SAVES ====================

// --- Save token info ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-token') {
    $token = loadJson('token.json');
    $token['title']       = trim($_POST['title'] ?? '');
    $token['description'] = trim($_POST['description'] ?? '');
    $links = $token['links'] ?? [];
    $links['twitter']     = trim($_POST['link_twitter'] ?? '');
    $links['discord']     = trim($_POST['link_discord'] ?? '');
    $links['solscan']     = trim($_POST['link_solscan'] ?? '');
    $links['dexscreener'] = trim($_POST['link_dexscreener'] ?? '');
    $token['links'] = $links;
    // highlights: rebuild from posted arrays
    $hIcons = $_POST['h_icon'] ?? [];
    $hTexts = $_POST['h_text'] ?? [];
    $highlights = [];
    foreach ($hIcons as $i => $icon) {
        $text = trim($hTexts[$i] ?? '');
        if ($text !== '') {
            $highlights[] = ['icon' => trim($icon), 'text' => $text];
        }
    }
    $token['highlights'] = $highlights;
    saveJson('token.json', $token);
    $success = 'Token info saved.';
    $action = 'token';
}

// --- Save guides ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-guides') {
    $ids   = $_POST['vid_id']    ?? [];
    $titles= $_POST['vid_title'] ?? [];
    $yids  = $_POST['vid_ytid']  ?? [];
    $descs = $_POST['vid_desc']  ?? [];
    $videos = [];
    foreach ($titles as $i => $title) {
        $title = trim($title);
        if ($title === '') continue;
        $videos[] = [
            'id'          => (int)($ids[$i] ?? ($i + 1)),
            'title'       => $title,
            'youtube_id'  => trim($yids[$i] ?? ''),
            'description' => trim($descs[$i] ?? ''),
        ];
    }
    saveJson('guides.json', ['videos' => $videos]);
    $success = 'Guides saved.';
    $action = 'guides';
}

// --- Save posts ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-posts') {
    $ids    = $_POST['post_id']   ?? [];
    $tids   = $_POST['tweet_id']  ?? [];
    $descs  = $_POST['post_desc'] ?? [];
    $urls   = $_POST['post_url']  ?? [];
    $posts = [];
    foreach ($tids as $i => $tid) {
        $desc = trim($descs[$i] ?? '');
        $tid  = trim($tid);
        $url  = trim($urls[$i] ?? '');
        if ($tid === '' && $desc === '') continue;
        $posts[] = [
            'id'          => (int)($ids[$i] ?? ($i + 1)),
            'tweet_id'    => $tid,
            'description' => $desc,
            'url'         => $url,
        ];
    }
    saveJson('posts.json', ['posts' => $posts]);
    $success = 'Posts saved.';
    $action = 'posts';
}

// --- Save industry (general info) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-industry') {
    $indId = $_POST['industry_id'] ?? '';
    $data = loadJson('industries.json');
    $industries = $data['industries'] ?? [];
    foreach ($industries as &$ind) {
        if ($ind['id'] === $indId) {
            $ind['name']        = trim($_POST['ind_name'] ?? $ind['name']);
            $ind['description'] = trim($_POST['ind_desc'] ?? '');
            $ind['icon']        = trim($_POST['ind_icon'] ?? '');
            // Handle image upload
            if (!empty($_FILES['ind_image']['name'])) {
                $uploaded = handleUpload('ind_image', 'industry_' . $indId);
                if ($uploaded) $ind['image'] = $uploaded;
            }
            break;
        }
    }
    unset($ind);
    saveJson('industries.json', ['industries' => $industries]);
    $success = 'Industry info saved.';
    $action = 'industry-' . $indId;
}

// --- Save fish ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-fish') {
    $indId = $_POST['industry_id'] ?? 'fishing';
    $data = loadJson('industries.json');
    $industries = $data['industries'] ?? [];

    $names   = $_POST['fish_name']  ?? [];
    $xps     = $_POST['fish_xp']    ?? [];
    $vals    = $_POST['fish_value'] ?? [];
    $baitsRaw= $_POST['fish_baits'] ?? [];
    $imgUrls = $_POST['fish_image'] ?? [];

    $newFish = [];
    foreach ($names as $i => $name) {
        $name = trim($name);
        if ($name === '') continue;
        $baits = array_filter(array_map('trim', explode(',', $baitsRaw[$i] ?? '')));
        $image = trim($imgUrls[$i] ?? '');
        // Handle individual fish image upload
        $uploadKey = 'fish_upload_' . $i;
        if (!empty($_FILES[$uploadKey]['name'])) {
            $uploaded = handleUpload($uploadKey, 'fish_' . strtolower(preg_replace('/\s+/', '_', $name)));
            if ($uploaded) $image = $uploaded;
        }
        $newFish[] = [
            'name'  => $name,
            'image' => $image,
            'xp'    => (int)($xps[$i] ?? 0),
            'value' => (int)($vals[$i] ?? 0),
            'baits' => array_values($baits),
        ];
    }

    foreach ($industries as &$ind) {
        if ($ind['id'] === $indId) {
            $ind['fish'] = $newFish;
            break;
        }
    }
    unset($ind);
    saveJson('industries.json', ['industries' => $industries]);
    $success = 'Fish data saved.';
    $action = 'industry-' . $indId;
}

// ==================== UPLOAD HANDLER ====================
function handleUpload($fileKey, $nameBase) {
    if (empty($_FILES[$fileKey]['tmp_name'])) return null;
    $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES[$fileKey]['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return null;
    $ext = ['image/png'=>'png','image/jpeg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp'][$mime];
    $filename = preg_replace('/[^a-z0-9_\-]/', '', strtolower($nameBase)) . '_' . time() . '.' . $ext;
    $dest = UPLOADS_DIR . $filename;
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
        return UPLOADS_URL . $filename;
    }
    return null;
}

// ==================== LOAD DATA FOR DISPLAY ====================
$token      = loadJson('token.json');
$guidesData = loadJson('guides.json');
$postsData  = loadJson('posts.json');
$indData    = loadJson('industries.json');
$industries = $indData['industries'] ?? [];

// Determine which sub-page
$subpage = $action;
$editingIndustryId = null;
if (strpos($action, 'industry-') === 0) {
    $editingIndustryId = substr($action, strlen('industry-'));
    $subpage = 'industry';
}

$pageTitle = 'Admin · $PUMPVILLE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .title-font { font-family: 'Space Grotesk', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(16px); }
        .input { background: #27272a; border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 0.625rem 0.875rem; width: 100%; color: #fff; font-size: 0.875rem; transition: border-color 0.15s; }
        .input:focus { outline: none; border-color: #34d399; }
        .label { font-size: 0.75rem; font-weight: 500; color: #71717a; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 0.375rem; display: block; }
        .btn-primary { background: #10b981; color: #fff; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-weight: 600; font-size: 0.875rem; transition: background 0.15s; cursor: pointer; }
        .btn-primary:hover { background: #34d399; }
        .btn-danger { background: #ef4444; color: #fff; padding: 0.4rem 0.875rem; border-radius: 0.625rem; font-size: 0.75rem; font-weight: 600; transition: background 0.15s; cursor: pointer; }
        .btn-danger:hover { background: #f87171; }
        .sidebar-link { display: flex; align-items: center; gap: 0.625rem; padding: 0.625rem 0.875rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 500; color: #a1a1aa; transition: all 0.15s; text-decoration: none; }
        .sidebar-link:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar-link.active { background: rgba(16,185,129,0.15); color: #34d399; }
        .pixel-img { image-rendering: pixelated; image-rendering: crisp-edges; }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen flex">

<!-- Sidebar -->
<aside class="w-56 bg-zinc-900 border-r border-white/10 flex flex-col min-h-screen fixed top-0 left-0 z-40">
    <div class="p-5 border-b border-white/10">
        <a href="index.php" class="flex items-center gap-x-2">
            <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center text-lg">🐸</div>
            <span class="title-font font-semibold text-sm">$PUMPVILLE</span>
        </a>
        <p class="text-xs text-zinc-500 mt-1 ml-10">Admin Panel</p>
    </div>
    <nav class="flex-1 p-3 space-y-1">
        <a href="admin.php?action=dashboard" class="sidebar-link <?= ($subpage === 'dashboard') ? 'active' : '' ?>">
            <span>🏠</span> Dashboard
        </a>
        <a href="admin.php?action=token" class="sidebar-link <?= ($subpage === 'token') ? 'active' : '' ?>">
            <span>🐸</span> Token Info
        </a>
        <a href="admin.php?action=guides" class="sidebar-link <?= ($subpage === 'guides') ? 'active' : '' ?>">
            <span>🎬</span> Guides
        </a>
        <a href="admin.php?action=posts" class="sidebar-link <?= ($subpage === 'posts') ? 'active' : '' ?>">
            <span>𝕏</span> Posts
        </a>
        <a href="admin.php?action=industries" class="sidebar-link <?= ($subpage === 'industries') ? 'active' : '' ?>">
            <span>🏭</span> Industries
        </a>
        <hr class="border-white/10 my-2">
        <?php foreach ($industries as $ind): ?>
            <a href="admin.php?action=industry-<?= urlencode($ind['id']) ?>"
               class="sidebar-link pl-8 text-xs <?= ($editingIndustryId === $ind['id']) ? 'active' : '' ?>">
                <?= htmlspecialchars($ind['icon'] ?? '') ?> <?= htmlspecialchars($ind['name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-3 border-t border-white/10">
        <a href="admin.php?action=logout" class="sidebar-link text-red-400 hover:text-red-300 hover:bg-red-500/10">
            <span>🚪</span> Logout
        </a>
    </div>
</aside>

<!-- Main content -->
<div class="ml-56 flex-1 min-h-screen">
    <header class="bg-zinc-900/80 border-b border-white/10 px-8 py-4 flex items-center justify-between sticky top-0 z-30 backdrop-blur">
        <h1 class="title-font font-semibold text-lg">
            <?php
            $titles = [
                'dashboard'  => 'Dashboard',
                'token'      => 'Edit Token Info',
                'guides'     => 'Edit Guides',
                'posts'      => 'Edit Posts',
                'industries' => 'Industries',
                'industry'   => 'Edit Industry: ' . ucfirst($editingIndustryId ?? ''),
            ];
            echo $titles[$subpage] ?? 'Admin';
            ?>
        </h1>
        <a href="index.php" target="_blank" class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors">← View Site</a>
    </header>

    <main class="p-8">
        <?php if ($success): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-xl p-3 mb-6">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php

        // ==================== DASHBOARD ====================
        if ($subpage === 'dashboard'):
        ?>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <?php
            $cards = [
                ['🐸', 'Token Info',   'token',      'emerald'],
                ['🎬', 'Guides',       'guides',     'red'],
                ['𝕏',  'Posts',        'posts',      'sky'],
                ['🏭', 'Industries',   'industries', 'purple'],
            ];
            $colorMap = ['emerald'=>'border-emerald-500/30 hover:bg-emerald-500/10','red'=>'border-red-500/30 hover:bg-red-500/10','sky'=>'border-sky-500/30 hover:bg-sky-500/10','purple'=>'border-purple-500/30 hover:bg-purple-500/10'];
            foreach ($cards as [$icon,$label,$act,$color]):
            ?>
            <a href="admin.php?action=<?= $act ?>"
               class="glass border <?= $colorMap[$color] ?> rounded-2xl p-5 flex flex-col gap-y-2 transition-all">
                <span class="text-3xl"><?= $icon ?></span>
                <span class="font-semibold"><?= $label ?></span>
                <span class="text-xs text-zinc-500">Edit →</span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="glass border border-white/10 rounded-2xl p-6">
            <h2 class="font-semibold mb-4">Quick Status</h2>
            <div class="space-y-2 text-sm text-zinc-400">
                <div class="flex justify-between"><span>Guides</span><span class="text-white"><?= count($guidesData['videos'] ?? []) ?> video(s)</span></div>
                <div class="flex justify-between"><span>Posts</span><span class="text-white"><?= count($postsData['posts'] ?? []) ?> post(s)</span></div>
                <div class="flex justify-between"><span>Industries</span><span class="text-white"><?= count($industries) ?></span></div>
                <?php foreach ($industries as $ind):
                    $fishCount = count($ind['fish'] ?? $ind['crops'] ?? $ind['resources'] ?? []);
                ?>
                <div class="flex justify-between pl-4"><span><?= htmlspecialchars($ind['name']) ?></span><span class="text-white"><?= $fishCount ?> item(s)</span></div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php

        // ==================== TOKEN ====================
        elseif ($subpage === 'token'):
        ?>
        <form method="POST" action="admin.php?action=save-token">
            <div class="space-y-6 max-w-2xl">
                <div>
                    <label class="label">Page Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($token['title'] ?? '') ?>" class="input">
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea name="description" rows="4" class="input resize-none"><?= htmlspecialchars($token['description'] ?? '') ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Twitter URL</label>
                        <input type="url" name="link_twitter" value="<?= htmlspecialchars($token['links']['twitter'] ?? '') ?>" class="input">
                    </div>
                    <div>
                        <label class="label">Discord URL</label>
                        <input type="url" name="link_discord" value="<?= htmlspecialchars($token['links']['discord'] ?? '') ?>" class="input">
                    </div>
                    <div>
                        <label class="label">Solscan URL</label>
                        <input type="url" name="link_solscan" value="<?= htmlspecialchars($token['links']['solscan'] ?? '') ?>" class="input">
                    </div>
                    <div>
                        <label class="label">Dexscreener URL</label>
                        <input type="url" name="link_dexscreener" value="<?= htmlspecialchars($token['links']['dexscreener'] ?? '') ?>" class="input">
                    </div>
                </div>

                <div>
                    <label class="label">Highlights</label>
                    <div id="highlights-list" class="space-y-2 mb-3">
                        <?php foreach (($token['highlights'] ?? []) as $h): ?>
                        <div class="flex gap-x-2 highlight-row">
                            <input type="text" name="h_icon[]" value="<?= htmlspecialchars($h['icon'] ?? '') ?>" placeholder="🐸" class="input w-16 text-center">
                            <input type="text" name="h_text[]" value="<?= htmlspecialchars($h['text'] ?? '') ?>" placeholder="Highlight text" class="input flex-1">
                            <button type="button" onclick="this.closest('.highlight-row').remove()" class="btn-danger">✕</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addHighlight()" class="text-sm text-emerald-400 hover:underline">+ Add highlight</button>
                </div>

                <button type="submit" class="btn-primary">Save Token Info</button>
            </div>
        </form>
        <script>
        function addHighlight() {
            const list = document.getElementById('highlights-list');
            const row = document.createElement('div');
            row.className = 'flex gap-x-2 highlight-row';
            row.innerHTML = `<input type="text" name="h_icon[]" placeholder="🐸" class="input w-16 text-center" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;color:#fff;font-size:0.875rem;">
                <input type="text" name="h_text[]" placeholder="Highlight text" class="input flex-1" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;color:#fff;font-size:0.875rem;">
                <button type="button" onclick="this.closest('.highlight-row').remove()" style="background:#ef4444;color:#fff;padding:0.4rem 0.875rem;border-radius:0.625rem;font-size:0.75rem;font-weight:600;cursor:pointer;">✕</button>`;
            list.appendChild(row);
        }
        </script>

        <?php

        // ==================== GUIDES ====================
        elseif ($subpage === 'guides'):
        $videos = $guidesData['videos'] ?? [];
        ?>
        <form method="POST" action="admin.php?action=save-guides">
            <div id="videos-list" class="space-y-4 mb-6">
                <?php foreach ($videos as $v): ?>
                <div class="glass border border-white/10 rounded-2xl p-5 video-row">
                    <input type="hidden" name="vid_id[]" value="<?= (int)($v['id'] ?? 0) ?>">
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="label">Title</label>
                            <input type="text" name="vid_title[]" value="<?= htmlspecialchars($v['title'] ?? '') ?>" class="input" placeholder="Guide title">
                        </div>
                        <div>
                            <label class="label">YouTube Video ID</label>
                            <input type="text" name="vid_ytid[]" value="<?= htmlspecialchars($v['youtube_id'] ?? '') ?>" class="input" placeholder="dQw4w9WgXcQ">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="label">Description</label>
                        <textarea name="vid_desc[]" rows="2" class="input resize-none" placeholder="Short description..."><?= htmlspecialchars($v['description'] ?? '') ?></textarea>
                    </div>
                    <button type="button" onclick="this.closest('.video-row').remove()" class="btn-danger">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-x-3">
                <button type="button" onclick="addVideo()" class="text-sm text-emerald-400 hover:underline">+ Add video</button>
                <button type="submit" class="btn-primary ml-auto">Save Guides</button>
            </div>
        </form>
        <script>
        function addVideo() {
            const list = document.getElementById('videos-list');
            const div = document.createElement('div');
            div.className = 'glass border border-white/10 rounded-2xl p-5 video-row';
            div.style.cssText = 'background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:1rem;padding:1.25rem;';
            div.innerHTML = `<input type="hidden" name="vid_id[]" value="0">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:0.75rem;">
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.375rem;display:block;">Title</label>
                    <input type="text" name="vid_title[]" placeholder="Guide title" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.375rem;display:block;">YouTube Video ID</label>
                    <input type="text" name="vid_ytid[]" placeholder="dQw4w9WgXcQ" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                </div>
                <div style="margin-bottom:0.75rem;"><label style="font-size:0.75rem;font-weight:500;color:#71717a;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.375rem;display:block;">Description</label>
                <textarea name="vid_desc[]" rows="2" placeholder="Short description..." style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;resize:none;"></textarea></div>
                <button type="button" onclick="this.closest('.video-row').remove()" style="background:#ef4444;color:#fff;padding:0.4rem 0.875rem;border-radius:0.625rem;font-size:0.75rem;font-weight:600;cursor:pointer;">Remove</button>`;
            list.appendChild(div);
        }
        </script>

        <?php

        // ==================== POSTS ====================
        elseif ($subpage === 'posts'):
        $posts = $postsData['posts'] ?? [];
        ?>
        <form method="POST" action="admin.php?action=save-posts">
            <div id="posts-list" class="space-y-4 mb-6">
                <?php foreach ($posts as $p): ?>
                <div class="glass border border-white/10 rounded-2xl p-5 post-row">
                    <input type="hidden" name="post_id[]" value="<?= (int)($p['id'] ?? 0) ?>">
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="label">Tweet ID (from URL)</label>
                            <input type="text" name="tweet_id[]" value="<?= htmlspecialchars($p['tweet_id'] ?? '') ?>" class="input" placeholder="1234567890123456789">
                        </div>
                        <div>
                            <label class="label">Post URL</label>
                            <input type="url" name="post_url[]" value="<?= htmlspecialchars($p['url'] ?? '') ?>" class="input" placeholder="https://x.com/...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="label">Description / Caption</label>
                        <textarea name="post_desc[]" rows="2" class="input resize-none" placeholder="Post text or caption..."><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                    </div>
                    <button type="button" onclick="this.closest('.post-row').remove()" class="btn-danger">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-x-3">
                <button type="button" onclick="addPost()" class="text-sm text-emerald-400 hover:underline">+ Add post</button>
                <button type="submit" class="btn-primary ml-auto">Save Posts</button>
            </div>
        </form>
        <p class="text-xs text-zinc-500 mt-4">Get the Tweet ID from the tweet URL: <code>https://x.com/username/status/<strong>1234567890</strong></code></p>
        <script>
        function addPost() {
            const list = document.getElementById('posts-list');
            const div = document.createElement('div');
            div.className = 'post-row';
            div.style.cssText = 'background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:1rem;padding:1.25rem;margin-bottom:1rem;';
            div.innerHTML = `<input type="hidden" name="post_id[]" value="0">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:0.75rem;">
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.375rem;display:block;">Tweet ID</label>
                    <input type="text" name="tweet_id[]" placeholder="1234567890123456789" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.375rem;display:block;">Post URL</label>
                    <input type="url" name="post_url[]" placeholder="https://x.com/..." style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                </div>
                <div style="margin-bottom:0.75rem;"><label style="font-size:0.75rem;font-weight:500;color:#71717a;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.375rem;display:block;">Description</label>
                <textarea name="post_desc[]" rows="2" placeholder="Post text..." style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;resize:none;"></textarea></div>
                <button type="button" onclick="this.closest('.post-row').remove()" style="background:#ef4444;color:#fff;padding:0.4rem 0.875rem;border-radius:0.625rem;font-size:0.75rem;font-weight:600;cursor:pointer;">Remove</button>`;
            list.appendChild(div);
        }
        </script>

        <?php

        // ==================== INDUSTRIES LIST ====================
        elseif ($subpage === 'industries'):
        ?>
        <div class="space-y-3">
            <?php foreach ($industries as $ind): ?>
            <div class="glass border border-white/10 rounded-2xl p-5 flex items-center gap-x-4">
                <span class="text-3xl"><?= htmlspecialchars($ind['icon'] ?? '') ?></span>
                <div class="flex-1">
                    <p class="font-semibold"><?= htmlspecialchars($ind['name']) ?></p>
                    <p class="text-zinc-400 text-sm"><?= htmlspecialchars(mb_substr($ind['description'] ?? '', 0, 80)) ?>…</p>
                </div>
                <a href="admin.php?action=industry-<?= urlencode($ind['id']) ?>" class="btn-primary">Edit</a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php

        // ==================== SINGLE INDUSTRY EDIT ====================
        elseif ($subpage === 'industry' && $editingIndustryId):
        $editInd = null;
        foreach ($industries as $ind) {
            if ($ind['id'] === $editingIndustryId) { $editInd = $ind; break; }
        }
        if (!$editInd): ?>
            <p class="text-red-400">Industry not found.</p>
        <?php else: ?>

        <!-- Industry general info form -->
        <form method="POST" action="admin.php?action=save-industry" enctype="multipart/form-data" class="mb-10">
            <input type="hidden" name="industry_id" value="<?= htmlspecialchars($editInd['id']) ?>">
            <div class="space-y-4 max-w-2xl">
                <h2 class="font-semibold text-lg">Industry Info</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Name</label>
                        <input type="text" name="ind_name" value="<?= htmlspecialchars($editInd['name'] ?? '') ?>" class="input">
                    </div>
                    <div>
                        <label class="label">Icon (emoji)</label>
                        <input type="text" name="ind_icon" value="<?= htmlspecialchars($editInd['icon'] ?? '') ?>" class="input">
                    </div>
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea name="ind_desc" rows="3" class="input resize-none"><?= htmlspecialchars($editInd['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="label">Industry Image</label>
                    <?php if (!empty($editInd['image'])): ?>
                        <img src="../<?= htmlspecialchars($editInd['image']) ?>" class="w-16 h-16 pixel-img mb-2 rounded-lg">
                    <?php endif; ?>
                    <input type="file" name="ind_image" accept="image/*" class="input">
                </div>
                <button type="submit" class="btn-primary">Save Industry Info</button>
            </div>
        </form>

        <?php if ($editInd['id'] === 'fishing'): ?>

        <!-- Fish editor -->
        <div class="border-t border-white/10 pt-8">
            <h2 class="font-semibold text-lg mb-6">Fish List (<?= count($editInd['fish'] ?? []) ?> fish)</h2>
            <form method="POST" action="admin.php?action=save-fish" enctype="multipart/form-data">
                <input type="hidden" name="industry_id" value="fishing">
                <div id="fish-list" class="space-y-4 mb-6">
                    <?php foreach (($editInd['fish'] ?? []) as $fi => $fish): ?>
                    <div class="glass border border-white/10 rounded-2xl p-5 fish-row">
                        <div class="grid grid-cols-4 gap-4 mb-3">
                            <div class="col-span-2">
                                <label class="label">Fish Name</label>
                                <input type="text" name="fish_name[]" value="<?= htmlspecialchars($fish['name'] ?? '') ?>" class="input" placeholder="e.g. Bass">
                            </div>
                            <div>
                                <label class="label">XP Reward</label>
                                <input type="number" name="fish_xp[]" value="<?= (int)($fish['xp'] ?? 0) ?>" class="input" min="0">
                            </div>
                            <div>
                                <label class="label">Value (coins)</label>
                                <input type="number" name="fish_value[]" value="<?= (int)($fish['value'] ?? 0) ?>" class="input" min="0">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <label class="label">Baits (comma-separated)</label>
                                <input type="text" name="fish_baits[]" value="<?= htmlspecialchars(implode(', ', $fish['baits'] ?? [])) ?>" class="input" placeholder="Worm, Minnow">
                            </div>
                            <div>
                                <label class="label">Image</label>
                                <div class="flex items-center gap-x-3">
                                    <?php if (!empty($fish['image'])): ?>
                                        <img src="../<?= htmlspecialchars($fish['image']) ?>" class="w-10 h-10 pixel-img rounded">
                                    <?php endif; ?>
                                    <input type="hidden" name="fish_image[]" value="<?= htmlspecialchars($fish['image'] ?? '') ?>">
                                    <input type="file" name="fish_upload_<?= $fi ?>" accept="image/*" class="input text-xs">
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('.fish-row').remove()" class="btn-danger">Remove Fish</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-x-3">
                    <button type="button" onclick="addFish()" class="text-sm text-emerald-400 hover:underline">+ Add fish</button>
                    <button type="submit" class="btn-primary ml-auto">Save Fish Data</button>
                </div>
            </form>
        </div>
        <script>
        let fishCount = <?= count($editInd['fish'] ?? []) ?>;
        function addFish() {
            const list = document.getElementById('fish-list');
            const idx = fishCount++;
            const div = document.createElement('div');
            div.className = 'fish-row';
            div.style.cssText = 'background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:1rem;padding:1.25rem;';
            div.innerHTML = `
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:1rem;margin-bottom:0.75rem;">
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.375rem;display:block;">Fish Name</label>
                    <input type="text" name="fish_name[]" placeholder="e.g. Tuna" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.375rem;display:block;">XP</label>
                    <input type="number" name="fish_xp[]" value="0" min="0" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.375rem;display:block;">Value (coins)</label>
                    <input type="number" name="fish_value[]" value="0" min="0" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:0.75rem;">
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.375rem;display:block;">Baits (comma-separated)</label>
                    <input type="text" name="fish_baits[]" placeholder="Worm, Minnow" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                    <div><label style="font-size:0.75rem;font-weight:500;color:#71717a;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.375rem;display:block;">Image Upload</label>
                    <input type="hidden" name="fish_image[]" value="">
                    <input type="file" name="fish_upload_${idx}" accept="image/*" style="background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;width:100%;color:#fff;font-size:0.875rem;"></div>
                </div>
                <button type="button" onclick="this.closest('.fish-row').remove()" style="background:#ef4444;color:#fff;padding:0.4rem 0.875rem;border-radius:0.625rem;font-size:0.75rem;font-weight:600;cursor:pointer;">Remove Fish</button>`;
            list.appendChild(div);
        }
        </script>

        <?php else: ?>
        <p class="text-zinc-400 text-sm mt-4">Detailed content editor coming soon for this industry.</p>
        <?php endif; ?>

        <?php endif; // $editInd exists ?>

        <?php endif; // subpage switch ?>

    </main>
</div>

</body>
</html>
