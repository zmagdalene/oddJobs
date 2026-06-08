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

function renderHeader(PDO $pdo, ?array $user, string $page, ?string $notice): void
{
    $unread = $user ? unreadCount($pdo, (int) $user['user_id']) : 0;
    $neighbourhood = $user['neighbourhood'] ?? 'Greenwood Valley';
    $isWorker = ($user['user_type'] ?? '') === 'worker';
    $isFamily = ($user['user_type'] ?? '') === 'family';
    ?>
    <header class="topbar">
        <div class="topline">
            <span><i class="fa-solid fa-location-dot"></i> Your neighborhood: <strong><?= h($neighbourhood) ?></strong></span>
            <span>Local jobs, messages, bookings, and payments in one place</span>
        </div>
        <nav class="nav-shell" aria-label="Main navigation">
            <a class="brand" href="index.php">
                <span class="brand-mark"><i class="fa-solid fa-check"></i></span>
                <span>LocalLoop</span>
            </a>
            <div class="nav-links">
                <a class="<?= activePage('jobs', $page) ?>" href="index.php?page=jobs">Find Jobs</a>
                <?php if (!$user || $isFamily): ?>
                    <a class="<?= activePage('account', $page) ?>" href="<?= $user ? 'index.php?page=account#post-job' : 'index.php?page=signup' ?>">Post a Job</a>
                <?php endif; ?>
                <a class="<?= activePage('map', $page) ?>" href="index.php?page=map">Map</a>
                <a class="<?= activePage('messages', $page) ?>" href="<?= $user ? 'index.php?page=messages' : 'index.php?page=login' ?>">
                    Messages
                    <?php if ($unread > 0): ?>
                        <span class="badge"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
                <?php if (!$isWorker): ?>
                    <a class="<?= activePage('worker', $page) ?>" href="index.php?page=worker">Workers</a>
                <?php endif; ?>
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
            <a href="index.php?page=jobs">Find Jobs</a>
            <a href="index.php?page=map">Map</a>
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
    $workers = array_slice(usersByType($pdo, 'worker'), 0, 3);
    $heroImage = 'LocalLoop/profile_files/photo-1516733725897-1aa73b87c8e8';
    ?>
    <main>
        <section class="hero-band">
            <div class="hero-copy">
                <p class="eyebrow">Neighborhood relationships</p>
                <h1>Find reliable local help for family needs.</h1>
                <p class="hero-text">Connect with dependable neighbors for babysitting, dog walking, tutoring, errands, cleaning, and home support.</p>
                <form class="hero-search" action="index.php" method="get">
                    <input type="hidden" name="page" value="jobs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input name="q" type="search" placeholder="Search babysitting, tutoring, errands..." aria-label="Search jobs">
                    <button class="button button-primary" type="submit">Search</button>
                </form>
                <div class="hero-actions">
                    <a class="button button-primary" href="index.php?page=account#post-job">I Need Help</a>
                    <a class="button button-secondary" href="index.php?page=jobs">I Want to Work</a>
                </div>
            </div>
            <div class="hero-visual" aria-label="Local family support">
                <div class="info-pill"><i class="fa-solid fa-map-location-dot"></i> Ireland marketplace</div>
                <div class="worker-orbit">
                    <img class="hero-avatar" src="<?= h($heroImage) ?>" alt="Family walking together">
                    <div class="orbit-card orbit-card-top">
                        <strong><?= (int) $stats['workers'] ?> workers</strong>
                        <span>Available locally</span>
                    </div>
                    <div class="orbit-card orbit-card-bottom">
                        <strong><?= (int) $stats['jobs'] ?> open jobs</strong>
                        <span>Near <?= h($user['neighbourhood'] ?? 'Greenwood Valley') ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats-strip" aria-label="Marketplace stats">
            <div><strong><?= (int) $stats['jobs'] ?></strong><span>Open jobs</span></div>
            <div><strong><?= (int) $stats['workers'] ?></strong><span>Worker profiles</span></div>
            <div><strong><?= (int) $stats['messages'] ?></strong><span>Messages sent</span></div>
            <div><strong><?= (int) $stats['bookings'] ?></strong><span>Confirmed bookings</span></div>
        </section>

        <section class="section-shell">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Available opportunities</p>
                    <h2>Open jobs in your loop</h2>
                </div>
                <a class="text-link" href="index.php?page=jobs">View all</a>
            </div>
            <div class="job-grid">
                <?php foreach ($featuredJobs as $job): ?>
                    <?php renderJobCard($pdo, $job, $user); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section-shell">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Local profiles</p>
                    <h2>Workers nearby</h2>
                </div>
                <a class="text-link" href="index.php?page=worker">See profiles</a>
            </div>
            <div class="worker-grid">
                <?php foreach ($workers as $worker): ?>
                    <article class="worker-card">
                        <?= avatar($worker, 'avatar avatar-large') ?>
                        <div>
                            <h3><?= h($worker['name']) ?></h3>
                            <p><?= h($worker['bio']) ?></p>
                            <div class="meta-row">
                                <span><i class="fa-solid fa-location-dot"></i> <?= h($worker['neighbourhood']) ?></span>
                                <span><?= money($worker['hourly_rate']) ?>/hr</span>
                            </div>
                        </div>
                        <a class="button button-secondary" href="index.php?page=worker&id=<?= (int) $worker['user_id'] ?>">View Profile</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <?php
}

function renderJobsPage(PDO $pdo, ?array $user): void
{
    $filters = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'category_id' => (int) ($_GET['category_id'] ?? 0),
    ];
    $view = ($_GET['view'] ?? 'list') === 'map' ? 'map' : 'list';
    $jobRows = jobs($pdo, $filters);
    $markers = listingMapMarkers($jobRows);
    $categories = categories($pdo);
    $baseQuery = [
        'page' => 'jobs',
        'q' => $filters['q'],
        'category_id' => $filters['category_id'] ?: '',
    ];
    $listUrl = 'index.php?' . http_build_query(array_merge($baseQuery, ['view' => 'list']));
    $mapUrl = 'index.php?' . http_build_query(array_merge($baseQuery, ['view' => 'map']));
    ?>
    <main class="page-shell">
        <section class="page-heading">
            <div>
                <p class="eyebrow">Find local work</p>
                <h1>Open jobs near <?= h($user['neighbourhood'] ?? 'Greenwood Valley') ?></h1>
            </div>
            <?php if (!$user || ($user['user_type'] ?? '') === 'family'): ?>
                <a class="button button-primary" href="<?= $user ? 'index.php?page=account#post-job' : 'index.php?page=signup' ?>">Post a Job</a>
            <?php endif; ?>
        </section>

        <section class="browse-layout">
            <aside class="filter-panel">
                <form action="index.php" method="get">
                    <input type="hidden" name="page" value="jobs">
                    <label for="job-search">Search</label>
                    <input id="job-search" name="q" value="<?= h($filters['q']) ?>" placeholder="Tutoring, pets, errands">
                    <label for="job-category">Category</label>
                    <select id="job-category" name="category_id">
                        <option value="">All jobs</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['category_id'] ?>" <?= (int) $category['category_id'] === $filters['category_id'] ? 'selected' : '' ?>>
                                <?= h(labelize($category['category_name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button button-primary" type="submit">Filter Jobs</button>
                </form>
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
            <span><i class="fa-solid fa-location-dot"></i> <?= h($job['location'] ?? 'Greenwood Valley') ?></span>
            <span><i class="fa-regular fa-clock"></i> <?= h($job['frequency'] ?? 'One-time') ?></span>
            <span><i class="fa-solid fa-dollar-sign"></i> <?= money($job['pay']) ?></span>
        </div>
        <div class="card-actions">
            <a class="button button-secondary" href="index.php?page=job&id=<?= (int) $job['job_id'] ?>">Details</a>
            <?php if (!$user): ?>
                <a class="button button-primary" href="index.php?page=login">Log In to Apply</a>
            <?php elseif ($user['user_type'] === 'worker'): ?>
                <?php if ($application): ?>
                    <span class="status-pill <?= h(statusClass($application['status_name'])) ?>"><?= h(labelize($application['status_name'])) ?></span>
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
                    <div><span>Rate</span><strong><?= money($job['pay']) ?></strong></div>
                    <div><span>Frequency</span><strong><?= h($job['frequency']) ?></strong></div>
                    <div><span>Location</span><strong><?= h($job['location']) ?></strong></div>
                    <div><span>Schedule</span><strong><?= h(formatDateTime($job['scheduled_at'])) ?></strong></div>
                </div>

                <h2>About this family</h2>
                <div class="profile-line">
                    <?= avatar(['name' => $job['family_name'], 'avatar_url' => $job['family_avatar']], 'avatar') ?>
                    <div>
                        <strong><?= h($job['family_name']) ?></strong>
                        <span><?= h($job['family_neighbourhood']) ?></span>
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
                                <span><?= money($application['hourly_rate']) ?>/hr</span>
                                </div>
                                <span class="status-pill <?= h(statusClass($application['status_name'])) ?>"><?= h(labelize($application['status_name'])) ?></span>
                                <form action="index.php" method="post" class="inline-actions">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_application_status">
                                    <input type="hidden" name="application_id" value="<?= (int) $application['application_id'] ?>">
                                    <input type="hidden" name="redirect" value="index.php?page=job&id=<?= (int) $job['job_id'] ?>">
                                    <button name="status" value="accepted" class="button button-primary button-small" type="submit">Accept</button>
                                    <button name="status" value="rejected" class="button button-danger button-small" type="submit">Reject</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <aside class="booking-panel">
                <div class="rate"><?= money($job['pay']) ?> <span>/ job</span></div>
                <div class="mini-calendar">
                    <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                    <strong>8</strong><strong>9</strong><strong>10</strong><strong>11</strong><strong>12</strong><strong>13</strong><strong>14</strong>
                </div>
                <div class="next-opening">
                    <span>Next opening</span>
                    <strong><?= h(formatDateTime($job['scheduled_at'])) ?></strong>
                </div>

                <?php if (!$user): ?>
                    <a class="button button-primary button-full" href="index.php?page=login">Log In to Apply</a>
                <?php elseif ($user['user_type'] === 'worker'): ?>
                    <?php if ($myApplication): ?>
                        <div class="status-pill <?= h(statusClass($myApplication['status_name'])) ?>"><?= h(labelize($myApplication['status_name'])) ?></div>
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
                    <p><?= h(labelize($selectedUser['user_type'] ?? 'member')) ?> &middot; <?= h($selectedUser['neighbourhood']) ?></p>
                </div>
                <?php if ($activeThread && !empty($activeThread['job_title'])): ?>
                    <div class="context-job">
                        <span>Active job inquiry</span>
                        <h3><?= h($activeThread['job_title']) ?></h3>
                        <dl>
                            <dt>Rate</dt><dd><?= money($activeThread['pay']) ?></dd>
                            <dt>Frequency</dt><dd><?= h($activeThread['frequency']) ?></dd>
                            <dt>Location</dt><dd><?= h($activeThread['location']) ?></dd>
                        </dl>
                    </div>
                    <?php renderFinalizePayment($pdo, $user, $selectedUser, (int) $activeThread['job_id'], (float) $activeThread['pay']); ?>
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
                    <p><?= h(labelize($user['user_type'] ?? 'member')) ?> &middot; <?= h($user['neighbourhood']) ?></p>
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
                    <label>Neighborhood or town
                        <input name="neighbourhood" value="<?= h($user['neighbourhood'] ?? '') ?>" required>
                    </label>
                    <label class="span-2">Full address
                        <input name="full_address" value="<?= h($user['full_address'] ?? '') ?>" placeholder="Street, town, county, Ireland">
                    </label>
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
                            <legend>Odd job categories</legend>
                            <?php foreach ($categories as $category): ?>
                                <label>
                                    <input name="category_ids[]" type="checkbox" value="<?= (int) $category['category_id'] ?>" <?= in_array((int) $category['category_id'], $selectedCategoryIds, true) ? 'checked' : '' ?>>
                                    <span><?= h(labelize($category['category_name'])) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <label class="span-2">Why these categories?
                            <textarea name="category_reason" rows="3" placeholder="A short reason families can read on your profile"><?= h($categoryReason) ?></textarea>
                        </label>
                    <?php endif; ?>
                    <button class="button button-primary" type="submit">Save Profile</button>
                </form>

                <?php if (($user['user_type'] ?? '') === 'family'): ?>
                    <div class="section-heading" id="post-job">
                        <div>
                            <p class="eyebrow">Create listing</p>
                            <h2>Post a neighborhood job</h2>
                        </div>
                    </div>
                    <form action="index.php" method="post" class="form-grid">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="create_job">
                        <label>Job title
                            <input name="title" required placeholder="Morning dog walk">
                        </label>
                        <label>Category
                            <select name="category_id">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['category_id'] ?>"><?= h(labelize($category['category_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Pay
                            <input name="pay" type="number" min="0" step="0.01" required placeholder="25.00">
                        </label>
                        <label>Frequency
                            <input name="frequency" placeholder="One-time, weekly, 3x / week">
                        </label>
                        <label>Town or area
                            <input name="location" value="<?= h($user['neighbourhood'] ?? 'Ireland') ?>">
                        </label>
                        <label>Full address
                            <input name="full_address" value="<?= h($user['full_address'] ?? '') ?>" placeholder="Street, town, county, Ireland">
                        </label>
                        <label>Schedule
                            <input name="scheduled_at" type="datetime-local">
                        </label>
                        <label class="span-2">Description
                            <textarea name="description" rows="4" required placeholder="What do you need help with?"></textarea>
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
                                    <small><?= h($application['family_name']) ?> &middot; <?= money($application['pay']) ?></small>
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
        echo '<main class="page-shell"><div class="empty-state">Worker profile not found.</div></main>';
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
                        <p class="profile-subtitle"><?= h(labelize($worker['user_type'])) ?> in <?= h($worker['neighbourhood']) ?></p>
                        <div class="profile-stats">
                            <span><strong><?= (int) $worker['experience_years'] ?>+ years</strong> experience</span>
                            <span><strong><?= money($worker['hourly_rate']) ?></strong> hourly rate</span>
                        </div>
                    </div>
                </div>

                <h2>About Me</h2>
                <p class="lead"><?= h($worker['bio']) ?></p>

                <h2>Odd Job Categories</h2>
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
                    <div class="empty-state">This worker has not added categories yet.</div>
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
    ?>
    <main class="page-shell auth-shell">
        <section class="auth-card">
            <div>
                <p class="eyebrow"><?= $isSignup ? 'Join LocalLoop' : 'Welcome back' ?></p>
                <h1><?= $isSignup ? 'Create your account.' : 'Log in to continue.' ?></h1>
                <p class="lead"><?= $isSignup ? 'Sign up as a family to post jobs, or as a worker to apply and message families.' : 'Use the email and password you signed up with.' ?></p>
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
                    <label>Account type
                        <select name="user_type">
                            <option value="family">Family - I need help</option>
                            <option value="worker">Worker - I want jobs</option>
                        </select>
                    </label>
                    <label>Neighborhood
                        <input name="neighbourhood" value="Greenwood Valley" required>
                    </label>
                    <label class="span-2">Full address
                        <input name="full_address" placeholder="Street, town, county, Ireland">
                    </label>
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
        'jobs' => renderJobsPage($pdo, $currentUser),
        'map' => renderMapPage($pdo, $currentUser),
        'job' => renderJobDetails($pdo, $currentUser),
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
                html: '<span class="job-marker"></span>',
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
                    <a href="${url}">View listing</a>
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
