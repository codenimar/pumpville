<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Game Guides · $PUMPVILLE';
$data = loadJson('guides.json');
$videos = $data['videos'] ?? [];
require_once __DIR__ . '/includes/header.php';
?>

    <div class="mb-8">
        <h1 class="title-font text-3xl font-semibold tracking-tight">Game Guides</h1>
        <p class="text-zinc-400 mt-2">Video tutorials and guides for Pumpville from YouTube.</p>
    </div>

    <?php if (empty($videos)): ?>
        <div class="glass border border-white/10 rounded-2xl p-12 text-center text-zinc-400">
            <div class="text-5xl mb-4">🎬</div>
            <p class="text-lg font-medium">No guides added yet.</p>
            <p class="text-sm mt-1">Check back soon or visit the <a href="https://x.com/PumpvilleWorld" class="text-sky-400 hover:underline" target="_blank">@PumpvilleWorld</a> X account.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($videos as $video): ?>
                <div class="glass border border-white/10 rounded-2xl overflow-hidden flex flex-col">
                    <?php if (!empty($video['youtube_id'])): ?>
                        <div class="relative" style="padding-top:56.25%">
                            <iframe
                                class="absolute inset-0 w-full h-full"
                                src="https://www.youtube.com/embed/<?= htmlspecialchars($video['youtube_id']) ?>"
                                title="<?= htmlspecialchars($video['title']) ?>"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center justify-center bg-zinc-800 text-zinc-500" style="height:180px">
                            <span class="text-4xl">▶</span>
                        </div>
                    <?php endif; ?>
                    <div class="p-5 flex flex-col flex-1">
                        <h2 class="font-semibold text-base leading-snug"><?= htmlspecialchars($video['title']) ?></h2>
                        <?php if (!empty($video['description'])): ?>
                            <p class="text-zinc-400 text-sm mt-2 flex-1"><?= htmlspecialchars($video['description']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($video['youtube_id'])): ?>
                            <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($video['youtube_id']) ?>"
                               target="_blank"
                               class="mt-4 inline-flex items-center gap-x-2 text-sm text-red-400 hover:text-red-300 transition-colors font-medium">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                Watch on YouTube
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
