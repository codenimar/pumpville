<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Industries · $PUMPVILLE';
$data = loadJson('industries.json');
$industries = $data['industries'] ?? [];
require_once __DIR__ . '/includes/header.php';
?>

    <div class="mb-8">
        <h1 class="title-font text-3xl font-semibold tracking-tight">Industries</h1>
        <p class="text-zinc-400 mt-2">Explore the different industries in Pumpville and learn how to earn XP and coins.</p>
    </div>

    <?php if (empty($industries)): ?>
        <div class="glass border border-white/10 rounded-2xl p-12 text-center text-zinc-400">
            <div class="text-5xl mb-4">🏭</div>
            <p class="text-lg font-medium">No industries added yet.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($industries as $industry): ?>
                <a href="industry.php?id=<?= urlencode($industry['id']) ?>"
                   class="glass border border-white/10 hover:border-emerald-500/40 rounded-2xl p-6 flex flex-col gap-y-4 transition-all hover:shadow-lg hover:shadow-emerald-500/10 group">
                    <div class="flex items-center gap-x-4">
                        <?php if (!empty($industry['image'])): ?>
                            <img src="<?= htmlspecialchars($industry['image']) ?>" alt="<?= htmlspecialchars($industry['name']) ?>"
                                 class="w-14 h-14 object-contain rounded-xl bg-zinc-800 p-1 pixel-img">
                        <?php else: ?>
                            <div class="w-14 h-14 bg-zinc-800 rounded-xl flex items-center justify-center text-3xl">
                                <?= htmlspecialchars($industry['icon'] ?? '🏭') ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="title-font text-xl font-semibold group-hover:text-emerald-400 transition-colors">
                                <?= htmlspecialchars($industry['name']) ?>
                            </h2>
                            <?php if ($industry['id'] === 'fishing'): ?>
                                <span class="text-xs text-zinc-500"><?= count($industry['fish'] ?? []) ?> fish types</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-zinc-400 text-sm leading-relaxed"><?= htmlspecialchars($industry['description'] ?? '') ?></p>
                    <span class="mt-auto text-sm font-medium text-emerald-400 group-hover:underline">View details →</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
