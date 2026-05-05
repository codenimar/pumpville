<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = '$PUMPVILLE · Live Token Dashboard';

$mint_address = '72FkeF1cpBMtbordhTVNVbBGdaN5DfHcchstHwPWpump';
$original_supply = 1000000000;

$rpc_url = 'https://api.mainnet-beta.solana.com';

// ==================== TOKEN SUPPLY ====================
function getPumpvilleSupply($mint, $rpc) {
    $payload = [
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => 'getTokenSupply',
        'params'  => [$mint]
    ];

    $ch = curl_init($rpc);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) return null;

    $data = json_decode($response, true);
    return isset($data['result']['value']['uiAmount']) ? (float)$data['result']['value']['uiAmount'] : null;
}

// ==================== DEXSCREENER PRICE + MARKET CAP ====================
function getTokenPriceAndMC($mint, $circulating) {
    $url = "https://api.dexscreener.com/latest/dex/tokens/{$mint}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return ['price' => null, 'market_cap' => null];

    $data = json_decode($response, true);
    if (!isset($data['pairs']) || empty($data['pairs'])) return ['price' => null, 'market_cap' => null];

    $best_price = null;
    $best_liq = 0;
    foreach ($data['pairs'] as $pair) {
        if (isset($pair['priceUsd']) && isset($pair['liquidity']['usd'])) {
            $liq = (float)$pair['liquidity']['usd'];
            if ($liq > $best_liq && $liq > 5000) {
                $best_liq = $liq;
                $best_price = (float)$pair['priceUsd'];
            }
        }
    }

    if ($best_price === null) return ['price' => null, 'market_cap' => null];

    $market_cap = $circulating !== null ? round($best_price * $circulating, 2) : null;

    return [
        'price'       => $best_price,
        'market_cap'  => $market_cap
    ];
}

// ==================== RELIABLE X FOLLOWERS – fxTwitter Public API (no scraping) ====================
function getXFollowers($username = 'PumpvilleWorld') {
    $url = "https://api.fxtwitter.com/{$username}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;

    $data = json_decode($response, true);

    // fxTwitter structure
    if (isset($data['user']['followers'])) {
        return (int)$data['user']['followers'];
    }
    if (isset($data['user']['followers_count'])) {
        return (int)$data['user']['followers_count'];
    }

    return null;
}

// ==================== DISCORD MEMBERS ====================
function getDiscordMembers($inviteCode = 'pumpville') {
    $url = "https://discord.com/api/v10/invites/{$inviteCode}?with_counts=true";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;
    $data = json_decode($response, true);
    return $data['approximate_member_count'] ?? null;
}

// ==================== FETCH ALL DATA ====================
$circulating     = getPumpvilleSupply($mint_address, $rpc_url);
$burned          = $circulating !== null ? $original_supply - $circulating : 0;

$price_data      = getTokenPriceAndMC($mint_address, $circulating);
$price_usd       = $price_data['price'];
$market_cap      = $price_data['market_cap'];

$x_followers     = getXFollowers();
$discord_members = getDiscordMembers();

$last_updated = date('M j, Y • g:i A T');
$token = loadJson('token.json');
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(16px); }
        .stat-value {
            font-size: clamp(2.2rem, 5vw, 3rem);
            line-height: 1.05;
            white-space: nowrap;
            transition: transform 0.1s ease;
        }
        .card { padding: 24px 28px; min-height: 178px; }
    </style>
        <!-- Header -->
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="title-font text-3xl font-semibold tracking-tight">$PUMPVILLE Dashboard</h1>
                <div class="flex items-center gap-x-2 text-sm mt-1">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-emerald-400 font-medium">LIVE • Solana Mainnet</span>
                </div>
            </div>
            
            <div class="flex items-center gap-x-3">
                <a href="https://solscan.io/token/72FkeF1cpBMtbordhTVNVbBGdaN5DfHcchstHwPWpump" 
                   target="_blank"
                   class="flex items-center gap-x-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-2xl text-sm font-medium transition-all">
                    <span>Solscan</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L9 15" />
                    </svg>
                </a>
                <a href="https://x.com/PumpvilleWorld" target="_blank" class="flex items-center gap-x-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-2xl text-sm font-medium transition-all">
                    <span class="text-sky-400">𝕏</span>
                    <span>@PumpvilleWorld</span>
                </a>
                <a href="https://discord.gg/pumpville" target="_blank" class="flex items-center gap-x-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-2xl text-sm font-medium transition-all">
                    Discord
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-7 grid grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div class="glass border border-white/10 rounded-3xl card flex flex-col justify-between">
                    <p class="text-zinc-400 text-xs font-medium tracking-widest">TOKENS IN CIRCULATION</p>
                    <?php if ($circulating !== null): ?>
                        <p class="stat-value font-semibold title-font text-emerald-400 mt-auto" id="circulating"><?= number_format($circulating, 0) ?></p>
                        <p class="text-emerald-400 text-base mt-1">$PUMPVILLE</p>
                    <?php else: ?>
                        <p class="stat-value font-semibold title-font text-red-400 mt-auto">RPC Error</p>
                    <?php endif; ?>
                </div>

                <div class="glass border border-white/10 rounded-3xl card flex flex-col justify-between">
                    <p class="text-zinc-400 text-xs font-medium tracking-widest">TOKENS BURNED</p>
                    <?php if ($circulating !== null): ?>
                        <p class="stat-value font-semibold title-font text-orange-400 mt-auto" id="burned"><?= number_format($burned, 0) ?></p>
                        <p class="text-orange-400 text-base mt-1">$PUMPVILLE</p>
                    <?php else: ?>
                        <p class="stat-value font-semibold title-font text-red-400 mt-auto">RPC Error</p>
                    <?php endif; ?>
                </div>

                <div class="glass border border-white/10 rounded-3xl card flex flex-col justify-between">
                    <p class="text-zinc-400 text-xs font-medium tracking-widest">CURRENT PRICE</p>
                    <?php if ($price_usd !== null): ?>
                        <p class="stat-value font-semibold title-font text-cyan-400 mt-auto" id="price-usd">
                            $<?= $price_usd < 0.01 ? number_format($price_usd, 8) : number_format($price_usd, 6) ?>
                        </p>
                        <p class="text-cyan-400 text-base mt-1">USD</p>
                    <?php else: ?>
                        <p class="stat-value font-semibold title-font text-zinc-400 mt-auto" id="price-usd">—</p>
                    <?php endif; ?>
                </div>

                <div class="glass border border-white/10 rounded-3xl card flex flex-col justify-between">
                    <p class="text-zinc-400 text-xs font-medium tracking-widest">MARKET CAP</p>
                    <?php if ($market_cap !== null): ?>
                        <p class="stat-value font-semibold title-font text-purple-400 mt-auto" id="market-cap">
                            $<?= number_format($market_cap, 0) ?>
                        </p>
                        <p class="text-purple-400 text-base mt-1">USD</p>
                    <?php else: ?>
                        <p class="stat-value font-semibold title-font text-zinc-400 mt-auto" id="market-cap">—</p>
                    <?php endif; ?>
                </div>

                <div class="glass border border-white/10 rounded-3xl card flex flex-col justify-between">
                    <p class="text-zinc-400 text-xs font-medium tracking-widest">𝕏 FOLLOWERS</p>
                    <?php if ($x_followers !== null): ?>
                        <p class="stat-value font-semibold title-font text-sky-400 mt-auto" id="x-followers"><?= number_format($x_followers, 0) ?></p>
                        <p class="text-sky-400 text-base mt-1">@PumpvilleWorld</p>
                    <?php else: ?>
                        <p class="stat-value font-semibold title-font text-sky-400 mt-auto" id="x-followers">—</p>
                        <p class="text-sky-400 text-base mt-1">@PumpvilleWorld</p>
                    <?php endif; ?>
                </div>

                <div class="glass border border-white/10 rounded-3xl card flex flex-col justify-between">
                    <p class="text-zinc-400 text-xs font-medium tracking-widest">DISCORD MEMBERS</p>
                    <?php if ($discord_members !== null): ?>
                        <p class="stat-value font-semibold title-font text-indigo-400 mt-auto" id="discord-members"><?= number_format($discord_members, 0) ?></p>
                        <p class="text-indigo-400 text-base mt-1">discord.gg/pumpville</p>
                    <?php else: ?>
                        <p class="stat-value font-semibold title-font text-zinc-400 mt-auto" id="discord-members">—</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Donut Chart -->
            <div class="lg:col-span-5">
                <div class="glass border border-white/10 rounded-3xl p-6 h-full flex flex-col">
                    <h3 class="text-zinc-400 text-xs font-medium tracking-widest mb-4">SUPPLY DISTRIBUTION</h3>
                    <div class="flex-1 flex items-center justify-center">
                        <canvas id="supplyChart" class="max-h-[300px] w-full"></canvas>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-6 text-sm">
                        <div class="flex items-center gap-x-3">
                            <div class="w-3 h-3 bg-emerald-500 rounded"></div>
                            <div>
                                <p class="font-medium text-xs">Circulating</p>
                                <p class="text-emerald-400 font-mono text-sm" id="chart-circ"><?= $circulating !== null ? number_format($circulating, 0) : '0' ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-x-3">
                            <div class="w-3 h-3 bg-orange-500 rounded"></div>
                            <div>
                                <p class="font-medium text-xs">Burned</p>
                                <p class="text-orange-400 font-mono text-sm" id="chart-burned"><?= number_format($burned, 0) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-10 flex flex-col md:flex-row justify-between items-center text-xs text-zinc-500">
            <div>Original supply: <span class="font-mono text-white">1,000,000,000</span> $PUMPVILLE</div>
            <div class="mt-3 md:mt-0 flex items-center gap-x-6">
                <div>Last updated: <span id="last-updated" class="font-medium text-white"><?= $last_updated ?></span></div>
                <div class="flex items-center gap-x-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    AUTO-REFRESH EVERY 1 HOUR
                </div>
            </div>
            <div class="mt-3 md:mt-0">Powered by Solana RPC • Dexscreener • fxTwitter • Pure PHP</div>
        </div>

    <script>
        tailwind.config = { content: [] };

        // Smooth number scaling with debounce
        let resizeTimeout;
        function scaleNumbers() {
            const numbers = document.querySelectorAll('.stat-value');
            numbers.forEach(num => {
                const card = num.closest('.card');
                if (!card) return;
                
                let fontSize = 64;
                num.style.fontSize = fontSize + 'px';
                
                while ((num.scrollWidth > card.clientWidth - 40) && fontSize > 28) {
                    fontSize -= 1;
                    num.style.fontSize = fontSize + 'px';
                }
            });
        }

        function debouncedScale() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(scaleNumbers, 150);
        }

        // Chart
        let myChart;
        function createChart(circ, burned) {
            const ctx = document.getElementById('supplyChart');
            if (myChart) myChart.destroy();
            myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Circulating', 'Burned'],
                    datasets: [{
                        data: [circ || 0, burned || 0],
                        backgroundColor: ['#00ff9d', '#f59e0b'],
                        borderColor: '#18181b',
                        borderWidth: 8,
                        hoverOffset: 12
                    }]
                },
                options: {
                    cutout: '78%',
                    plugins: { legend: { display: false } }
                }
            });
        }

        <?php if ($circulating !== null): ?>
        createChart(<?= $circulating ?>, <?= $burned ?>);
        <?php endif; ?>

        async function refreshDashboard() {
            try {
                const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error();
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const ids = ['circulating','burned','price-usd','market-cap','x-followers','discord-members','last-updated','chart-circ','chart-burned'];
                ids.forEach(id => {
                    const el = doc.getElementById(id);
                    if (el) document.getElementById(id).innerHTML = el.innerHTML;
                });

                const newCirc = parseFloat(document.getElementById('circulating')?.textContent.replace(/[^0-9.]/g, '') || 0);
                const newBurned = parseFloat(document.getElementById('burned')?.textContent.replace(/[^0-9.]/g, '') || 0);
                createChart(newCirc, newBurned);

                setTimeout(scaleNumbers, 100);
            } catch(e) {}
        }

        // Initial load + resize handling
        window.addEventListener('load', () => {
            scaleNumbers();
            window.addEventListener('resize', debouncedScale);
        });

        // 1-hour refresh
        setInterval(refreshDashboard, 3600000);

        console.log('%c$PUMPVILLE Dashboard – Smooth scaling + reliable fxTwitter API for X followers ✓', 'color:#00ff9d; font-family:monospace');
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
