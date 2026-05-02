<?php
require_once __DIR__ . '/includes/auth.php';

$data = loadJson('industries.json');
$industries = $data['industries'] ?? [];

$id = $_GET['id'] ?? '';
if (!preg_match('/^[a-z0-9_\-]+$/i', $id)) {
    header('Location: industries.php');
    exit;
}
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

$fields = $industry['fields'] ?? [];
$items  = $industry['items']  ?? [];

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
                 class="w-20 h-20 object-contain rounded-2xl bg-zinc-800 p-2 pixel-img flex-shrink-0">
        <?php else: ?>
            <div class="w-20 h-20 bg-zinc-800 rounded-2xl flex items-center justify-center text-4xl flex-shrink-0">
                <?= htmlspecialchars($industry['icon'] ?? '🏭') ?>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="title-font text-3xl font-semibold tracking-tight"><?= htmlspecialchars($industry['name']) ?> Industry</h1>
            <p class="text-zinc-400 mt-2 max-w-2xl"><?= htmlspecialchars($industry['description'] ?? '') ?></p>
            <p class="text-zinc-500 text-sm mt-2"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></p>
        </div>
    </div>

    <?php if (empty($items)): ?>
    <div class="glass border border-white/10 rounded-2xl p-12 text-center text-zinc-400">
        <p class="text-4xl mb-4"><?= htmlspecialchars($industry['icon'] ?? '🏭') ?></p>
        <p class="text-lg font-medium">No items added yet.</p>
    </div>
    <?php else: ?>

    <!-- Controls -->
    <div class="glass border border-white/10 rounded-2xl p-4 mb-6 flex flex-wrap gap-3 items-end">
        <!-- Sort -->
        <div>
            <label class="block text-xs text-zinc-500 uppercase tracking-wider mb-1">Sort by</label>
            <select id="sort-field" onchange="renderItems()"
                    class="bg-zinc-800 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 cursor-pointer">
                <?php
                $hasRarity = (bool) array_filter($fields, fn($f) => $f['key'] === 'rarity');
                if ($hasRarity): ?>
                    <option value="rarity">Rarity</option>
                <?php endif; ?>
                <option value="name">Name</option>
                <?php foreach ($fields as $f): if ($f['key'] === 'rarity') continue; ?>
                    <option value="<?= htmlspecialchars($f['key']) ?>"><?= htmlspecialchars($f['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Direction -->
        <div>
            <label class="block text-xs text-zinc-500 uppercase tracking-wider mb-1">Order</label>
            <select id="sort-dir" onchange="renderItems()"
                    class="bg-zinc-800 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 cursor-pointer">
                <option value="asc">Ascending</option>
                <option value="desc">Descending</option>
            </select>
        </div>
        <!-- Per-field text filters -->
        <?php foreach ($fields as $f):
            if ($f['type'] !== 'text') continue;
            $uniqueVals = array_values(array_unique(array_filter(array_map(fn($it) => $it[$f['key']] ?? '', $items))));
            if ($f['key'] === 'rarity') {
                $rarityOrd = ['common' => 1, 'uncommon' => 2, 'rare' => 3, 'epic' => 4, 'legendary' => 5, 'mythic' => 6];
                usort($uniqueVals, fn($a, $b) => ($rarityOrd[strtolower($a)] ?? 99) - ($rarityOrd[strtolower($b)] ?? 99));
            } else {
                sort($uniqueVals);
            }
            if (empty($uniqueVals)) continue;
        ?>
        <div>
            <label class="block text-xs text-zinc-500 uppercase tracking-wider mb-1">
                Filter: <?= htmlspecialchars($f['label']) ?>
            </label>
            <select id="filter-<?= htmlspecialchars($f['key']) ?>" onchange="renderItems()"
                    class="bg-zinc-800 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 cursor-pointer">
                <option value="">All</option>
                <?php foreach ($uniqueVals as $val): ?>
                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endforeach; ?>
        <!-- Search -->
        <div>
            <label class="block text-xs text-zinc-500 uppercase tracking-wider mb-1">Search</label>
            <input type="text" id="search-input" oninput="renderItems()" placeholder="Search…"
                   class="bg-zinc-800 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 w-36">
        </div>
        <div class="ml-auto self-end">
            <span id="item-count" class="text-sm text-zinc-500"></span>
        </div>
    </div>

    <!-- Items grid rendered by JS -->
    <div id="items-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>

    <script>
    const ITEMS  = <?= json_encode($items,  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const FIELDS = <?= json_encode($fields, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const RARITY_ORDER = {common:1, uncommon:2, rare:3, epic:4, legendary:5, mythic:6};

    const RARITY_COLORS = {
        common:    {border:'border-zinc-500/40',  text:'text-zinc-400',   bg:'bg-zinc-500/10'},
        uncommon:  {border:'border-green-500/40', text:'text-green-400',  bg:'bg-green-500/10'},
        rare:      {border:'border-blue-500/40',  text:'text-blue-400',   bg:'bg-blue-500/10'},
        epic:      {border:'border-purple-500/40',text:'text-purple-400', bg:'bg-purple-500/10'},
        legendary: {border:'border-yellow-500/40',text:'text-yellow-400', bg:'bg-yellow-500/10'},
        mythic:    {border:'border-red-500/40',   text:'text-red-400',    bg:'bg-red-500/10'},
    };

    function rarityStyle(rarity) {
        return RARITY_COLORS[(rarity||'').toLowerCase()] || {border:'border-zinc-500/40', text:'text-zinc-400', bg:'bg-zinc-500/10'};
    }

    function esc(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function renderItems() {
        const sortField = document.getElementById('sort-field').value;
        const sortDir   = document.getElementById('sort-dir').value;
        const search    = (document.getElementById('search-input')?.value || '').toLowerCase().trim();

        // Collect active filters
        const filters = {};
        FIELDS.forEach(f => {
            if (f.type !== 'text') return;
            const el = document.getElementById('filter-' + f.key);
            if (el && el.value) filters[f.key] = el.value;
        });

        let items = [...ITEMS];

        // Search
        if (search) {
            items = items.filter(item => {
                if (item.name.toLowerCase().includes(search)) return true;
                return FIELDS.some(f => String(item[f.key] || '').toLowerCase().includes(search));
            });
        }

        // Filters
        Object.entries(filters).forEach(([key, val]) => {
            items = items.filter(item => (item[key] || '') === val);
        });

        // Sort
        items.sort((a, b) => {
            let av = a[sortField] ?? a.name ?? '';
            let bv = b[sortField] ?? b.name ?? '';

            if (sortField === 'rarity') {
                const ao = RARITY_ORDER[(av+'').toLowerCase()] ?? 99;
                const bo = RARITY_ORDER[(bv+'').toLowerCase()] ?? 99;
                if (ao !== bo) return sortDir === 'asc' ? ao - bo : bo - ao;
                return a.name.localeCompare(b.name);
            }

            const af = parseFloat(av), bf = parseFloat(bv);
            if (!isNaN(af) && !isNaN(bf)) return sortDir === 'asc' ? af - bf : bf - af;

            const cmp = (av+'').localeCompare(bv+'', undefined, {numeric:true, sensitivity:'base'});
            return sortDir === 'asc' ? cmp : -cmp;
        });

        document.getElementById('item-count').textContent = items.length + ' of ' + ITEMS.length + ' items';

        const grid = document.getElementById('items-grid');
        if (items.length === 0) {
            grid.innerHTML = `<div class="col-span-full glass border border-white/10 rounded-2xl p-12 text-center text-zinc-400">
                <p class="text-4xl mb-3">🔍</p><p class="text-lg">No items match your filters.</p></div>`;
            return;
        }

        const rarityFieldDef = FIELDS.find(f => f.key === 'rarity');

        grid.innerHTML = items.map(item => {
            const rarity = rarityFieldDef ? (item.rarity || '') : '';
            const rs = rarityStyle(rarity);

            const imgHtml = item.image
                ? `<img src="${esc(item.image)}" alt="${esc(item.name)}" class="w-14 h-14 object-contain pixel-img bg-zinc-800 rounded-xl p-1 flex-shrink-0">`
                : `<div class="w-14 h-14 bg-zinc-800 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">📦</div>`;

            const fieldsHtml = FIELDS.map(f => {
                const val = item[f.key];
                if (val === undefined || val === null || val === '') return '';

                if (f.key === 'rarity') return ''; // shown in header badge

                if (f.type === 'number') {
                    const icon = f.key === 'xp' ? '✨' : f.key === 'value' ? '🪙' : '';
                    return `<div class="flex justify-between items-center">
                        <span class="text-xs text-zinc-500">${esc(f.label)}</span>
                        <span class="text-sm font-semibold">${icon ? icon+' ' : ''}${esc(val)}</span>
                    </div>`;
                }
                return `<div class="flex justify-between items-start gap-x-2">
                    <span class="text-xs text-zinc-500 shrink-0">${esc(f.label)}</span>
                    <span class="text-xs text-zinc-300 text-right">${esc(val)}</span>
                </div>`;
            }).filter(Boolean).join('');

            return `<div class="glass border border-white/10 rounded-2xl p-5 flex flex-col gap-y-3">
                <div class="flex items-center gap-x-3">
                    ${imgHtml}
                    <div class="min-w-0">
                        <h3 class="title-font font-semibold text-base leading-tight">${esc(item.name)}</h3>
                        ${rarity ? `<span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium border mt-1 ${rs.border} ${rs.text} ${rs.bg}">${esc(rarity)}</span>` : ''}
                    </div>
                </div>
                ${fieldsHtml ? `<div class="space-y-1.5 border-t border-white/5 pt-3">${fieldsHtml}</div>` : ''}
            </div>`;
        }).join('');
    }

    renderItems();
    </script>

    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
