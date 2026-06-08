<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

$databaseError = null;
$pdo = null;
$currentUser = null;
$page = $_GET['page'] ?? 'home';

try {
    $pdo = db();
    handlePost($pdo);
    $currentUser = currentUser($pdo);
    $notice = flash();
} catch (Throwable $error) {
    $databaseError = $error;
    $notice = null;
}

function activePage(string $page, string $current): string
{
    return $page === $current ? ' is-active' : '';
}

function publicRoleLabel(?string $role): string
{
    return $role === 'worker' ? 'Helper' : ($role === 'family' ? 'Help seeker' : 'Member');
}

function renderHeader(PDO $pdo, ?array $user, string $page, ?string $notice): void
{
    $unread = $user ? unreadCount($pdo, (int) $user['user_id']) : 0;
    $area = publicArea($user['neighbourhood'] ?? 'Balbriggan');
    $isWorker = ($user['user_type'] ?? '') === 'worker';
    $isFamily = ($user['user_type'] ?? '') === 'family';
    ?>
    <header class="topbar">
        <div class="topline">
            <span><i class="fa-solid fa-location-dot"></i> Your area: <strong><?= h($area) ?></strong></span>
            <span>Local jobs, messages, bookings, and payments in one place</span>
        </div>
        <nav class="nav-shell" aria-label="Main navigation">
            <a class="brand" href="index.php">
                <span class="brand-mark"><i class="fa-solid fa-check"></i></span>
                <span>LocalLoop</span>
            </a>
            <div class="nav-links">
                <?php if (!$isWorker): ?>
                    <a class="<?= activePage('helpers', $page) ?>" href="index.php?page=helpers">Find Helpers</a>
                <?php endif; ?>
                <?php if (!$user || !$isFamily): ?>
                    <a class="<?= activePage('jobs', $page) ?>" href="index.php?page=jobs">Find Jobs</a>
                <?php endif; ?>
                <a class="<?= activePage('how', $page) ?>" href="index.php?page=how">How it works</a>
                <?php if (!$user || $isFamily): ?>
                    <a class="<?= activePage('account', $page) ?>" href="<?= $user ? 'index.php?page=account#post-job' : 'index.php?page=signup' ?>">Post a Job</a>
                <?php endif; ?>
                <a class="<?= activePage('messages', $page) ?>" href="<?= $user ? 'index.php?page=messages' : 'index.php?page=login' ?>">
                    Messages
                    <?php if ($unread > 0): ?>
                        <span class="badge"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="nav-actions">
                <?php if ($user): ?>
                    <a class="icon-link" href="index.php?page=account" title="Account">
                        <i class="fa-regular fa-user"></i>
                        <span><?= h(explode(' ', (string) $user['name'])[0]) ?></span>
                    </a>
                    <?php if ($isFamily): ?>
                        <a class="button button-primary button-small" href="index.php?page=account#post-job">Post a Job</a>
                    <?php endif; ?>
                    <form action="index.php" method="post" class="logout-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="logout">
                        <button class="text-button" type="submit">Log Out</button>
                    </form>
                <?php else: ?>
                    <a class="icon-link" href="index.php?page=login">Log In</a>
                    <a class="button button-primary button-small" href="index.php?page=signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
        <?php if ($notice): ?>
            <div class="flash"><?= h($notice) ?></div>
        <?php endif; ?>
    </header>
    <?php
}

function renderFooter(): void
{
    ?>
    <footer class="site-footer">
        <div>
            <strong>LocalLoop</strong>
            <span>Neighborhood help, booked locally.</span>
        </div>
        <div class="footer-links">
            <a href="index.php?page=helpers">Find Helpers</a>
            <a href="index.php?page=jobs">Find Jobs</a>
            <a href="index.php?page=how">How it works</a>
            <a href="index.php?page=messages">Messages</a>
            <a href="index.php?page=account">Account</a>
        </div>
    </footer>
    <?php
}

function renderHome(PDO $pdo, ?array $user): void
{
    $stats = dashboardStats($pdo);
    $featuredJobs = array_slice(jobs($pdo), 0, 3);
    $helpers = array_slice(helpers($pdo, [], $user), 0, 3);
    $heroImage = 'LocalLoop/profile_files/photo-1516733725897-1aa73b87c8e8';
    $area = publicArea($user['neighbourhood'] ?? 'Balbriggan');
    ?>
    <main>
        <section class="hero-band">
            <div class="hero-copy">
                <p class="eyebrow">Care & home helpers</p>
                <h1>Find household help or local work near <?= h($area) ?>.</h1>
                <p class="hero-text">Search helpers for babysitting, pets, tutoring, errands, cleaning, gardening, and everyday household support.</p>
                <form class="market-search" action="index.php" method="get">
                    <div class="search-tabs" role="group" aria-label="Choose search type">
                        <button type="submit" name="page" value="helpers">
                            <i class="fa-solid fa-house-user"></i>
                            Find household help
                        </button>
                        <button type="submit" name="page" value="jobs">
                            <i class="fa-solid fa-briefcase"></i>
                            Find Jobs
                        </button>
                    </div>
                    <label class="search-input-line">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input name="q" type="search" placeholder="Try babysitting, tutoring, dog walking..." aria-label="Search helpers or jobs">
                    </label>
                    <div class="search-options">
                        <label class="checkbox-line">
                            <input name="nearby" value="0" type="hidden">
                            <input name="nearby" value="1" type="checkbox" checked>
                            <span>Search nearby</span>
                        </label>
                        <label>
                            <span>Radius</span>
                            <select name="radius">
                                <option value="5">5km</option>
                                <option value="10" selected>10km</option>
                                <option value="20">20km</option>
                                <option value="50">50km</option>
                            </select>
                        </label>
                    </div>
                </form>
                <div class="hero-actions">
                    <a class="button button-primary" href="index.php?page=signup&type=family">I need household help</a>
                    <a class="button button-secondary" href="index.php?page=signup&type=worker">I'm looking for household work</a>
                </div>
            </div>
            <div class="hero-visual" aria-label="Local family support">
                <div class="info-pill"><i class="fa-solid fa-map-location-dot"></i> Ireland marketplace</div>
                <div class="worker-orbit">
                    <img class="hero-avatar" src="<?= h($heroImage) ?>" alt="Family walking together">
                    <div class="orbit-card orbit-card-top">
                        <strong><?= (int) $stats['workers'] ?> helpers</strong>
                        <span>Profiles with services</span>
                    </div>
                    <div class="orbit-card orbit-card-bottom">
                        <strong><?= (int) $stats['jobs'] ?> open jobs</strong>
                        <span>Near <?= h($area) ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats-strip" aria-label="Marketplace stats">
            <div><strong><?= (int) $stats['jobs'] ?></strong><span>Open jobs</span></div>
            <div><strong><?= (int) $stats['workers'] ?></strong><span>Helper profiles</span></div>
            <div><strong><?= (int) $stats['messages'] ?></strong><span>Messages sent</span></div>
            <div><strong><?= (int) $stats['bookings'] ?></strong><span>Confirmed bookings</span></div>
        </section>

        <section class="section-shell">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Featured helpers</p>
                    <h2>Household helpers near <?= h($area) ?></h2>
                </div>
                <a class="text-link" href="index.php?page=helpers">See helpers</a>
            </div>
            <div class="helper-card-grid">
                <?php foreach ($helpers as $helper): ?>
                    <?php renderHelperCard($helper); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section-shell how-steps" id="how-it-works">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">How it works</p>
                    <h2>Search, connect, arrange work.</h2>
                </div>
            </div>
            <div class="steps-grid">
                <article>
                    <span>1</span>
                    <h3>Search</h3>
                    <p>Filter by service, area, radius, and availability notes to find suitable helpers or jobs.</p>
                </article>
                <article>
                    <span>2</span>
                    <h3>Connect</h3>
                    <p>Open a profile or listing, send a message, and agree the details privately.</p>
                </article>
                <article>
                    <span>3</span>
                    <h3>Book / arrange work</h3>
                    <p>Use bookings and payment records in your account once both sides are ready.</p>
                </article>
            </div>
        </section>

        <section class="section-shell">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Available opportunities</p>
                    <h2>Open household jobs</h2>
                </div>
                <a class="text-link" href="index.php?page=jobs">View all</a>
            </div>
            <div class="job-grid">
                <?php foreach ($featuredJobs as $job): ?>
                    <?php renderJobCard($pdo, $job, $user); ?>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <?php
}

function renderHowPage(): void
{
    ?>
    <main class="page-shell">
        <section class="page-heading">
            <div>
                <p class="eyebrow">How it works</p>
                <h1>Household help, arranged clearly.</h1>
                <p class="page-subtitle">LocalLoop helps families and helpers find each other, talk through the work, and keep booking records in one place.</p>
            </div>
            <a class="button button-primary" href="index.php?page=signup">Get started</a>
        </section>
        <section class="section-shell how-steps">
            <div class="steps-grid">
                <article>
                    <span>1</span>
                    <h3>Search</h3>
                    <p>Use service, keyword, radius, list view, and map view to compare helpers or open jobs.</p>
                </article>
                <article>
                    <span>2</span>
                    <h3>Connect</h3>
                    <p>Open a real profile or listing and message through your LocalLoop account.</p>
                </article>
                <article>
                    <span>3</span>
                    <h3>Book / arrange work</h3>
                    <p>Agree details privately, then keep applications, bookings, and payment records together.</p>
                </article>
            </div>
        </section>
    </main>
    <?php
}

function helperFiltersFromRequest(): array
{
    return [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'category_id' => (int) ($_GET['category_id'] ?? 0),
        'nearby' => isset($_GET['nearby']) ? (int) $_GET['nearby'] : 1,
        'radius' => selectedRadius($_GET['radius'] ?? null),
    ];
}

function renderHelperFilters(array $filters, array $categories, string $prefix = 'helper'): void
{
    ?>
    <form action="index.php" method="get">
        <input type="hidden" name="page" value="helpers">
        <label for="<?= h($prefix) ?>-search">Search</label>
        <input id="<?= h($prefix) ?>-search" name="q" value="<?= h($filters['q']) ?>" placeholder="Babysitting, tutoring, pets">
        <label for="<?= h($prefix) ?>-category">Services</label>
        <select id="<?= h($prefix) ?>-category" name="category_id">
            <option value="">All services</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>" <?= (int) $category['category_id'] === (int) $filters['category_id'] ? 'selected' : '' ?>>
                    <?= h(labelize($category['category_name'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="<?= h($prefix) ?>-radius">Distance</label>
        <select id="<?= h($prefix) ?>-radius" name="radius">
            <?php foreach ([5, 10, 20, 50] as $radius): ?>
                <option value="<?= $radius ?>" <?= (int) $filters['radius'] === $radius ? 'selected' : '' ?>><?= $radius ?>km</option>
            <?php endforeach; ?>
        </select>
        <label class="checkbox-line">
            <input name="nearby" value="0" type="hidden">
            <input name="nearby" value="1" type="checkbox" <?= !empty($filters['nearby']) ? 'checked' : '' ?>>
            <span>Search nearby</span>
        </label>
        <button class="button button-primary" type="submit">Filter helpers</button>
    </form>
    <?php
}

function renderHelpersPage(PDO $pdo, ?array $user): void
{
    if (($user['user_type'] ?? '') === 'worker') {
        flash('Helper accounts browse jobs from the Find Jobs page.');
        redirectTo('index.php?page=jobs');
    }
    $filters = helperFiltersFromRequest();
    $view = ($_GET['view'] ?? 'list') === 'map' ? 'map' : 'list';
    [$baseLat, $baseLng, $area] = defaultSearchPoint($user);
    $helperRows = helpers($pdo, $filters, $user);
    $markers = helperMapMarkers($helperRows);
    $categories = categories($pdo);
    $baseQuery = [
        'page' => 'helpers',
        'q' => $filters['q'],
        'category_id' => $filters['category_id'] ?: '',
        'nearby' => $filters['nearby'] ? 1 : '',
        'radius' => $filters['radius'],
    ];
    $listUrl = 'index.php?' . http_build_query(array_merge($baseQuery, ['view' => 'list']));
    $mapUrl = 'index.php?' . http_build_query(array_merge($baseQuery, ['view' => 'map']));
    ?>
    <main class="page-shell">
        <section class="page-heading">
            <div>
                <p class="eyebrow">Find Helpers</p>
                <h1>Showing helpers near <?= h($area) ?></h1>
                <p class="page-subtitle">Search within <?= (int) $filters['radius'] ?>km of <?= h($area) ?>. Public results use town or area only.</p>
            </div>
            <a class="button button-primary" href="index.php?page=signup&type=family">I need household help</a>
        </section>

        <details class="mobile-filter-drawer">
            <summary><i class="fa-solid fa-sliders"></i> Filters</summary>
            <div class="filter-panel">
                <?php renderHelperFilters($filters, $categories, 'mobile-helper'); ?>
            </div>
        </details>

        <section class="browse-layout">
            <aside class="filter-panel desktop-filter">
                <?php renderHelperFilters($filters, $categories); ?>
                <div class="filter-note">
                    <strong>Location privacy</strong>
                    <span>Full addresses stay private. Map markers are approximate.</span>
                </div>
            </aside>

            <div class="results-surface <?= $view === 'map' ? 'show-map' : 'show-list' ?>">
                <div class="mobile-view-toggle" aria-label="Choose results view">
                    <a class="<?= $view === 'list' ? 'is-active' : '' ?>" href="<?= h($listUrl) ?>">
                        <i class="fa-solid fa-list"></i>
                        List view
                    </a>
                    <a class="<?= $view === 'map' ? 'is-active' : '' ?>" href="<?= h($mapUrl) ?>">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Map view
                    </a>
                </div>

                <div class="listing-results-layout">
                    <div class="list-pane helper-list">
                        <?php if (!$helperRows): ?>
                            <div class="empty-state">No helpers match that search yet.</div>
                        <?php endif; ?>
                        <?php foreach ($helperRows as $helper): ?>
                            <?php renderHelperCard($helper, true); ?>
                        <?php endforeach; ?>
                    </div>

                    <aside class="map-pane">
                        <div
                            id="helpers-map"
                            class="listings-map"
                            data-map-markers="<?= h(json_encode($markers, JSON_THROW_ON_ERROR)) ?>"
                            data-empty-message="No mapped helpers match these filters yet."
                        ></div>
                        <p class="map-privacy-note">Map points are slightly offset from saved coordinates. Exact addresses are not public.</p>
                    </aside>
                </div>
            </div>
        </section>
    </main>
    <?php
}

function renderHelperCard(array $helper, bool $wide = false): void
{
    $services = array_filter(array_map('trim', explode(',', (string) ($helper['services'] ?? ''))));
    $bio = trim((string) ($helper['bio'] ?? ''));
    ?>
    <article class="helper-card <?= $wide ? 'helper-card-wide' : '' ?>">
        <?= avatar($helper, 'avatar avatar-large') ?>
        <div class="helper-card-body">
            <div class="helper-card-title">
                <div>
                    <h3><?= h($helper['name']) ?></h3>
                    <p><i class="fa-solid fa-location-dot"></i> <?= h(publicArea($helper['neighbourhood'] ?? 'Balbriggan')) ?></p>
                </div>
                <strong><?= money($helper['hourly_rate'] ?? 0) ?>/hr</strong>
            </div>
            <p><?= h($bio ?: 'Available for household help and local odd jobs.') ?></p>
            <div class="helper-meta">
                <span><i class="fa-regular fa-calendar-check"></i> <?= (int) ($helper['experience_years'] ?? 0) ?>+ years experience</span>
                <span><i class="fa-regular fa-comment"></i> References coming soon</span>
            </div>
            <?php if ($services): ?>
                <div class="skill-list compact-skills">
                    <?php foreach (array_slice($services, 0, 4) as $service): ?>
                        <span class="skill-badge"><?= h(labelize($service)) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="card-actions">
                <a class="button button-secondary" href="index.php?page=worker&id=<?= (int) $helper['user_id'] ?>">View profile</a>
            </div>
        </div>
    </article>
    <?php
}

function renderJobFilters(array $filters, array $categories, string $prefix = 'job'): void
{
    ?>
    <form action="index.php" method="get">
        <input type="hidden" name="page" value="jobs">
        <label for="<?= h($prefix) ?>-search">Search</label>
        <input id="<?= h($prefix) ?>-search" name="q" value="<?= h($filters['q']) ?>" placeholder="Tutoring, pets, errands">
        <label for="<?= h($prefix) ?>-category">Category</label>
        <select id="<?= h($prefix) ?>-category" name="category_id">
            <option value="">All jobs</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>" <?= (int) $category['category_id'] === (int) $filters['category_id'] ? 'selected' : '' ?>>
                    <?= h(labelize($category['category_name'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="<?= h($prefix) ?>-radius">Distance</label>
        <select id="<?= h($prefix) ?>-radius" name="radius">
            <?php foreach ([5, 10, 20, 50] as $radius): ?>
                <option value="<?= $radius ?>" <?= (int) ($filters['radius'] ?? 10) === $radius ? 'selected' : '' ?>><?= $radius ?>km</option>
            <?php endforeach; ?>
        </select>
        <label class="checkbox-line">
            <input name="nearby" value="0" type="hidden">
            <input name="nearby" value="1" type="checkbox" <?= !empty($filters['nearby']) ? 'checked' : '' ?>>
            <span>Search nearby</span>
        </label>
        <button class="button button-primary" type="submit">Filter jobs</button>
    </form>
    <?php
}

function renderAddressFields(array $values = [], string $mode = 'account'): void
{
    $line1 = (string) ($values['address_line1'] ?? '');
    $line2 = (string) ($values['address_line2'] ?? '');
    $town = (string) ($values['address_town'] ?? $values['neighbourhood'] ?? $values['location'] ?? 'Balbriggan');
    $county = (string) ($values['address_county'] ?? '');
    $eircode = (string) ($values['eircode'] ?? '');
    $country = (string) ($values['country'] ?? 'Ireland');
    ?>
    <fieldset class="address-fieldset span-2">
        <legend><?= $mode === 'job' ? 'Location and private address' : 'Private address' ?></legend>
        <label>Address line 1
            <input name="address_line1" value="<?= h($line1) ?>" placeholder="Street address">
        </label>
        <label>Address line 2 <span>optional</span>
            <input name="address_line2" value="<?= h($line2) ?>" placeholder="Apartment, building, etc.">
        </label>
        <label>Town / Area
            <input name="address_town" value="<?= h($town) ?>" required placeholder="Balbriggan">
        </label>
        <label>County / City
            <input name="address_county" value="<?= h($county) ?>" placeholder="Dublin">
        </label>
        <label>Eircode
            <input name="eircode" value="<?= h($eircode) ?>" placeholder="Optional">
        </label>
        <label>Country
            <input name="country" value="<?= h($country ?: 'Ireland') ?>">
        </label>
        <p>Only Town / Area is shown publicly. Street address and Eircode stay private.</p>
    </fieldset>
    <?php
}

function renderJobsPage(PDO $pdo, ?array $user): void
{
    $filters = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'category_id' => (int) ($_GET['category_id'] ?? 0),
        'nearby' => isset($_GET['nearby']) ? (int) $_GET['nearby'] : 1,
        'radius' => selectedRadius($_GET['radius'] ?? null),
    ];
    if (($user['user_type'] ?? '') === 'family') {
        flash('Household accounts browse helpers from the Find Helpers page.');
        redirectTo('index.php?page=helpers');
    }
    $view = ($_GET['view'] ?? 'list') === 'map' ? 'map' : 'list';
    $categories = categories($pdo);
    [$baseLat, $baseLng, $area] = defaultSearchPoint($user);
    $jobRows = jobs($pdo, $filters);
    if (!empty($filters['nearby'])) {
        $jobRows = array_values(array_filter($jobRows, function (array $job) use ($baseLat, $baseLng, $filters): bool {
            $lat = nullableCoordinate($job['latitude'] ?? null);
            $lng = nullableCoordinate($job['longitude'] ?? null);
            return $lat === null || $lng === null || distanceKm($baseLat, $baseLng, $lat, $lng) <= (float) $filters['radius'];
        }));
    }
    $markers = listingMapMarkers($jobRows);
    $baseQuery = [
        'page' => 'jobs',
        'q' => $filters['q'],
        'category_id' => $filters['category_id'] ?: '',
        'nearby' => $filters['nearby'] ? 1 : '',
        'radius' => $filters['radius'],
    ];
    $listUrl = 'index.php?' . http_build_query(array_merge($baseQuery, ['view' => 'list']));
    $mapUrl = 'index.php?' . http_build_query(array_merge($baseQuery, ['view' => 'map']));
    ?>
    <main class="page-shell">
        <section class="page-heading">
            <div>
                <p class="eyebrow">Find local work</p>
                <h1>Open jobs near <?= h($area) ?></h1>
                <p class="page-subtitle">Search within <?= (int) $filters['radius'] ?>km of <?= h($area) ?>. Job locations show public area only.</p>
            </div>
            <?php if (!$user || ($user['user_type'] ?? '') === 'family'): ?>
                <a class="button button-primary" href="<?= $user ? 'index.php?page=account#post-job' : 'index.php?page=signup' ?>">Post a Job</a>
            <?php endif; ?>
        </section>

        <details class="mobile-filter-drawer">
            <summary><i class="fa-solid fa-sliders"></i> Filters</summary>
            <div class="filter-panel">
                <?php renderJobFilters($filters, $categories, 'mobile-job'); ?>
            </div>
        </details>

        <section class="browse-layout">
            <aside class="filter-panel desktop-filter">
                <?php renderJobFilters($filters, $categories); ?>
                <div class="filter-note">
                    <strong>Secure Pay enabled</strong>
                    <span>Payments and booking records are tracked in the backend.</span>
                </div>
            </aside>

            <div class="results-surface <?= $view === 'map' ? 'show-map' : 'show-list' ?>">
                <div class="mobile-view-toggle" aria-label="Choose results view">
                    <a class="<?= $view === 'list' ? 'is-active' : '' ?>" href="<?= h($listUrl) ?>">
                        <i class="fa-solid fa-list"></i>
                        List view
                    </a>
                    <a class="<?= $view === 'map' ? 'is-active' : '' ?>" href="<?= h($mapUrl) ?>">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Map view
                    </a>
                </div>

                <div class="listing-results-layout">
                    <div class="list-pane job-grid job-grid-wide">
                        <?php if (!$jobRows): ?>
                            <div class="empty-state">No jobs match that search yet.</div>
                        <?php endif; ?>
                        <?php foreach ($jobRows as $job): ?>
                            <?php renderJobCard($pdo, $job, $user); ?>
                        <?php endforeach; ?>
                    </div>

                    <aside class="map-pane">
                        <div
                            id="listings-map"
                            class="listings-map"
                            data-map-markers="<?= h(json_encode($markers, JSON_THROW_ON_ERROR)) ?>"
                            data-empty-message="No mapped jobs match these filters yet."
                        ></div>
                        <p class="map-privacy-note">Map points use approximate saved coordinates. Full addresses are never shown publicly.</p>
                    </aside>
                </div>
            </div>
        </section>
    </main>
    <?php
}

function renderMapPage(PDO $pdo, ?array $user): void
{
    $categoryId = (int) ($_GET['category_id'] ?? 0);
    $filters = ['q' => trim((string) ($_GET['q'] ?? '')), 'category_id' => $categoryId];
    $categories = categories($pdo);
    $jobRows = jobs($pdo, $filters);
    $markers = listingMapMarkers($jobRows);
    ?>
    <main class="page-shell">
        <section class="page-heading">
            <div>
                <p class="eyebrow">Ireland map</p>
                <h1>See open odd jobs across Ireland.</h1>
            </div>
        </section>

        <section class="map-layout">
            <aside class="filter-panel map-filter">
                <form action="index.php" method="get">
                    <input type="hidden" name="page" value="map">
                    <label for="map-search">Search</label>
                    <input id="map-search" name="q" value="<?= h($filters['q']) ?>" placeholder="Tutoring, pets, errands">
                    <label for="map-category">Category</label>
                    <select id="map-category" name="category_id">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['category_id'] ?>" <?= (int) $category['category_id'] === $categoryId ? 'selected' : '' ?>>
                                <?= h(labelize($category['category_name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button button-primary" type="submit">Update Map</button>
                </form>
                <p class="filter-note">Map points use approximate saved coordinates. Listings without coordinates remain in the list results.</p>
            </aside>

            <div class="map-panel">
                <div id="ireland-map" class="ireland-map" data-map-markers="<?= h(json_encode($markers, JSON_THROW_ON_ERROR)) ?>" data-empty-message="No mapped jobs match these filters yet."></div>
            </div>
        </section>
    </main>
    <?php
}

function renderJobCard(PDO $pdo, array $job, ?array $user): void
{
    $saved = $user ? in_array((int) $job['job_id'], savedJobIds($pdo, (int) $user['user_id']), true) : false;
    $application = ($user && $user['user_type'] === 'worker') ? applicationFor($pdo, (int) $job['job_id'], (int) $user['user_id']) : null;
    ?>
    <article class="job-card">
        <div class="card-topline">
            <span class="chip"><?= h(labelize($job['category_name'] ?? 'General help')) ?></span>
            <?php if ($user): ?>
                <form action="index.php" method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save_job">
                    <input type="hidden" name="job_id" value="<?= (int) $job['job_id'] ?>">
                    <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php?page=jobs') ?>">
                    <button class="icon-button <?= $saved ? 'is-saved' : '' ?>" type="submit" title="<?= $saved ? 'Unsave job' : 'Save job' ?>">
                        <i class="<?= $saved ? 'fa-solid' : 'fa-regular' ?> fa-bookmark"></i>
                    </button>
                </form>
            <?php else: ?>
                <a class="icon-button" href="index.php?page=login" title="Log in to save">
                    <i class="fa-regular fa-bookmark"></i>
                </a>
            <?php endif; ?>
        </div>
        <h3><a href="index.php?page=job&id=<?= (int) $job['job_id'] ?>"><?= h($job['title']) ?></a></h3>
        <p><?= h($job['description']) ?></p>
        <div class="job-meta">
            <span><i class="fa-solid fa-location-dot"></i> <?= h(publicArea($job['location'] ?? 'Balbriggan')) ?></span>
            <span><i class="fa-regular fa-calendar-check"></i> <?= h(scheduleFrequency($job['schedule_days'] ?? '', $job['frequency'] ?? 'Flexible')) ?></span>
            <span><i class="fa-solid fa-dollar-sign"></i> <?= money($job['pay']) ?>/hour</span>
        </div>
        <div class="job-meta">
            <span><i class="fa-regular fa-user"></i> Posted by <?= h($job['family_name'] ?? 'Household') ?></span>
            <span>Schedule: <?= h(scheduleLabel($job['schedule_days'] ?? '')) ?></span>
        </div>
        <div class="card-actions">
            <a class="button button-secondary" href="index.php?page=job&id=<?= (int) $job['job_id'] ?>">Details</a>
            <?php if (!$user): ?>
                <a class="button button-primary" href="index.php?page=login">Log In to Apply</a>
            <?php elseif ($user['user_type'] === 'worker'): ?>
                <?php if ($application): ?>
                    <span class="status-pill <?= h(statusClass($application['status_name'])) ?>"><?= h(labelize($application['status_name'])) ?> at <?= money($application['agreed_rate'] ?? $job['pay']) ?>/hour</span>
                <?php else: ?>
                    <form action="index.php" method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="apply_job">
                        <input type="hidden" name="job_id" value="<?= (int) $job['job_id'] ?>">
                        <button class="button button-primary" type="submit">Apply</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <span class="muted"><?= (int) ($job['application_count'] ?? 0) ?> applicants</span>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

function renderScheduleDisplay(?string $selectedDays): void
{
    $selected = array_flip(array_filter(array_map('trim', explode(',', (string) $selectedDays))));
    ?>
    <div class="mini-calendar week-selector week-display" aria-label="Weekly schedule">
        <?php foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $value => $label): ?>
            <span class="<?= isset($selected[$value]) ? 'is-selected' : '' ?>"><?= h($label) ?></span>
        <?php endforeach; ?>
    </div>
    <?php
}

function renderJobDetails(PDO $pdo, ?array $user): void
{
    $job = jobById($pdo, (int) ($_GET['id'] ?? 0));
    if (!$job) {
        echo '<main class="page-shell"><div class="empty-state">Job not found.</div></main>';
        return;
    }

    $applications = applicationsForJob($pdo, (int) $job['job_id']);
    $myApplication = ($user && $user['user_type'] === 'worker') ? applicationFor($pdo, (int) $job['job_id'], (int) $user['user_id']) : null;
    $isOwner = $user && (int) $job['family_id'] === (int) $user['user_id'];
    ?>
    <main class="page-shell">
        <section class="detail-layout">
            <article class="detail-main">
                <a class="back-link" href="index.php?page=jobs"><i class="fa-solid fa-arrow-left"></i> Back to jobs</a>
                <span class="chip"><?= h(labelize($job['category_name'] ?? 'General help')) ?></span>
                <h1><?= h($job['title']) ?></h1>
                <p class="lead"><?= h($job['description']) ?></p>
                <div class="detail-facts">
                    <div><span>Hourly rate</span><strong><?= money($job['pay']) ?>/hour</strong></div>
                    <div><span>Frequency</span><strong><?= h(scheduleFrequency($job['schedule_days'] ?? '', $job['frequency'])) ?></strong></div>
                    <div><span>Area</span><strong><?= h(publicArea($job['location'])) ?></strong></div>
                    <div><span>Schedule</span><strong><?= h(scheduleLabel($job['schedule_days'] ?? '')) ?></strong></div>
                </div>
                <?php if (!empty($job['preferred_start_date']) || !empty($job['notes'])): ?>
                    <div class="detail-facts detail-facts-compact">
                        <?php if (!empty($job['preferred_start_date'])): ?>
                            <div><span>Preferred start</span><strong><?= h(date('M j, Y', strtotime($job['preferred_start_date']))) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($job['notes'])): ?>
                            <div><span>Notes</span><strong><?= h($job['notes']) ?></strong></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <h2>About this family</h2>
                <div class="profile-line">
                    <?= avatar(['name' => $job['family_name'], 'avatar_url' => $job['family_avatar']], 'avatar') ?>
                    <div>
                        <strong><?= h($job['family_name']) ?></strong>
                        <span><?= h(publicArea($job['family_neighbourhood'] ?? 'Balbriggan')) ?></span>
                    </div>
                </div>

                <?php if ($isOwner): ?>
                    <h2>Applicants</h2>
                    <div class="applicant-list">
                        <?php if (!$applications): ?>
                            <div class="empty-state">No applicants yet.</div>
                        <?php endif; ?>
                        <?php foreach ($applications as $application): ?>
                            <div class="applicant-row">
                                <?= avatar(['name' => $application['worker_name'], 'avatar_url' => $application['avatar_url']], 'avatar') ?>
                                <div>
                                    <strong><?= h($application['worker_name']) ?></strong>
                                <span>Agreed rate: <?= money($application['agreed_rate'] ?? $job['pay']) ?>/hour</span>
                                </div>
                                <span class="status-pill <?= h(statusClass($application['status_name'])) ?>"><?= h(labelize($application['status_name'])) ?></span>
                                <?php if ($application['status_name'] === 'pending'): ?>
                                    <form action="index.php" method="post" class="inline-actions">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_application_status">
                                        <input type="hidden" name="application_id" value="<?= (int) $application['application_id'] ?>">
                                        <input type="hidden" name="redirect" value="index.php?page=job&id=<?= (int) $job['job_id'] ?>">
                                        <button name="status" value="accepted" class="button button-primary button-small" type="submit">Accept</button>
                                        <button name="status" value="rejected" class="button button-danger button-small" type="submit">Reject</button>
                                    </form>
                                <?php elseif ($application['status_name'] === 'accepted'): ?>
                                    <form action="index.php" method="post" class="inline-actions">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_application_status">
                                        <input type="hidden" name="application_id" value="<?= (int) $application['application_id'] ?>">
                                        <input type="hidden" name="redirect" value="index.php?page=job&id=<?= (int) $job['job_id'] ?>">
                                        <button name="status" value="cancelled" class="button button-danger button-small" type="submit">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <aside class="booking-panel">
                <div class="rate"><?= money($job['pay']) ?> <span>/ hour</span></div>
                <?php renderScheduleDisplay($job['schedule_days'] ?? ''); ?>
                <div class="next-opening">
                    <span>Next opening</span>
                    <strong><?= h(!empty($job['preferred_start_date']) ? date('M j, Y', strtotime($job['preferred_start_date'])) : formatDateTime($job['scheduled_at'])) ?></strong>
                </div>

                <?php if (!$user): ?>
                    <a class="button button-primary button-full" href="index.php?page=login">Log In to Apply</a>
                <?php elseif ($user['user_type'] === 'worker'): ?>
                    <?php if ($myApplication): ?>
                        <div class="status-pill <?= h(statusClass($myApplication['status_name'])) ?>">
                            <?= h(labelize($myApplication['status_name'])) ?> at <?= money($myApplication['agreed_rate'] ?? $job['pay']) ?>/hour
                        </div>
                    <?php else: ?>
                        <form action="index.php" method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="apply_job">
                            <input type="hidden" name="job_id" value="<?= (int) $job['job_id'] ?>">
                            <button class="button button-primary button-full" type="submit">Apply for This Job</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($user && (int) $job['family_id'] !== (int) $user['user_id']): ?>
                    <form action="index.php" method="post" class="message-box">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="receiver_id" value="<?= (int) $job['family_id'] ?>">
                        <input type="hidden" name="job_id" value="<?= (int) $job['job_id'] ?>">
                        <textarea name="body" rows="4" placeholder="Ask about this job"></textarea>
                        <button class="button button-secondary button-full" type="submit">Send Message</button>
                    </form>
                <?php endif; ?>
                <?php if (!$user): ?>
                    <a class="button button-secondary button-full" href="index.php?page=signup">Create Account to Message</a>
                <?php endif; ?>
            </aside>
        </section>
    </main>
    <?php
}

function renderMessagesPage(PDO $pdo, ?array $user): void
{
    requireUser($user);
    $threads = conversations($pdo, (int) $user['user_id']);
    $selectedId = (int) ($_GET['with'] ?? ($threads[0]['other_user_id'] ?? 0));
    $selectedUser = $selectedId > 0 ? userById($pdo, $selectedId) : null;
    $messages = $selectedId > 0 ? messagesWith($pdo, (int) $user['user_id'], $selectedId) : [];

    if ($selectedId > 0) {
        $markRead = $pdo->prepare("UPDATE messages SET read_at = CURRENT_TIMESTAMP WHERE receiver_id = :me AND sender_id = :other AND read_at IS NULL");
        $markRead->execute(['me' => (int) $user['user_id'], 'other' => $selectedId]);
    }

    $activeThread = null;
    foreach ($threads as $thread) {
        if ((int) $thread['other_user_id'] === $selectedId) {
            $activeThread = $thread;
            break;
        }
    }
    ?>
    <main class="messages-layout">
        <aside class="conversation-list">
            <h1>Messages</h1>
            <?php if (!$threads): ?>
                <div class="empty-state">No conversations yet.</div>
            <?php endif; ?>
            <?php foreach ($threads as $thread): ?>
                <a class="conversation <?= (int) $thread['other_user_id'] === $selectedId ? 'is-active' : '' ?>" href="index.php?page=messages&with=<?= (int) $thread['other_user_id'] ?>">
                    <?= avatar(['name' => $thread['name'], 'avatar_url' => $thread['avatar_url']], 'avatar') ?>
                    <span>
                        <strong><?= h($thread['name']) ?></strong>
                        <small><?= h(strlen((string) $thread['latest_body']) > 42 ? substr((string) $thread['latest_body'], 0, 39) . '...' : (string) $thread['latest_body']) ?></small>
                    </span>
                    <?php if ((int) $thread['unread'] > 0): ?>
                        <em><?= (int) $thread['unread'] ?></em>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </aside>

        <section class="thread-panel">
            <div class="secure-banner">
                <i class="fa-solid fa-lock"></i>
                <span><strong>Secure Pay Enabled:</strong> bookings and payments are stored once finalized.</span>
            </div>
            <?php if (!$selectedUser): ?>
                <div class="empty-state">Choose a conversation.</div>
            <?php else: ?>
                <div class="thread-scroll">
                    <?php foreach ($messages as $message): ?>
                        <div class="bubble <?= (int) $message['sender_id'] === (int) $user['user_id'] ? 'mine' : 'theirs' ?>">
                            <p><?= h($message['body']) ?></p>
                            <span><?= h(date('M j, g:i A', strtotime($message['created_at']))) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form action="index.php" method="post" class="composer">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="receiver_id" value="<?= (int) $selectedId ?>">
                    <input type="hidden" name="job_id" value="<?= (int) ($activeThread['job_id'] ?? 0) ?>">
                    <input name="body" placeholder="Write a message..." autocomplete="off">
                    <button class="button button-primary" type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            <?php endif; ?>
        </section>

        <aside class="thread-context">
            <?php if ($selectedUser): ?>
                <div class="context-profile">
                    <?= avatar($selectedUser, 'avatar avatar-xl') ?>
                    <h2><?= h($selectedUser['name']) ?></h2>
                    <p><?= h(publicRoleLabel($selectedUser['user_type'] ?? null)) ?> &middot; <?= h(publicArea($selectedUser['neighbourhood'] ?? 'Balbriggan')) ?></p>
                </div>
                <?php if ($activeThread && !empty($activeThread['job_title'])): ?>
                    <div class="context-job">
                        <span>Active job inquiry</span>
                        <h3><?= h($activeThread['job_title']) ?></h3>
                        <dl>
                            <dt>Agreed rate</dt><dd><?= money($activeThread['agreed_rate'] ?? $activeThread['pay']) ?>/hour</dd>
                            <dt>Schedule</dt><dd><?= h(scheduleLabel($activeThread['schedule_days'] ?? '')) ?></dd>
                            <dt>Area</dt><dd><?= h(publicArea($activeThread['location'] ?? 'Balbriggan')) ?></dd>
                        </dl>
                    </div>
                    <?php renderFinalizePayment($pdo, $user, $selectedUser, (int) $activeThread['job_id'], (float) ($activeThread['agreed_rate'] ?? $activeThread['pay'])); ?>
                <?php endif; ?>
                <a class="button button-secondary button-full" href="index.php?page=worker&id=<?= (int) $selectedUser['user_id'] ?>">View Profile</a>
            <?php endif; ?>
        </aside>
    </main>
    <?php
}

function renderFinalizePayment(PDO $pdo, array $user, array $selectedUser, int $jobId, float $pay): void
{
    if (($user['user_type'] ?? '') !== 'family') {
        return;
    }

    $stmt = $pdo->prepare("
        SELECT a.*, s.status_name, j.scheduled_at
        FROM applications a
        LEFT JOIN application_status s ON s.status_id = a.application_status_id
        LEFT JOIN jobs j ON j.job_id = a.job_id
        WHERE a.job_id = :job_id AND a.worker_id = :worker_id
        ORDER BY a.application_id DESC
        LIMIT 1
    ");
    $stmt->execute(['job_id' => $jobId, 'worker_id' => (int) $selectedUser['user_id']]);
    $application = $stmt->fetch();

    if (!$application || !in_array($application['status_name'], ['accepted', 'completed'], true)) {
        return;
    }
    ?>
    <form action="index.php" method="post" class="pay-box">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="finalize_payment">
        <input type="hidden" name="application_id" value="<?= (int) $application['application_id'] ?>">
        <input type="hidden" name="amount" value="<?= h((string) $pay) ?>">
        <input type="hidden" name="scheduled_for" value="<?= h(date('Y-m-d\TH:i', strtotime($application['scheduled_at'] ?? 'now'))) ?>">
        <input type="hidden" name="redirect" value="index.php?page=messages&with=<?= (int) $selectedUser['user_id'] ?>">
        <button class="button button-primary button-full" type="submit">Finalize Booking & Pay</button>
    </form>
    <?php
}

function renderAccountPage(PDO $pdo, ?array $user): void
{
    requireUser($user);
    $postedJobs = myPostedJobs($pdo, (int) $user['user_id']);
    $applications = myWorkerApplications($pdo, (int) $user['user_id']);
    $categories = categories($pdo);
    $workerCategories = ($user['user_type'] ?? '') === 'worker' ? workerCategories($pdo, (int) $user['user_id']) : [];
    $selectedCategoryIds = array_map('intval', array_column($workerCategories, 'category_id'));
    $categoryReason = $workerCategories[0]['reason'] ?? '';
    ?>
    <main class="page-shell">
        <section class="account-grid">
            <aside class="account-panel">
                <div class="context-profile">
                    <?= avatar($user, 'avatar avatar-xl') ?>
                    <h1><?= h($user['name']) ?></h1>
                    <p><?= ($user['user_type'] ?? '') === 'worker' ? 'Helper' : 'Help seeker' ?> &middot; <?= h(publicArea($user['neighbourhood'] ?? 'Balbriggan')) ?></p>
                </div>
                <p class="account-note">Your session is stored in a secure server-side session. Use Log Out when you are done on a shared computer.</p>
            </aside>

            <section class="account-main">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Account details</p>
                        <h2>Profile and location</h2>
                    </div>
                </div>
                <form action="index.php" method="post" class="form-grid">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <label>Name
                        <input name="name" value="<?= h($user['name']) ?>" required>
                    </label>
                    <?php renderAddressFields($user); ?>
                    <label class="span-2">About
                        <textarea name="bio" rows="3" placeholder="Short profile description"><?= h($user['bio'] ?? '') ?></textarea>
                    </label>
                    <?php if (($user['user_type'] ?? '') === 'worker'): ?>
                        <label>Hourly rate
                            <input name="hourly_rate" type="number" min="0" step="0.01" value="<?= h((string) ($user['hourly_rate'] ?? '')) ?>">
                        </label>
                        <label>Experience years
                            <input name="experience_years" type="number" min="0" step="1" value="<?= (int) ($user['experience_years'] ?? 0) ?>">
                        </label>
                        <label class="span-2">Availability
                            <input name="availability_note" value="<?= h($user['availability_note'] ?? '') ?>" placeholder="Weekday evenings, weekends, etc.">
                        </label>
                        <fieldset class="category-picker span-2">
                            <legend>Services offered</legend>
                            <?php foreach ($categories as $category): ?>
                                <label>
                                    <input name="category_ids[]" type="checkbox" value="<?= (int) $category['category_id'] ?>" <?= in_array((int) $category['category_id'], $selectedCategoryIds, true) ? 'checked' : '' ?>>
                                    <span><?= h(labelize($category['category_name'])) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <label class="span-2">Why these services?
                            <textarea name="category_reason" rows="3" placeholder="A short reason families can read on your profile"><?= h($categoryReason) ?></textarea>
                        </label>
                    <?php endif; ?>
                    <button class="button button-primary" type="submit">Save Profile</button>
                </form>

                <?php if (($user['user_type'] ?? '') === 'family'): ?>
                    <div class="section-heading" id="post-job">
                        <div>
                            <p class="eyebrow">Create listing</p>
                            <h2>Post a household job</h2>
                        </div>
                    </div>
                    <form action="index.php" method="post" class="form-grid">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="create_job">
                        <label class="span-2">Job title
                            <input name="title" required placeholder="Morning dog walk">
                        </label>
                        <label>Service / category
                            <select name="category_id">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['category_id'] ?>"><?= h(labelize($category['category_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Hourly rate
                            <input name="pay" type="number" min="0" step="0.01" required placeholder="15.00">
                        </label>
                        <label class="span-2">Description
                            <textarea name="description" rows="4" required placeholder="What do you need help with?"></textarea>
                        </label>
                        <?php renderAddressFields($user, 'job'); ?>
                        <fieldset class="schedule-picker span-2">
                            <legend>Schedule</legend>
                            <p>Select the days this job usually happens.</p>
                            <div class="mini-calendar week-selector">
                                <?php foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $value => $label): ?>
                                    <label>
                                        <input type="checkbox" name="schedule_days[]" value="<?= h($value) ?>">
                                        <span><?= h($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <label>Preferred start date
                            <input name="preferred_start_date" type="date">
                        </label>
                        <label>Specific date/time <span>optional</span>
                            <input name="scheduled_at" type="datetime-local">
                        </label>
                        <label class="span-2">Optional notes
                            <textarea name="notes" rows="3" placeholder="Anything useful about access, pets, parking, or timing."></textarea>
                        </label>
                        <label class="checkbox-line span-2">
                            <input name="is_recurring" type="checkbox">
                            <span>Recurring job</span>
                        </label>
                        <button class="button button-primary" type="submit">Publish Job</button>
                    </form>
                <?php endif; ?>

                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Your activity</p>
                        <h2><?= ($user['user_type'] ?? '') === 'worker' ? 'Applications' : 'Posted jobs' ?></h2>
                    </div>
                </div>

                <?php if (($user['user_type'] ?? '') === 'worker'): ?>
                    <div class="activity-list">
                        <?php foreach ($applications as $application): ?>
                            <a class="activity-row" href="index.php?page=job&id=<?= (int) $application['job_id'] ?>">
                                <span>
                                    <strong><?= h($application['title']) ?></strong>
                                    <small><?= h($application['family_name']) ?> &middot; <?= money($application['agreed_rate'] ?? $application['pay']) ?>/hour &middot; <?= h(scheduleFrequency($application['schedule_days'] ?? '', $application['frequency'] ?? 'Flexible')) ?></small>
                                </span>
                                <em class="status-pill <?= h(statusClass($application['status_name'])) ?>"><?= h(labelize($application['status_name'])) ?></em>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($postedJobs as $job): ?>
                            <a class="activity-row" href="index.php?page=job&id=<?= (int) $job['job_id'] ?>">
                                <span>
                                    <strong><?= h($job['title']) ?></strong>
                                    <small><?= h(labelize($job['category_name'])) ?> &middot; <?= (int) $job['application_count'] ?> applicants</small>
                                </span>
                                <em><?= money($job['pay']) ?></em>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>
    <?php
}

function renderWorkerProfile(PDO $pdo, ?array $user): void
{
    $workerId = (int) ($_GET['id'] ?? (($user['user_type'] ?? '') === 'worker' ? $user['user_id'] : 3));
    $worker = workerById($pdo, $workerId);
    if (!$worker) {
        echo '<main class="page-shell"><div class="empty-state">Helper profile not found.</div></main>';
        return;
    }
    $skills = workerCategories($pdo, (int) $worker['user_id']);
    ?>
    <main class="page-shell">
        <section class="worker-profile-layout">
            <article class="worker-profile-main">
                <div class="profile-hero">
                    <?= avatar($worker, 'profile-photo') ?>
                    <div>
                        <h1><?= h($worker['name']) ?></h1>
                        <p class="profile-subtitle">Household helper in <?= h(publicArea($worker['neighbourhood'] ?? 'Balbriggan')) ?></p>
                        <div class="profile-stats">
                            <span><strong><?= (int) $worker['experience_years'] ?>+ years</strong> experience</span>
                            <span><strong><?= money($worker['hourly_rate']) ?></strong> hourly rate</span>
                        </div>
                    </div>
                </div>

                <h2>About this helper</h2>
                <p class="lead"><?= h($worker['bio']) ?></p>

                <h2>Services offered</h2>
                <?php if ($skills): ?>
                    <div class="skill-list">
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-badge"><?= h(labelize($skill['category_name'])) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($skills[0]['reason'])): ?>
                        <p class="lead"><?= h($skills[0]['reason']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">This helper has not added services yet.</div>
                <?php endif; ?>

                <h2>Completed Work</h2>
                <div class="feedback-list">
                    <div class="empty-state">Booking feedback will appear here after real completed jobs.</div>
                </div>
            </article>

            <aside class="booking-panel">
                <div class="rate"><?= money($worker['hourly_rate']) ?> <span>/ hr</span></div>
                <div class="mini-calendar">
                    <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                    <strong>8</strong><strong>9</strong><strong>10</strong><strong>11</strong><strong>12</strong><strong>13</strong><strong>14</strong>
                </div>
                <div class="next-opening">
                    <span>Next opening</span>
                    <strong><?= h($worker['availability_note'] ?? 'This week') ?></strong>
                </div>
                <?php if ($user && (int) $worker['user_id'] !== (int) $user['user_id']): ?>
                    <form action="index.php" method="post" class="message-box">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="receiver_id" value="<?= (int) $worker['user_id'] ?>">
                        <textarea name="body" rows="4" placeholder="Ask about availability"></textarea>
                        <button class="button button-primary button-full" type="submit">Send Message</button>
                    </form>
                <?php endif; ?>
                <?php if (!$user): ?>
                    <a class="button button-primary button-full" href="index.php?page=login">Log In to Message</a>
                <?php endif; ?>
            </aside>
        </section>
    </main>
    <?php
}

function renderAuthPage(string $mode): void
{
    $isSignup = $mode === 'signup';
    $selectedType = ($_GET['type'] ?? '') === 'worker' ? 'worker' : (($_GET['type'] ?? '') === 'family' ? 'family' : '');
    if ($isSignup && $selectedType === '') {
        ?>
        <main class="page-shell auth-shell">
            <section class="auth-card path-card">
                <div>
                    <p class="eyebrow">Join LocalLoop</p>
                    <h1>What brings you here?</h1>
                    <p class="lead">Choose a path first so your account starts with the right profile and actions.</p>
                </div>
                <div class="path-options">
                    <a href="index.php?page=signup&type=family">
                        <i class="fa-solid fa-house-user"></i>
                        <strong>I need household help</strong>
                        <span>Post jobs, message helpers, and arrange bookings.</span>
                    </a>
                    <a href="index.php?page=signup&type=worker">
                        <i class="fa-solid fa-briefcase"></i>
                        <strong>I'm looking for household work</strong>
                        <span>Create a helper profile, add services, and apply for jobs.</span>
                    </a>
                </div>
                <p class="auth-switch">Already have an account? <a href="index.php?page=login">Log in</a></p>
            </section>
        </main>
        <?php
        return;
    }
    ?>
    <main class="page-shell auth-shell">
            <section class="auth-card">
                <?php if ($isSignup): ?>
                    <a class="back-link auth-back" href="index.php?page=signup"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <?php endif; ?>
                <div>
                <p class="eyebrow"><?= $isSignup ? 'Join LocalLoop' : 'Welcome back' ?></p>
                <h1><?= $isSignup ? ($selectedType === 'worker' ? 'Create your helper profile.' : 'Create your help-seeker account.') : 'Log in to continue.' ?></h1>
                <p class="lead"><?= $isSignup ? ($selectedType === 'worker' ? 'Add your services, rate, and availability after signup.' : 'Post household jobs and message helpers after signup.') : 'Use the email and password you signed up with.' ?></p>
            </div>
            <form action="index.php" method="post" class="auth-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $isSignup ? 'signup' : 'login' ?>">
                <?php if ($isSignup): ?>
                    <label>Name
                        <input name="name" required autocomplete="name" placeholder="Jane Stevenson">
                    </label>
                <?php endif; ?>
                <label>Email
                    <input name="email" type="email" required autocomplete="email" placeholder="you@example.com">
                </label>
                <label>Password
                    <input name="password" type="password" required autocomplete="<?= $isSignup ? 'new-password' : 'current-password' ?>" minlength="8" placeholder="At least 8 characters">
                </label>
                <?php if ($isSignup): ?>
                    <input type="hidden" name="user_type" value="<?= h($selectedType ?: 'family') ?>">
                    <?php renderAddressFields(['address_town' => 'Balbriggan', 'country' => 'Ireland']); ?>
                <?php endif; ?>
                <button class="button button-primary button-full" type="submit"><?= $isSignup ? 'Create Account' : 'Log In' ?></button>
            </form>
            <p class="auth-switch">
                <?= $isSignup ? 'Already have an account?' : 'Need an account?' ?>
                <a href="index.php?page=<?= $isSignup ? 'login' : 'signup' ?>"><?= $isSignup ? 'Log in' : 'Sign up' ?></a>
            </p>
        </section>
    </main>
    <?php
}

function renderDatabaseError(Throwable $error): void
{
    ?>
    <main class="page-shell">
        <div class="setup-error">
            <h1>Database connection needed</h1>
            <p>The PHP app is ready, but it needs the MySQL Docker service running to load jobs, users, messages, and bookings.</p>
            <code><?= h($error->getMessage()) ?></code>
        </div>
    </main>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalLoop</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php if ($databaseError): ?>
    <?php renderDatabaseError($databaseError); ?>
<?php else: ?>
    <?php renderHeader($pdo, $currentUser, $page, $notice); ?>
    <?php
    match ($page) {
        'helpers' => renderHelpersPage($pdo, $currentUser),
        'jobs' => renderJobsPage($pdo, $currentUser),
        'map' => renderMapPage($pdo, $currentUser),
        'job' => renderJobDetails($pdo, $currentUser),
        'how' => renderHowPage(),
        'messages' => renderMessagesPage($pdo, $currentUser),
        'account' => renderAccountPage($pdo, $currentUser),
        'worker' => renderWorkerProfile($pdo, $currentUser),
        'login' => renderAuthPage('login'),
        'signup' => renderAuthPage('signup'),
        default => renderHome($pdo, $currentUser),
    };
    ?>
    <?php renderFooter(); ?>
<?php endif; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(() => {
    document.querySelectorAll('.week-selector input[type="checkbox"]').forEach((input) => {
        const label = input.closest('label');
        const sync = () => label?.classList.toggle('is-selected', input.checked);
        sync();
        input.addEventListener('change', sync);
    });

    if (typeof L === 'undefined') return;

    document.querySelectorAll('[data-map-markers]').forEach((mapEl) => {
        const markers = JSON.parse(mapEl.dataset.mapMarkers || '[]');
        const map = L.map(mapEl).setView([53.1424, -7.6921], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const bounds = [];
        markers.forEach((marker) => {
            const lat = Number(marker.latitude);
            const lng = Number(marker.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const icon = L.divIcon({
                className: 'map-marker',
                html: `<span class="${marker.marker_type === 'helper' ? 'worker-marker' : 'job-marker'}"></span>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            L.marker([lat, lng], { icon })
                .addTo(map)
                .bindPopup(markerPopup(marker), { minWidth: 230 });
            bounds.push([lat, lng]);
        });

        if (bounds.length) {
            map.fitBounds(bounds, { padding: [34, 34], maxZoom: 12 });
        } else {
            const empty = L.control({ position: 'topright' });
            empty.onAdd = () => {
                const div = L.DomUtil.create('div', 'map-empty-note');
                div.textContent = mapEl.dataset.emptyMessage || 'No mapped listings yet.';
                return div;
            };
            empty.addTo(map);
        }

        setTimeout(() => map.invalidateSize(), 120);
    });

    function markerPopup(marker) {
        const title = escapeHtml(marker.title || 'Open job');
        const area = escapeHtml(marker.area || 'Ireland');
        const price = escapeHtml(marker.price || '');
        const category = escapeHtml(marker.category || 'Odd job');
        const url = escapeAttr(marker.url || 'index.php?page=jobs');
        const cta = marker.marker_type === 'helper' ? 'View profile' : 'View listing';
        const image = marker.image
            ? `<img class="map-popup-image" src="${escapeAttr(marker.image)}" alt="">`
            : `<span class="map-popup-fallback">${escapeHtml((marker.title || 'J').slice(0, 1).toUpperCase())}</span>`;

        return `
            <article class="map-popup-card">
                ${image}
                <div>
                    <strong>${title}</strong>
                    <span>${category}</span>
                    <span>${area}${price ? ` · ${price}` : ''}</span>
                    <a href="${url}">${cta}</a>
                </div>
            </article>
        `;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
})();
</script>
</body>
</html>
