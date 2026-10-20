<?php
require_once 'config.php';
$projects = get_projects();

function format_date($dateStr) {
    if (!$dateStr) return '';
    $ts = strtotime($dateStr);
    return $ts ? date('M j, Y', $ts) : $dateStr;
}

$heroProjects = array_slice($projects, 0, 3);
$totalCount   = count($projects);
$allCategories = get_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creative Gardens — Websites that stand out</title>
    <meta name="description" content="We design and build websites that make people stop scrolling.">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="public-nav">
    <div class="public-nav-inner">
        <a href="index.php" class="public-brand">
            <img src="logo.png" alt="Creative Gardens">
        </a>
        <nav class="public-links">
            <a href="#garden">Projects</a>
            <a href="#" onclick="openContact(event)">Contact</a>
        </nav>
    </div>
</header>

<section class="public-hero">
    <span class="hero-eyebrow">Creative studio</span>
    <h1 class="public-hero-title">
        Websites that make people
        <span class="hero-accent">stop scrolling.</span>
    </h1>
    <p class="public-hero-sub">
        We design and build digital experiences that refuse to blend in.
        Every pixel, every word — crafted to make your brand unforgettable.
    </p>
    <div class="public-hero-actions">
        <a href="#" onclick="openContact(event)" class="btn-primary">Start a project</a>
        <a href="#garden" class="btn-link">See our work →</a>
    </div>
</section>

<section class="public-projects" id="garden">
    <h2 class="public-section-title">In the garden</h2>

    <?php if (!empty($projects)): ?>
        <div class="projects-toolbar">
            <div class="toolbar-select">
                <select id="categoryFilter">
                    <option value="all">All categories</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="toolbar-search">
                <input type="text" id="searchInput" placeholder="Search projects...">
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($projects)): ?>
        <p class="public-empty">Nothing has been planted yet. Check back soon.</p>
    <?php else: ?>
        <div class="public-grid" id="projectGrid">
            <?php foreach ($projects as $proj):
                $status = $proj['status'] ?? 'Pending';
                $isComplete = is_project_complete($status);
                $hasLink = ($isComplete && !empty($proj['demoUrl']));
                $category = $proj['category'] ?? 'Other';
            ?>
                <article class="public-card"
                         data-category="<?= e($category) ?>"
                         data-title="<?= e(strtolower($proj['title'])) ?>"
                         data-description="<?= e(strtolower($proj['description'])) ?>">
                    <div class="public-card-logo">
                        <img src="<?= e($proj['imageUrl']) ?>" alt="<?= e($proj['title']) ?> logo">
                    </div>
                    <div class="public-card-body">
                        <h3 class="public-card-title"><?= e($proj['title']) ?></h3>
                        <p class="public-card-desc"><?= e($proj['description']) ?></p>
                        <div class="public-card-meta">
                            <span class="public-status <?= $isComplete ? 'complete' : 'pending' ?>">
                                <span class="dot"></span>
                                <?= $isComplete ? 'Complete' : 'Pending' ?>
                            </span>
                            <span class="public-date">
                                <?= $isComplete ? 'Launched' : 'Launch' ?> · <?= e(format_date($proj['launchDate'] ?? '')) ?>
                            </span>
                        </div>
                        <?php if ($hasLink): ?>
                            <a href="<?= e($proj['demoUrl']) ?>" target="_blank" rel="noopener" class="public-btn">Preview project</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="no-results" id="noResults" style="display:none;">No projects match your search.</p>

        <div class="load-more-wrap" id="loadMoreWrap">
            <button id="loadMoreBtn" class="load-more-btn" type="button">
                <span id="loadMoreLabel">View more ↓</span>
            </button>
        </div>
    <?php endif; ?>
</section>

<section class="public-contact">
    <h2 class="public-contact-title">Let's plant something<br>worth visiting.</h2>
    <a href="#" onclick="openContact(event)" class="public-contact-link">Get in touch</a>
</section>

<footer class="public-footer">
    <div class="public-footer-inner">
        <span>Creative Gardens</span>
        <span>&copy; <?= date('Y') ?> · Harare, Zimbabwe</span>
    </div>
</footer>

<div class="modal-overlay" id="contactModal" onclick="closeContact(event)">
    <div class="modal-card" onclick="event.stopPropagation();">
        <button class="modal-close" onclick="closeContact(event)">&times;</button>
        <h2 class="modal-title">Get in touch</h2>
        <p class="modal-sub">Tell us about your project — we'd love to hear from you.</p>
        <form action="contact.php" method="POST" class="modal-form">
            <?= csrf_field() ?>
            <label>Your name <input type="text" name="name" required maxlength="100"></label>
            <label>Email <input type="email" name="email" required maxlength="150"></label>
            <label>Message <textarea name="message" required maxlength="2000" rows="5"></textarea></label>
            <button type="submit" class="btn-primary btn-full">Send message</button>
        </form>
    </div>
</div>

<script>
function openContact(e) {
    if (e) e.preventDefault();
    document.getElementById('contactModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeContact(e) {
    if (e) e.preventDefault();
    document.getElementById('contactModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeContact(e);
});

(function () {
    const grid       = document.getElementById('projectGrid');
    if (!grid) return;

    const cards      = Array.from(grid.querySelectorAll('.public-card'));
    const select     = document.getElementById('categoryFilter');
    const search     = document.getElementById('searchInput');
    const loadMore   = document.getElementById('loadMoreBtn');
    const loadLabel  = document.getElementById('loadMoreLabel');
    const noResults  = document.getElementById('noResults');
    const sectionTop = document.getElementById('garden');
    const PER_PAGE   = 6;

    let visibleCount = PER_PAGE;
    let expanded     = false;

    function getFiltered() {
        const cat = (select.value || 'all').toLowerCase();
        const q   = (search.value || '').trim().toLowerCase();

        return cards.filter(card => {
            const cardCat = (card.dataset.category || '').toLowerCase();
            const title   = (card.dataset.title || '').toLowerCase();
            const desc    = (card.dataset.description || '').toLowerCase();
            const matchCat  = (cat === 'all') || (cardCat === cat);
            const matchText = q === '' || title.includes(q) || desc.includes(q);
            return matchCat && matchText;
        });
    }

    function render() {
        const filtered = getFiltered();

        cards.forEach(c => c.style.display = 'none');
        filtered.slice(0, visibleCount).forEach(c => c.style.display = '');

        if (noResults) {
            noResults.style.display = (filtered.length === 0) ? 'block' : 'none';
        }

        if (loadMore && loadLabel) {
            if (filtered.length <= PER_PAGE) {
                loadMore.style.display = 'none';
                expanded = false;
            } else if (visibleCount >= filtered.length) {
                loadMore.style.display = 'inline-flex';
                loadLabel.textContent = 'Show less ↑';
                expanded = true;
            } else {
                loadMore.style.display = 'inline-flex';
                loadLabel.textContent = 'View more ↓';
                expanded = false;
            }
        }
    }

    if (select) select.addEventListener('change', () => { visibleCount = PER_PAGE; render(); });
    if (search) search.addEventListener('input',  () => { visibleCount = PER_PAGE; render(); });

    if (loadMore) {
        loadMore.addEventListener('click', () => {
            if (expanded) {
                visibleCount = PER_PAGE;
                render();
                if (sectionTop) sectionTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                visibleCount += PER_PAGE;
                render();
            }
        });
    }

    render();
})();
</script>

</body>
</html>
