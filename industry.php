<?php
require_once __DIR__ . '/includes/auth.php';

$data = loadJson('industries.json');
$industries = $data['industries'] ?? [];

$id = $_GET['id'] ?? '';
$industry = null;
foreach ($industries as $ind) {
    if ($ind['id'] === $id) {
        $industry = $ind;
        break;
    }
}

if (!$industry) {
    header('Location: industries.php');
    exit;
}

$pageTitle = htmlspecialchars($industry['name']) . ' Industry · $PUMPVILLE';
require_once __DIR__ . '/includes/header.php';
?>

    <!-- Breadcrumb -->
    <div class="flex items-center gap-x-2 text-sm text-zinc-500 mb-8">
        <a href="industries.php" class="hover:text-zinc-300 transition-colors">Industries</a>
        <span>›</span>
        <span class="text-zinc-300"><?= htmlspecialchars($industry['name']) ?></span>
    </div>

    <!-- Industry Header -->
    <div class="flex items-start gap-x-6 mb-10">
        <?php if (!empty($industry['image'])): ?>
            <img src="<?= htmlspecialchars($industry['image']) ?>" alt="<?= htmlspecialchars($industry['name']) ?>"
                 class="w-20 h-20 object-contain rounded-2xl bg-zinc-800 p-2 pixel-img">
        <?php else: ?>
            <div class="w-20 h-20 bg-zinc-800 rounded-2xl flex items-center justify-center text-4xl flex-shrink-0">
                <?= htmlspecialchars($industry['icon'] ?? '🏭') ?>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="title-font text-3xl font-semibold tracking-tight"><?= htmlspecialchars($industry['name']) ?> Industry</h1>
            <p class="text-zinc-400 mt-2 max-w-2xl"><?= htmlspecialchars($industry['description'] ?? '') ?></p>
        </div>
    </div>

    <?php if ($industry['id'] === 'fishing' && !empty($industry['fish'])): ?>

        <!-- Common Bait Info -->
        <div class="glass border border-white/10 rounded-2xl p-5 mb-8 flex items-center gap-x-4">
            <img src="assets/worm.png" alt="Worm" class="w-10 h-10 pixel-img">
            <div>
                <p class="font-semibold text-emerald-400 text-sm">Common Bait: Worm</p>
                <p class="text-zinc-400 text-sm">The worm is the most versatile bait, effective for catching Bass, Goldfish, Catfish, Perch, Tilapia, and Trout.</p>
            </div>
        </div>

        <h2 class="title-font text-2xl font-semibold mb-6">
            Common Fish
            <span class="text-base font-normal text-zinc-500 ml-2">(<?= count($industry['fish']) ?> types)</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($industry['fish'] as $fish): ?>
                <div class="glass border border-white/10 rounded-2xl p-5 flex flex-col gap-y-4">
                    <div class="flex items-center gap-x-4">
                        <?php if (!empty($fish['image'])): ?>
                            <img src="<?= htmlspecialchars($fish['image']) ?>"
                                 alt="<?= htmlspecialchars($fish['name']) ?>"
                                 class="w-14 h-14 object-contain pixel-img bg-zinc-800 rounded-xl p-1">
                        <?php else: ?>
                            <div class="w-14 h-14 bg-zinc-800 rounded-xl flex items-center justify-center text-2xl">🐟</div>
                        <?php endif; ?>
                        <h3 class="title-font text-lg font-semibold"><?= htmlspecialchars($fish['name']) ?></h3>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3 text-center">
                            <p class="text-xs text-emerald-400 font-medium tracking-wider">XP</p>
                            <p class="title-font text-xl font-semibold text-emerald-400 mt-0.5">+<?= (int)($fish['xp'] ?? 0) ?></p>
                        </div>
                        <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-3 text-center">
                            <p class="text-xs text-yellow-400 font-medium tracking-wider">VALUE</p>
                            <p class="title-font text-xl font-semibold text-yellow-400 mt-0.5"><?= (int)($fish['value'] ?? 0) ?> 🪙</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500 font-medium tracking-wider mb-2">BAITS</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (($fish['baits'] ?? []) as $bait): ?>
                                <span class="flex items-center gap-x-1.5 px-2.5 py-1 bg-zinc-800 rounded-lg text-xs font-medium text-zinc-300">
                                    <?php if (strtolower($bait) === 'worm'): ?>
                                        <img src="assets/worm.png" alt="worm" class="w-4 h-4 pixel-img">
                                    <?php else: ?>
                                        🎣
                                    <?php endif; ?>
                                    <?= htmlspecialchars($bait) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="glass border border-white/10 rounded-2xl p-12 text-center text-zinc-400">
            <p class="text-4xl mb-4"><?= htmlspecialchars($industry['icon'] ?? '🏭') ?></p>
            <p class="text-lg font-medium">Details coming soon.</p>
        </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
