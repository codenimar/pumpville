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
        // Basic brute-force protection: max 5 attempts per session
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] > 5) {
            $error = 'Too many failed attempts. Please restart your browser session.';
        } elseif ($_POST['pin'] === ADMIN_PIN) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['login_attempts'] = 0;
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

// --- Add new industry ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-add-industry') {
    $indData2   = loadJson('industries.json');
    $industries2 = $indData2['industries'] ?? [];
    $name = trim($_POST['ind_name'] ?? '');
    $icon = trim($_POST['ind_icon'] ?? '🏭');
    $desc = trim($_POST['ind_desc'] ?? '');
    if ($name !== '') {
        $id = preg_replace('/[^a-z0-9_\-]/', '', strtolower(str_replace(' ', '_', $name)));
        if ($id === '') $id = 'ind_' . time();
        if (in_array($id, array_column($industries2, 'id'))) $id .= '_' . time();
        $image = '';
        if (!empty($_FILES['ind_image']['name'])) {
            $up = handleUpload('ind_image', 'industry_' . $id);
            if ($up) $image = $up;
        }
        $industries2[] = [
            'id' => $id, 'name' => $name, 'icon' => $icon,
            'description' => $desc, 'image' => $image,
            'fields' => [['key' => 'rarity', 'label' => 'Rarity', 'type' => 'text']],
            'items'  => [],
        ];
        saveJson('industries.json', ['industries' => $industries2]);
        $success = 'Industry "' . htmlspecialchars($name) . '" added.';
    }
    $action = 'industries';
}

// --- Delete industry ---
if ($action === 'delete-industry' && isset($_GET['id'])) {
    $delId = preg_replace('/[^a-z0-9_\-]/', '', $_GET['id'] ?? '');
    if ($delId !== '') {
        $indData2 = loadJson('industries.json');
        $filtered = array_values(array_filter($indData2['industries'] ?? [], fn($x) => $x['id'] !== $delId));
        saveJson('industries.json', ['industries' => $filtered]);
        $success = 'Industry deleted.';
    }
    $action = 'industries';
}

// --- Save industry info ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-industry') {
    $indId = $_POST['industry_id'] ?? '';
    $data = loadJson('industries.json');
    $industries = $data['industries'] ?? [];
    foreach ($industries as &$ind) {
        if ($ind['id'] === $indId) {
            $ind['name']        = trim($_POST['ind_name'] ?? $ind['name']);
            $ind['description'] = trim($_POST['ind_desc'] ?? '');
            $ind['icon']        = trim($_POST['ind_icon'] ?? '');
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

// --- Save industry fields ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-industry-fields') {
    $indId   = $_POST['industry_id'] ?? '';
    $data    = loadJson('industries.json');
    $inds    = $data['industries'] ?? [];
    $fKeys   = $_POST['field_key']   ?? [];
    $fLabels = $_POST['field_label'] ?? [];
    $fTypes  = $_POST['field_type']  ?? [];
    $fields  = [];
    foreach ($fKeys as $i => $key) {
        $key   = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($key)));
        $label = trim($fLabels[$i] ?? '');
        $type  = in_array($fTypes[$i] ?? '', ['text', 'number']) ? $fTypes[$i] : 'text';
        if ($key !== '' && $label !== '') $fields[] = ['key' => $key, 'label' => $label, 'type' => $type];
    }
    foreach ($inds as &$ind) {
        if ($ind['id'] === $indId) { $ind['fields'] = $fields; break; }
    }
    unset($ind);
    saveJson('industries.json', ['industries' => $inds]);
    $success = 'Fields saved.';
    $action = 'industry-' . $indId;
}

// --- Save industry items ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-industry-items') {
    $indId   = $_POST['industry_id'] ?? '';
    $data    = loadJson('industries.json');
    $inds    = $data['industries'] ?? [];
    $names   = $_POST['item_name'] ?? [];
    $imgUrls = $_POST['item_img']  ?? [];
    $idxes   = $_POST['item_idx']  ?? [];
    $fieldKeys = [];
    foreach ($inds as $ind) {
        if ($ind['id'] === $indId) { $fieldKeys = array_column($ind['fields'] ?? [], 'key'); break; }
    }
    $items = [];
    foreach ($names as $pos => $name) {
        $name = trim($name);
        if ($name === '') continue;
        $idx   = $idxes[$pos] ?? $pos;
        $image = trim($imgUrls[$pos] ?? '');
        $uploadKey = 'item_upload_' . $idx;
        if (!empty($_FILES[$uploadKey]['name'])) {
            $safe = preg_replace('/[^a-z0-9_\-]/', '', strtolower($name)) ?: 'item';
            $up = handleUpload($uploadKey, $indId . '_' . $safe);
            if ($up) $image = $up;
        }
        $item = ['id' => 'item_' . uniqid(), 'name' => $name, 'image' => $image];
        foreach ($fieldKeys as $fk) {
            $vals  = $_POST['fv_' . $fk] ?? [];
            $raw   = trim($vals[$pos] ?? '');
            // Find field type for this key to cast numbers properly
            $item[$fk] = $raw;
        }
        $items[] = $item;
    }
    foreach ($inds as &$ind) {
        if ($ind['id'] === $indId) { $ind['items'] = $items; break; }
    }
    unset($ind);
    saveJson('industries.json', ['industries' => $inds]);
    $success = 'Items saved.';
    $action = 'industry-' . $indId;
}

// ==================== UPLOAD HANDLER (FINAL WORKING VERSION) ====================
function handleUpload($fileKey, $nameBase) {
    $err = $_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) return null;
    if ($err !== UPLOAD_ERR_OK) return null;
    if (empty($_FILES[$fileKey]['tmp_name'])) return null;

    // File size limit: 2MB
    if ($_FILES[$fileKey]['size'] > 2 * 1024 * 1024) return null;

    $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES[$fileKey]['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return null;

    $ext = ['image/png'=>'png','image/jpeg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp'][$mime];

    // Sanitize base name
    $safeName = preg_replace('/[^a-z0-9_\-]/', '', strtolower($nameBase));
    if ($safeName === '') $safeName = 'upload';
    $filename = $safeName . '_' . time() . '.' . $ext;

    // Ensure directory exists + writable
    if (!is_dir(UPLOADS_DIR)) {
        if (!mkdir(UPLOADS_DIR, 0755, true)) {
            return null;
        }
    }
    if (!is_writable(UPLOADS_DIR)) {
        return null;
    }

    $dest = UPLOADS_DIR . $filename;

    // NO realpath check → works on Hostinger/shared hosting
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
        return UPLOADS_URL . $filename;
    }
    return null;
}
    // ============================================================

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
                    $itemCount = count($ind['items'] ?? []);
                ?>
                <div class="flex justify-between pl-4"><span><?= htmlspecialchars($ind['name']) ?></span><span class="text-white"><?= $itemCount ?> item(s)</span></div>
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
        <div class="space-y-3 mb-8">
            <?php foreach ($industries as $ind): ?>
            <div class="glass border border-white/10 rounded-2xl p-5 flex items-center gap-x-4">
                <?php if (!empty($ind['image'])): ?>
                    <img src="<?= htmlspecialchars($ind['image']) ?>" class="w-12 h-12 object-contain rounded-xl bg-zinc-800 p-1 pixel-img flex-shrink-0">
                <?php else: ?>
                    <span class="text-3xl w-12 text-center flex-shrink-0"><?= htmlspecialchars($ind['icon'] ?? '🏭') ?></span>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold"><?= htmlspecialchars($ind['name']) ?></p>
                    <p class="text-zinc-500 text-xs"><?= count($ind['items'] ?? []) ?> item(s) · <?= count($ind['fields'] ?? []) ?> field(s)</p>
                </div>
                <a href="admin.php?action=industry-<?= urlencode($ind['id']) ?>" class="btn-primary text-xs px-3 py-2">Edit</a>
                <a href="admin.php?action=delete-industry&id=<?= urlencode($ind['id']) ?>"
                   onclick="return confirm('Delete <?= addslashes(htmlspecialchars($ind['name'])) ?>? This cannot be undone.')"
                   class="btn-danger text-xs px-3 py-2">Delete</a>
            </div>
            <?php endforeach; ?>
            <?php if (empty($industries)): ?>
            <div class="glass border border-white/10 rounded-2xl p-12 text-center text-zinc-400">
                <div class="text-4xl mb-3">🏭</div>
                <p>No industries yet. Add one below.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Add new industry -->
        <div class="glass border border-emerald-500/20 rounded-2xl p-6">
            <h2 class="font-semibold text-lg mb-5">Add New Industry</h2>
            <form method="POST" action="admin.php?action=save-add-industry" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Name</label>
                        <input type="text" name="ind_name" class="input" placeholder="e.g. Fishing" required>
                    </div>
                    <div>
                        <label class="label">Icon (emoji)</label>
                        <input type="text" name="ind_icon" class="input" placeholder="🏭" value="🏭">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="label">Description</label>
                    <textarea name="ind_desc" rows="2" class="input resize-none" placeholder="Short description..."></textarea>
                </div>
                <div class="mb-5">
                    <label class="label">Image (optional)</label>
                    <input type="file" name="ind_image" accept="image/*" class="input">
                </div>
                <button type="submit" class="btn-primary">Add Industry</button>
            </form>
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
        <?php else:
        $editFields = $editInd['fields'] ?? [];
        $editItems  = $editInd['items']  ?? [];
        ?>

        <!-- SECTION 1: Industry Info -->
        <div class="glass border border-white/10 rounded-2xl p-6 mb-6">
            <h2 class="font-semibold text-lg mb-5">Industry Info</h2>
            <form method="POST" action="admin.php?action=save-industry" enctype="multipart/form-data">
                <input type="hidden" name="industry_id" value="<?= htmlspecialchars($editInd['id']) ?>">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Name</label>
                        <input type="text" name="ind_name" value="<?= htmlspecialchars($editInd['name'] ?? '') ?>" class="input">
                    </div>
                    <div>
                        <label class="label">Icon (emoji)</label>
                        <input type="text" name="ind_icon" value="<?= htmlspecialchars($editInd['icon'] ?? '') ?>" class="input">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="label">Description</label>
                    <textarea name="ind_desc" rows="3" class="input resize-none"><?= htmlspecialchars($editInd['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-5">
                    <label class="label">Industry Image</label>
                    <?php if (!empty($editInd['image'])): ?>
                        <img src="<?= htmlspecialchars($editInd['image']) ?>" class="w-16 h-16 object-contain pixel-img mb-2 rounded-lg bg-zinc-800 p-1">
                    <?php endif; ?>
                    <input type="file" name="ind_image" accept="image/*" class="input">
                </div>
                <button type="submit" class="btn-primary">Save Info</button>
            </form>
        </div>

        <!-- SECTION 2: Custom Fields -->
        <div class="glass border border-white/10 rounded-2xl p-6 mb-6">
            <h2 class="font-semibold text-lg mb-5">Custom Fields</h2>
            <form method="POST" action="admin.php?action=save-industry-fields">
                <input type="hidden" name="industry_id" value="<?= htmlspecialchars($editInd['id']) ?>">
                <div class="grid grid-cols-3 gap-2 mb-2 px-1">
                    <span class="label mb-0">Key</span>
                    <span class="label mb-0">Label</span>
                    <span class="label mb-0">Type</span>
                </div>
                <div id="fields-list" class="space-y-2 mb-4">
                    <?php foreach ($editFields as $f): ?>
                    <div class="flex gap-x-2 field-row items-center">
                        <input type="text" name="field_key[]" value="<?= htmlspecialchars($f['key']) ?>"
                               placeholder="key" class="input w-36 font-mono text-xs" readonly
                               title="Key cannot be changed after creation (would orphan item data)">
                        <input type="text" name="field_label[]" value="<?= htmlspecialchars($f['label']) ?>"
                               placeholder="Label" class="input flex-1">
                        <select name="field_type[]" class="input w-28">
                            <option value="text"   <?= ($f['type'] === 'text')   ? 'selected' : '' ?>>Text</option>
                            <option value="number" <?= ($f['type'] === 'number') ? 'selected' : '' ?>>Number</option>
                        </select>
                        <button type="button" onclick="this.closest('.field-row').remove()" class="btn-danger">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex items-center gap-x-3 mb-4">
                    <button type="button" onclick="addField()" class="text-sm text-emerald-400 hover:underline">+ Add field</button>
                </div>
                <p class="text-xs text-zinc-500 mb-4">⚠️ Field keys cannot be changed after creation. Removing a field hides it from the display but does not delete data from existing items.</p>
                <button type="submit" class="btn-primary">Save Fields</button>
            </form>
        </div>

        <!-- SECTION 3: Items -->
        <div class="glass border border-white/10 rounded-2xl p-6">
            <h2 class="font-semibold text-lg mb-5">
                Items
                <span class="text-zinc-500 font-normal text-sm ml-2">(<?= count($editItems) ?>)</span>
            </h2>
            <form method="POST" action="admin.php?action=save-industry-items" enctype="multipart/form-data">
                <input type="hidden" name="industry_id" value="<?= htmlspecialchars($editInd['id']) ?>">
                <?php if (!empty($editFields)): ?>
                <div class="hidden lg:flex gap-2 mb-2 px-3 text-xs font-medium text-zinc-500 uppercase tracking-wider">
                    <span style="min-width:120px;flex:2;">Name</span>
                    <?php foreach ($editFields as $f): ?>
                        <span style="min-width:80px;flex:1;"><?= htmlspecialchars($f['label']) ?></span>
                    <?php endforeach; ?>
                    <span style="min-width:120px;flex:1.5;">Upload Image</span>
                    <span class="w-8"></span>
                </div>
                <?php endif; ?>
                <div id="items-list" class="space-y-2 mb-5">
                    <?php foreach ($editItems as $ii => $item): ?>
                    <div class="item-row flex flex-wrap gap-2 items-center bg-zinc-800/50 border border-white/5 rounded-xl p-3">
                        <input type="hidden" name="item_idx[]" value="<?= $ii ?>">
                        <input type="text" name="item_name[]" value="<?= htmlspecialchars($item['name'] ?? '') ?>"
                               placeholder="Name" class="input" style="min-width:120px;flex:2;">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?= htmlspecialchars($item['image']) ?>"
                                 class="w-8 h-8 object-contain rounded pixel-img bg-zinc-700 p-0.5 flex-shrink-0">
                        <?php endif; ?>
                        <input type="hidden" name="item_img[]" value="<?= htmlspecialchars($item['image'] ?? '') ?>">
                        <?php foreach ($editFields as $f): ?>
                            <input type="<?= $f['type'] === 'number' ? 'number' : 'text' ?>"
                                   name="fv_<?= htmlspecialchars($f['key']) ?>[]"
                                   value="<?= htmlspecialchars($item[$f['key']] ?? '') ?>"
                                   placeholder="<?= htmlspecialchars($f['label']) ?>"
                                   class="input"
                                   style="min-width:80px;flex:1;"
                                   <?= $f['type'] === 'number' ? 'min="0"' : '' ?>>
                        <?php endforeach; ?>
                        <input type="file" name="item_upload_<?= $ii ?>" accept="image/*" class="input text-xs" style="min-width:120px;flex:1.5;">
                        <button type="button" onclick="this.closest('.item-row').remove()" class="btn-danger flex-shrink-0">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-x-3">
                    <button type="button" onclick="addItem()" class="text-sm text-emerald-400 hover:underline">+ Add item</button>
                    <button type="submit" class="btn-primary ml-auto">Save Items</button>
                </div>
            </form>
        </div>

        <script>
        // ── Fields ──
        function addField() {
            const list = document.getElementById('fields-list');
            const row = document.createElement('div');
            row.className = 'flex gap-x-2 field-row items-center';
            const s = 'background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;color:#fff;font-size:0.875rem;';
            row.innerHTML = `
                <input type="text" name="field_key[]" placeholder="key (e.g. rarity)"
                       style="${s}width:9rem;font-family:monospace;font-size:0.75rem;">
                <input type="text" name="field_label[]" placeholder="Label"
                       style="${s}flex:1;">
                <select name="field_type[]" style="${s}min-width:7rem;">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                </select>
                <button type="button" onclick="this.closest('.field-row').remove()"
                        style="background:#ef4444;color:#fff;padding:0.4rem 0.875rem;border-radius:0.625rem;font-size:0.75rem;font-weight:600;cursor:pointer;">✕</button>`;
            list.appendChild(row);
            row.querySelector('input[name="field_key[]"]').focus();
        }

        // ── Items ──
        let itemCounter = <?= count($editItems) ?>;
        const fieldDefs = <?= json_encode(
            array_map(fn($f) => ['key' => $f['key'], 'label' => $f['label'], 'type' => $f['type']], $editFields),
            JSON_HEX_TAG | JSON_HEX_AMP
        ) ?>;

        function addItem() {
            const list = document.getElementById('items-list');
            const idx = itemCounter++;
            const row = document.createElement('div');
            row.className = 'item-row flex flex-wrap gap-2 items-center bg-zinc-800/50 border border-white/5 rounded-xl p-3';
            const s = 'background:#27272a;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.625rem 0.875rem;color:#fff;font-size:0.875rem;';
            let fieldsHtml = '';
            fieldDefs.forEach(f => {
                const t = f.type === 'number' ? 'number' : 'text';
                const m = f.type === 'number' ? 'min="0"' : '';
                fieldsHtml += `<input type="${t}" name="fv_${f.key}[]" placeholder="${f.label}" ${m} style="${s}min-width:80px;flex:1;">`;
            });
            row.innerHTML = `
                <input type="hidden" name="item_idx[]" value="${idx}">
                <input type="text" name="item_name[]" placeholder="Name" style="${s}min-width:120px;flex:2;">
                <input type="hidden" name="item_img[]" value="">
                ${fieldsHtml}
                <input type="file" name="item_upload_${idx}" accept="image/*" style="${s}min-width:120px;flex:1.5;">
                <button type="button" onclick="this.closest('.item-row').remove()"
                        style="background:#ef4444;color:#fff;padding:0.4rem 0.875rem;border-radius:0.625rem;font-size:0.75rem;font-weight:600;cursor:pointer;flex-shrink:0;">✕</button>`;
            list.appendChild(row);
            row.querySelector('input[name="item_name[]"]').focus();
        }
        </script>

        <?php endif; // $editInd exists ?>

        <?php endif; // subpage switch ?>

    </main>
</div>

</body>
</html>
