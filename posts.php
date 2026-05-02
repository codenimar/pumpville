<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'X Posts · $PUMPVILLE';
$data = loadJson('posts.json');
$posts = $data['posts'] ?? [];
require_once __DIR__ . '/includes/header.php';
?>

    <div class="mb-8">
        <h1 class="title-font text-3xl font-semibold tracking-tight">X / Twitter Posts</h1>
        <p class="text-zinc-400 mt-2">Latest posts from <a href="https://x.com/PumpvilleWorld" target="_blank" class="text-sky-400 hover:underline">@PumpvilleWorld</a>.</p>
    </div>

    <?php if (empty($posts) || (count($posts) === 1 && empty($posts[0]['tweet_id']))): ?>
        <div class="glass border border-white/10 rounded-2xl p-12 text-center text-zinc-400">
            <div class="text-5xl mb-4">𝕏</div>
            <p class="text-lg font-medium">No posts added yet.</p>
            <p class="text-sm mt-2">
                <a href="https://x.com/PumpvilleWorld" target="_blank" class="text-sky-400 hover:underline">Follow @PumpvilleWorld on X</a> for the latest news.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($posts as $post):
                if (empty($post['tweet_id']) && empty($post['description'])) continue;
            ?>
                <div class="glass border border-white/10 rounded-2xl p-5 flex flex-col gap-y-3">
                    <?php if (!empty($post['tweet_id'])): ?>
                        <blockquote class="twitter-tweet" data-theme="dark" data-dnt="true">
                            <a href="https://twitter.com/PumpvilleWorld/status/<?= htmlspecialchars($post['tweet_id']) ?>"></a>
                        </blockquote>
                    <?php else: ?>
                        <div class="flex items-start gap-x-3">
                            <span class="text-sky-400 text-2xl mt-0.5">𝕏</span>
                            <div>
                                <p class="font-medium text-sm text-sky-400">@PumpvilleWorld</p>
                                <?php if (!empty($post['description'])): ?>
                                    <p class="text-zinc-300 text-sm mt-1"><?= nl2br(htmlspecialchars($post['description'])) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($post['url'])): ?>
                                    <a href="<?= htmlspecialchars($post['url']) ?>" target="_blank" class="text-xs text-sky-400 hover:underline mt-2 inline-block">View on X →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $hasTweets = false;
        foreach ($posts as $p) { if (!empty($p['tweet_id'])) { $hasTweets = true; break; } }
        if ($hasTweets): ?>
            <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
        <?php endif; ?>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
