<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Dublin');

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'mysql-container';
    $name = getenv('DB_DATABASE') ?: 'local_jobs';
    $user = getenv('DB_USERNAME') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: (getenv('MYSQL_ROOT_PASSWORD') ?: 'nosecret');

    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    ensureAppSchema($pdo);

    return $pdo;
}

function ensureAppSchema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo->exec("SET time_zone = '+00:00'");

    addColumnIfMissing($pdo, 'jobs', 'location', "varchar(120) DEFAULT 'Greenwood Valley'");
    addColumnIfMissing($pdo, 'jobs', 'full_address', "varchar(255) NULL");
    addColumnIfMissing($pdo, 'jobs', 'latitude', "decimal(10,7) NULL");
    addColumnIfMissing($pdo, 'jobs', 'longitude', "decimal(10,7) NULL");
    addColumnIfMissing($pdo, 'jobs', 'frequency', "varchar(80) DEFAULT 'One-time'");
    addColumnIfMissing($pdo, 'jobs', 'scheduled_at', "datetime NULL");
    addColumnIfMissing($pdo, 'jobs', 'is_recurring', "tinyint(1) NOT NULL DEFAULT 0");
    addColumnIfMissing($pdo, 'jobs', 'status', "varchar(20) NOT NULL DEFAULT 'open'");
    addColumnIfMissing($pdo, 'jobs', 'created_at', "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

    addColumnIfMissing($pdo, 'users', 'neighbourhood', "varchar(120) DEFAULT 'Greenwood Valley'");
    addColumnIfMissing($pdo, 'users', 'full_address', "varchar(255) NULL");
    addColumnIfMissing($pdo, 'users', 'latitude', "decimal(10,7) NULL");
    addColumnIfMissing($pdo, 'users', 'longitude', "decimal(10,7) NULL");
    addColumnIfMissing($pdo, 'users', 'bio', "text NULL");
    addColumnIfMissing($pdo, 'users', 'hourly_rate', "decimal(6,2) NULL");
    addColumnIfMissing($pdo, 'users', 'experience_years', "int DEFAULT 1");
    addColumnIfMissing($pdo, 'users', 'avatar_url', "varchar(255) NULL");
    addColumnIfMissing($pdo, 'users', 'availability_note', "varchar(160) NULL");
    addColumnIfMissing($pdo, 'users', 'password_hash', "varchar(255) NULL");
    addColumnIfMissing($pdo, 'users', 'created_at', "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            message_id int NOT NULL AUTO_INCREMENT,
            job_id int DEFAULT NULL,
            sender_id int NOT NULL,
            receiver_id int NOT NULL,
            body text NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            read_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (message_id),
            KEY messages_job_id_idx (job_id),
            KEY messages_sender_id_idx (sender_id),
            KEY messages_receiver_id_idx (receiver_id),
            CONSTRAINT messages_job_fk FOREIGN KEY (job_id) REFERENCES jobs (job_id) ON DELETE SET NULL,
            CONSTRAINT messages_sender_fk FOREIGN KEY (sender_id) REFERENCES users (user_id) ON DELETE CASCADE,
            CONSTRAINT messages_receiver_fk FOREIGN KEY (receiver_id) REFERENCES users (user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS saved_jobs (
            saved_job_id int NOT NULL AUTO_INCREMENT,
            user_id int NOT NULL,
            job_id int NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (saved_job_id),
            UNIQUE KEY saved_jobs_unique (user_id, job_id),
            KEY saved_jobs_job_id_idx (job_id),
            CONSTRAINT saved_jobs_user_fk FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
            CONSTRAINT saved_jobs_job_fk FOREIGN KEY (job_id) REFERENCES jobs (job_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            booking_id int NOT NULL AUTO_INCREMENT,
            application_id int NOT NULL,
            scheduled_for datetime DEFAULT NULL,
            booking_status varchar(30) NOT NULL DEFAULT 'proposed',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (booking_id),
            KEY bookings_application_id_idx (application_id),
            CONSTRAINT bookings_application_fk FOREIGN KEY (application_id) REFERENCES applications (application_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            payment_id int NOT NULL AUTO_INCREMENT,
            application_id int NOT NULL,
            amount decimal(8,2) NOT NULL,
            payment_status varchar(30) NOT NULL DEFAULT 'authorized',
            paid_at timestamp NULL DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (payment_id),
            KEY payments_application_id_idx (application_id),
            CONSTRAINT payments_application_fk FOREIGN KEY (application_id) REFERENCES applications (application_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_categories (
            user_category_id int NOT NULL AUTO_INCREMENT,
            user_id int NOT NULL,
            category_id int NOT NULL,
            reason varchar(255) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_category_id),
            UNIQUE KEY user_categories_unique (user_id, category_id),
            KEY user_categories_category_id_idx (category_id),
            CONSTRAINT user_categories_user_fk FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
            CONSTRAINT user_categories_category_fk FOREIGN KEY (category_id) REFERENCES job_category (category_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");

    seedAppData($pdo);
    $done = true;
}

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $stmt->execute(['table' => $table, 'column' => $column]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function seedAppData(PDO $pdo): void
{
    $pdo->exec("
        INSERT IGNORE INTO application_status (status_id, status_name) VALUES
        (1, 'pending'),
        (2, 'accepted'),
        (3, 'rejected'),
        (4, 'completed')
    ");

    updateUser($pdo, 1, [
        'name' => 'Sarah Murphy',
        'email' => 'sarahMurphy6@gmail.com',
        'neighbourhood' => 'Greenwood Valley',
        'full_address' => 'Greenwood Valley, Dublin, Ireland',
        'latitude' => 53.349805,
        'longitude' => -6.260310,
        'bio' => null,
        'avatar_url' => null,
    ]);
    updateUser($pdo, 2, [
        'name' => "John O'Brian",
        'email' => 'johnOBrian454@gmail.com',
        'neighbourhood' => 'Greenwood Valley',
        'full_address' => 'Greenwood Valley, Dublin, Ireland',
        'latitude' => 53.344000,
        'longitude' => -6.267000,
        'bio' => null,
        'avatar_url' => null,
    ]);
    updateUser($pdo, 3, [
        'name' => 'Emma Kelly',
        'email' => 'emmaKelly09@gmail.com',
        'neighbourhood' => 'Greenwood Valley',
        'full_address' => 'Greenwood Valley, Dublin, Ireland',
        'latitude' => 53.351500,
        'longitude' => -6.255500,
        'bio' => 'Available for babysitting and tutoring.',
        'hourly_rate' => 22.00,
        'experience_years' => 5,
        'availability_note' => 'Next opening Monday at 3:30 PM',
        'avatar_url' => 'LocalLoop/profile_files/photo-1494790108377-be9c29b29330',
    ]);
    updateUser($pdo, 4, [
        'name' => 'Liam Byrne',
        'email' => 'liamByrne123@gmail.com',
        'neighbourhood' => 'Maple Court',
        'full_address' => 'Maple Court, Cork, Ireland',
        'latitude' => 51.898500,
        'longitude' => -8.475600,
        'bio' => 'Available for dog walking, cleaning, and garden help.',
        'hourly_rate' => 18.50,
        'experience_years' => 4,
        'availability_note' => 'Weekday mornings available',
        'avatar_url' => 'LocalLoop/profile_files/photo-1507003211169-0a1dd7228f2d',
    ]);
    updateUser($pdo, 5, [
        'name' => 'Aoife Nolan',
        'email' => 'aoifeNolan57@gmail.com',
        'neighbourhood' => 'Oak Lane',
        'full_address' => 'Oak Lane, Galway, Ireland',
        'latitude' => 53.270700,
        'longitude' => -9.056800,
        'bio' => 'Available for errands, pet sitting, and after-school support.',
        'hourly_rate' => 20.00,
        'experience_years' => 3,
        'availability_note' => 'Afternoons and weekends',
        'avatar_url' => 'LocalLoop/profile_files/photo-1544005313-94ddf0286df2',
    ]);

    setWorkerCategories($pdo, 3, [1, 2], 'Childcare and homework support');
    setWorkerCategories($pdo, 4, [3, 4, 5], 'Outdoor, cleaning, and pet routines');
    setWorkerCategories($pdo, 5, [5, 6], 'Errands, pets, and family support');

    upsertJobDetails($pdo, 1, 'Greenwood Valley', 'Greenwood Valley, Dublin, Ireland', 'One evening', '2026-06-12 18:00:00', 0, 53.349805, -6.260310);
    upsertJobDetails($pdo, 2, 'Greenwood Valley', 'Greenwood Valley, Dublin, Ireland', '3x / Week', '2026-06-10 08:30:00', 1, 53.344000, -6.267000);
    upsertJobDetails($pdo, 3, 'Oak Lane', 'Oak Lane, Galway, Ireland', 'Weekly', '2026-06-13 16:30:00', 1, 53.270700, -9.056800);
    upsertJobDetails($pdo, 4, 'Maple Court', 'Maple Court, Cork, Ireland', 'One-time', '2026-06-15 10:00:00', 0, 51.898500, -8.475600);

    insertJobIfMissing($pdo, [
        'category_id' => 4,
        'title' => 'Weekend Garden Reset',
        'description' => 'Mow lawn, tidy borders, and bag garden waste before Sunday afternoon.',
        'pay' => 55.00,
        'family_id' => 1,
        'location' => 'Greenwood Valley',
        'full_address' => 'Greenwood Valley, Dublin, Ireland',
        'latitude' => 53.349805,
        'longitude' => -6.260310,
        'frequency' => 'One-time',
        'scheduled_at' => '2026-06-14 09:00:00',
        'is_recurring' => 0,
    ]);
    insertJobIfMissing($pdo, [
        'category_id' => 6,
        'title' => 'After-School Errands',
        'description' => 'Collect groceries and drop a parcel twice a week for a busy family.',
        'pay' => 28.00,
        'family_id' => 2,
        'location' => 'Greenwood Valley',
        'full_address' => 'Greenwood Valley, Dublin, Ireland',
        'latitude' => 53.344000,
        'longitude' => -6.267000,
        'frequency' => '2x / Week',
        'scheduled_at' => '2026-06-11 15:45:00',
        'is_recurring' => 1,
    ]);

}

function updateUser(PDO $pdo, int $id, array $data): void
{
    $sets = [];
    $params = ['user_id' => $id];
    foreach ($data as $column => $value) {
        $sets[] = "`{$column}` = :{$column}";
        $params[$column] = $value;
    }

    $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $sets) . " WHERE user_id = :user_id");
    $stmt->execute($params);
}

function upsertJobDetails(PDO $pdo, int $id, string $location, string $fullAddress, string $frequency, string $scheduledAt, int $isRecurring, float $latitude, float $longitude): void
{
    $stmt = $pdo->prepare("
        UPDATE jobs
        SET location = :location,
            full_address = :full_address,
            latitude = :latitude,
            longitude = :longitude,
            frequency = :frequency,
            scheduled_at = :scheduled_at,
            is_recurring = :is_recurring,
            status = 'open'
        WHERE job_id = :job_id
    ");
    $stmt->execute([
        'location' => $location,
        'full_address' => $fullAddress,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'frequency' => $frequency,
        'scheduled_at' => $scheduledAt,
        'is_recurring' => $isRecurring,
        'job_id' => $id,
    ]);
}

function insertJobIfMissing(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE title = :title");
    $stmt->execute(['title' => $data['title']]);

    if ((int) $stmt->fetchColumn() > 0) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO jobs (category_id, title, description, pay, family_id, location, full_address, latitude, longitude, frequency, scheduled_at, is_recurring, status)
        VALUES (:category_id, :title, :description, :pay, :family_id, :location, :full_address, :latitude, :longitude, :frequency, :scheduled_at, :is_recurring, 'open')
    ");
    $stmt->execute($data);
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|string|null $amount): string
{
    return '$' . number_format((float) $amount, 2);
}

function labelize(?string $value): string
{
    return ucwords(str_replace('_', ' ', (string) $value));
}

function currentUser(PDO $pdo): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT u.*, t.user_type
        FROM users u
        LEFT JOIN user_type t ON t.user_type_id = u.user_type_id
        WHERE u.user_id = :id
    ");
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function requireUser(?array $user): void
{
    if (!$user) {
        flash('Please log in or create an account first.');
        redirectTo('index.php?page=login');
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!$token || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        flash('Your session expired. Please try again.');
        redirectTo($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

function allUsers(PDO $pdo): array
{
    return $pdo->query("
        SELECT u.*, t.user_type
        FROM users u
        LEFT JOIN user_type t ON t.user_type_id = u.user_type_id
        ORDER BY t.user_type, u.name
    ")->fetchAll();
}

function usersByType(PDO $pdo, string $type): array
{
    $stmt = $pdo->prepare("
        SELECT u.*, t.user_type
        FROM users u
        JOIN user_type t ON t.user_type_id = u.user_type_id
        WHERE t.user_type = :type
        ORDER BY u.name
    ");
    $stmt->execute(['type' => $type]);
    return $stmt->fetchAll();
}

function unreadCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :id AND read_at IS NULL");
    $stmt->execute(['id' => $userId]);
    return (int) $stmt->fetchColumn();
}

function categories(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM job_category ORDER BY category_name")->fetchAll();
}

function workerCategories(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT c.category_id, c.category_name, uc.reason
        FROM user_categories uc
        JOIN job_category c ON c.category_id = uc.category_id
        WHERE uc.user_id = :user_id
        ORDER BY c.category_name
    ");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function setWorkerCategories(PDO $pdo, int $userId, array $categoryIds, ?string $reason = null): void
{
    $pdo->prepare("DELETE FROM user_categories WHERE user_id = :user_id")->execute(['user_id' => $userId]);
    $cleanIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
    if (!$cleanIds) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_categories (user_id, category_id, reason)
        VALUES (:user_id, :category_id, :reason)
    ");
    foreach ($cleanIds as $categoryId) {
        $stmt->execute([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'reason' => $reason ?: null,
        ]);
    }
}

function jobs(PDO $pdo, array $filters = []): array
{
    $where = ["j.status = 'open'"];
    $params = [];

    if (!empty($filters['category_id'])) {
        $where[] = 'j.category_id = :category_id';
        $params['category_id'] = (int) $filters['category_id'];
    }

    if (!empty($filters['q'])) {
        $where[] = '(j.title LIKE :query_title OR j.description LIKE :query_description OR j.location LIKE :query_location OR c.category_name LIKE :query_category)';
        $query = '%' . $filters['q'] . '%';
        $params['query_title'] = $query;
        $params['query_description'] = $query;
        $params['query_location'] = $query;
        $params['query_category'] = $query;
    }

    $sql = "
        SELECT j.*,
               c.category_name,
               u.name AS family_name,
               u.neighbourhood AS family_neighbourhood,
               u.avatar_url AS family_avatar,
               (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS application_count
        FROM jobs j
        LEFT JOIN job_category c ON c.category_id = j.category_id
        LEFT JOIN users u ON u.user_id = j.family_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY j.created_at DESC, j.job_id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function nullableCoordinate(mixed $value): ?float
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return null;
    }

    return (float) $value;
}

function publicMapCoordinate(float $value, int $seed, int $axis): float
{
    $spread = (($seed * (17 + $axis * 11)) % 9) - 4;
    return round($value + ($spread * 0.0012), 7);
}

function listingMapMarkers(array $jobs): array
{
    $markers = [];
    foreach ($jobs as $job) {
        $latitude = nullableCoordinate($job['latitude'] ?? null);
        $longitude = nullableCoordinate($job['longitude'] ?? null);
        if ($latitude === null || $longitude === null) {
            continue;
        }

        $jobId = (int) $job['job_id'];
        $area = trim((string) ($job['location'] ?? $job['family_neighbourhood'] ?? 'Ireland'));
        $markers[] = [
            'marker_type' => 'job',
            'id' => $jobId,
            'title' => (string) ($job['title'] ?? 'Open job'),
            'area' => $area ?: 'Ireland',
            'latitude' => publicMapCoordinate($latitude, $jobId, 0),
            'longitude' => publicMapCoordinate($longitude, $jobId, 1),
            'price' => money($job['pay'] ?? 0),
            'category' => labelize($job['category_name'] ?? 'General help'),
            'image' => (string) ($job['family_avatar'] ?? ''),
            'url' => 'index.php?page=job&id=' . $jobId,
        ];
    }

    return $markers;
}

function jobById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT j.*,
               c.category_name,
               u.name AS family_name,
               u.email AS family_email,
               u.neighbourhood AS family_neighbourhood,
               u.avatar_url AS family_avatar
        FROM jobs j
        LEFT JOIN job_category c ON c.category_id = j.category_id
        LEFT JOIN users u ON u.user_id = j.family_id
        WHERE j.job_id = :id
    ");
    $stmt->execute(['id' => $id]);
    $job = $stmt->fetch();
    return $job ?: null;
}

function applicationsForJob(PDO $pdo, int $jobId): array
{
    $stmt = $pdo->prepare("
        SELECT a.*,
               s.status_name,
               u.name AS worker_name,
               u.email AS worker_email,
               u.hourly_rate,
               u.avatar_url
        FROM applications a
        LEFT JOIN application_status s ON s.status_id = a.application_status_id
        LEFT JOIN users u ON u.user_id = a.worker_id
        WHERE a.job_id = :job_id
        ORDER BY FIELD(s.status_name, 'accepted', 'pending', 'completed', 'rejected'), a.application_id DESC
    ");
    $stmt->execute(['job_id' => $jobId]);
    return $stmt->fetchAll();
}

function applicationFor(PDO $pdo, int $jobId, int $workerId): ?array
{
    $stmt = $pdo->prepare("
        SELECT a.*, s.status_name
        FROM applications a
        LEFT JOIN application_status s ON s.status_id = a.application_status_id
        WHERE a.job_id = :job_id AND a.worker_id = :worker_id
    ");
    $stmt->execute(['job_id' => $jobId, 'worker_id' => $workerId]);
    $application = $stmt->fetch();
    return $application ?: null;
}

function savedJobIds(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("SELECT job_id FROM saved_jobs WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'job_id'));
}

function workerById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.*, t.user_type
        FROM users u
        JOIN user_type t ON t.user_type_id = u.user_type_id
        WHERE u.user_id = :id AND t.user_type = 'worker'
    ");
    $stmt->execute(['id' => $id]);
    $worker = $stmt->fetch();
    return $worker ?: null;
}

function dashboardStats(PDO $pdo): array
{
    return [
        'jobs' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn(),
        'workers' => (int) $pdo->query("
            SELECT COUNT(*)
            FROM users u
            JOIN user_type t ON t.user_type_id = u.user_type_id
            WHERE t.user_type = 'worker'
        ")->fetchColumn(),
        'messages' => (int) $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
        'bookings' => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn(),
    ];
}

function conversations(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT m.*,
               sender.name AS sender_name,
               receiver.name AS receiver_name,
               sender.avatar_url AS sender_avatar,
               receiver.avatar_url AS receiver_avatar,
               j.title AS job_title,
               j.pay,
               j.frequency,
               j.location
        FROM messages m
        LEFT JOIN users sender ON sender.user_id = m.sender_id
        LEFT JOIN users receiver ON receiver.user_id = m.receiver_id
        LEFT JOIN jobs j ON j.job_id = m.job_id
        WHERE m.sender_id = :sender_id OR m.receiver_id = :receiver_id
        ORDER BY m.created_at DESC, m.message_id DESC
    ");
    $stmt->execute(['sender_id' => $userId, 'receiver_id' => $userId]);

    $threads = [];
    foreach ($stmt->fetchAll() as $message) {
        $otherId = (int) ($message['sender_id'] === $userId ? $message['receiver_id'] : $message['sender_id']);
        if (!isset($threads[$otherId])) {
            $threads[$otherId] = [
                'other_user_id' => $otherId,
                'name' => $message['sender_id'] === $userId ? $message['receiver_name'] : $message['sender_name'],
                'avatar_url' => $message['sender_id'] === $userId ? $message['receiver_avatar'] : $message['sender_avatar'],
                'latest_body' => $message['body'],
                'latest_at' => $message['created_at'],
                'job_id' => $message['job_id'],
                'job_title' => $message['job_title'],
                'pay' => $message['pay'],
                'frequency' => $message['frequency'],
                'location' => $message['location'],
                'unread' => 0,
            ];
        }
        if ((int) $message['receiver_id'] === $userId && $message['read_at'] === null) {
            $threads[$otherId]['unread']++;
        }
    }

    return array_values($threads);
}

function messagesWith(PDO $pdo, int $userId, int $otherUserId): array
{
    $stmt = $pdo->prepare("
        SELECT m.*, sender.name AS sender_name
        FROM messages m
        LEFT JOIN users sender ON sender.user_id = m.sender_id
        WHERE (m.sender_id = :user_sender_id AND m.receiver_id = :other_receiver_id)
           OR (m.sender_id = :other_sender_id AND m.receiver_id = :user_receiver_id)
        ORDER BY m.created_at ASC, m.message_id ASC
    ");
    $stmt->execute([
        'user_sender_id' => $userId,
        'other_receiver_id' => $otherUserId,
        'other_sender_id' => $otherUserId,
        'user_receiver_id' => $userId,
    ]);
    return $stmt->fetchAll();
}

function userById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.*, t.user_type
        FROM users u
        LEFT JOIN user_type t ON t.user_type_id = u.user_type_id
        WHERE u.user_id = :id
    ");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function myPostedJobs(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT j.*, c.category_name,
               (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS application_count
        FROM jobs j
        LEFT JOIN job_category c ON c.category_id = j.category_id
        WHERE j.family_id = :id
        ORDER BY j.created_at DESC, j.job_id DESC
    ");
    $stmt->execute(['id' => $userId]);
    return $stmt->fetchAll();
}

function myWorkerApplications(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT a.*, s.status_name, j.title, j.pay, j.location, j.frequency, u.name AS family_name
        FROM applications a
        LEFT JOIN application_status s ON s.status_id = a.application_status_id
        LEFT JOIN jobs j ON j.job_id = a.job_id
        LEFT JOIN users u ON u.user_id = j.family_id
        WHERE a.worker_id = :id
        ORDER BY a.application_id DESC
    ");
    $stmt->execute(['id' => $userId]);
    return $stmt->fetchAll();
}

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }

    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function redirectTo(string $url): never
{
    header("Location: {$url}");
    exit;
}

function handlePost(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $current = currentUser($pdo);

    if ($action === 'signup') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $userType = (string) ($_POST['user_type'] ?? 'family');
        $neighbourhood = trim((string) ($_POST['neighbourhood'] ?? 'Greenwood Valley'));
        $fullAddress = trim((string) ($_POST['full_address'] ?? $neighbourhood));
        $latitude = nullableCoordinate($_POST['latitude'] ?? null);
        $longitude = nullableCoordinate($_POST['longitude'] ?? null);

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            flash('Enter a name, valid email, and password with at least 8 characters.');
            redirectTo('index.php?page=signup');
        }

        $typeStmt = $pdo->prepare("SELECT user_type_id FROM user_type WHERE user_type = :type LIMIT 1");
        $typeStmt->execute(['type' => in_array($userType, ['family', 'worker'], true) ? $userType : 'family']);
        $typeId = (int) ($typeStmt->fetchColumn() ?: 1);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, user_type_id, neighbourhood, full_address, latitude, longitude, password_hash)
                VALUES (:name, :email, :user_type_id, :neighbourhood, :full_address, :latitude, :longitude, :password_hash)
            ");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'user_type_id' => $typeId,
                'neighbourhood' => $neighbourhood ?: 'Greenwood Valley',
                'full_address' => $fullAddress ?: $neighbourhood,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException) {
            flash('That email is already registered. Please log in.');
            redirectTo('index.php?page=login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        flash('Account created. Welcome to LocalLoop.');
        redirectTo('index.php?page=account');
    }

    if ($action === 'login') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || empty($user['password_hash']) || !password_verify($password, (string) $user['password_hash'])) {
            flash('Login failed. Check your email and password.');
            redirectTo('index.php?page=login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        flash('Logged in.');
        redirectTo('index.php?page=account');
    }

    if ($action === 'logout') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        redirectTo('index.php');
    }

    requireUser($current);

    if ($action === 'update_profile') {
        $name = trim((string) ($_POST['name'] ?? $current['name']));
        $neighbourhood = trim((string) ($_POST['neighbourhood'] ?? $current['neighbourhood']));
        $fullAddress = trim((string) ($_POST['full_address'] ?? $current['full_address'] ?? $neighbourhood));
        $latitude = nullableCoordinate($_POST['latitude'] ?? null) ?? nullableCoordinate($current['latitude'] ?? null);
        $longitude = nullableCoordinate($_POST['longitude'] ?? null) ?? nullableCoordinate($current['longitude'] ?? null);

        $data = [
            'name' => $name ?: $current['name'],
            'neighbourhood' => $neighbourhood ?: 'Ireland',
            'full_address' => $fullAddress ?: $neighbourhood,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'bio' => trim((string) ($_POST['bio'] ?? $current['bio'] ?? '')),
        ];

        if (($current['user_type'] ?? '') === 'worker') {
            $data['hourly_rate'] = (float) ($_POST['hourly_rate'] ?? $current['hourly_rate'] ?? 0);
            $data['experience_years'] = max(0, (int) ($_POST['experience_years'] ?? $current['experience_years'] ?? 0));
            $data['availability_note'] = trim((string) ($_POST['availability_note'] ?? $current['availability_note'] ?? ''));
            $categoryIds = $_POST['category_ids'] ?? [];
            setWorkerCategories($pdo, (int) $current['user_id'], is_array($categoryIds) ? $categoryIds : [$categoryIds], trim((string) ($_POST['category_reason'] ?? '')));
        }

        updateUser($pdo, (int) $current['user_id'], $data);
        flash('Profile updated.');
        redirectTo('index.php?page=account');
    }

    if ($action === 'create_job') {
        if (($current['user_type'] ?? '') !== 'family') {
            flash('Only family accounts can post jobs.');
            redirectTo('index.php?page=account');
        }

        $location = trim((string) ($_POST['location'] ?? $current['neighbourhood'] ?? 'Ireland'));
        $fullAddress = trim((string) ($_POST['full_address'] ?? $location));
        $latitude = nullableCoordinate($_POST['latitude'] ?? null) ?? nullableCoordinate($current['latitude'] ?? null);
        $longitude = nullableCoordinate($_POST['longitude'] ?? null) ?? nullableCoordinate($current['longitude'] ?? null);

        $stmt = $pdo->prepare("
            INSERT INTO jobs (category_id, title, description, pay, family_id, location, full_address, latitude, longitude, frequency, scheduled_at, is_recurring, status)
            VALUES (:category_id, :title, :description, :pay, :family_id, :location, :full_address, :latitude, :longitude, :frequency, :scheduled_at, :is_recurring, 'open')
        ");
        $stmt->execute([
            'category_id' => (int) ($_POST['category_id'] ?? 7),
            'title' => trim((string) ($_POST['title'] ?? 'Local help needed')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'pay' => (float) ($_POST['pay'] ?? 0),
            'family_id' => (int) $current['user_id'],
            'location' => $location ?: 'Ireland',
            'full_address' => $fullAddress ?: $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'frequency' => trim((string) ($_POST['frequency'] ?? 'One-time')),
            'scheduled_at' => !empty($_POST['scheduled_at']) ? str_replace('T', ' ', (string) $_POST['scheduled_at']) . ':00' : null,
            'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
        ]);
        flash('Your job listing is live.');
        redirectTo('index.php?page=job&id=' . $pdo->lastInsertId());
    }

    if ($action === 'apply_job') {
        $jobId = (int) ($_POST['job_id'] ?? 0);
        $workerId = (int) $current['user_id'];
        if (($current['user_type'] ?? '') !== 'worker') {
            flash('Only worker accounts can apply to jobs.');
            redirectTo('index.php?page=job&id=' . $jobId);
        }

        $ownerCheck = $pdo->prepare("SELECT family_id FROM jobs WHERE job_id = :job_id");
        $ownerCheck->execute(['job_id' => $jobId]);
        if ((int) $ownerCheck->fetchColumn() === $workerId) {
            flash('You cannot apply to your own job.');
            redirectTo('index.php?page=job&id=' . $jobId);
        }

        $existing = applicationFor($pdo, $jobId, $workerId);
        if (!$existing) {
            $stmt = $pdo->prepare("
                INSERT INTO applications (job_id, worker_id, application_status_id)
                VALUES (:job_id, :worker_id, 1)
            ");
            $stmt->execute(['job_id' => $jobId, 'worker_id' => $workerId]);
        }
        flash('Application sent.');
        redirectTo('index.php?page=job&id=' . $jobId);
    }

    if ($action === 'save_job') {
        $jobId = (int) ($_POST['job_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT saved_job_id FROM saved_jobs WHERE user_id = :user_id AND job_id = :job_id");
        $stmt->execute(['user_id' => (int) $current['user_id'], 'job_id' => $jobId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $delete = $pdo->prepare("DELETE FROM saved_jobs WHERE saved_job_id = :id");
            $delete->execute(['id' => $existing]);
            flash('Job removed from saved list.');
        } else {
            $insert = $pdo->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (:user_id, :job_id)");
            $insert->execute(['user_id' => (int) $current['user_id'], 'job_id' => $jobId]);
            flash('Job saved.');
        }
        redirectTo($_POST['redirect'] ?? 'index.php?page=jobs');
    }

    if ($action === 'send_message') {
        $receiverId = (int) ($_POST['receiver_id'] ?? 0);
        $jobId = !empty($_POST['job_id']) ? (int) $_POST['job_id'] : null;
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($receiverId > 0 && $body !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO messages (job_id, sender_id, receiver_id, body)
                VALUES (:job_id, :sender_id, :receiver_id, :body)
            ");
            $stmt->execute([
                'job_id' => $jobId,
                'sender_id' => (int) $current['user_id'],
                'receiver_id' => $receiverId,
                'body' => $body,
            ]);
            flash('Message sent.');
        }

        redirectTo('index.php?page=messages&with=' . $receiverId);
    }

    if ($action === 'update_application_status') {
        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'pending');
        $stmt = $pdo->prepare("
            UPDATE applications a
            JOIN jobs j ON j.job_id = a.job_id
            SET a.application_status_id = (SELECT status_id FROM application_status WHERE status_name = :status LIMIT 1)
            WHERE a.application_id = :application_id
              AND j.family_id = :family_id
        ");
        $stmt->execute([
            'status' => $status,
            'application_id' => $applicationId,
            'family_id' => (int) $current['user_id'],
        ]);
        flash($stmt->rowCount() > 0 ? 'Application updated.' : 'That application cannot be updated from this account.');
        redirectTo($_POST['redirect'] ?? 'index.php?page=account');
    }

    if ($action === 'finalize_payment') {
        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $scheduledFor = !empty($_POST['scheduled_for']) ? str_replace('T', ' ', (string) $_POST['scheduled_for']) . ':00' : null;

        $ownerCheck = $pdo->prepare("
            SELECT COUNT(*)
            FROM applications a
            JOIN jobs j ON j.job_id = a.job_id
            WHERE a.application_id = :application_id
              AND j.family_id = :family_id
        ");
        $ownerCheck->execute([
            'application_id' => $applicationId,
            'family_id' => (int) $current['user_id'],
        ]);
        if ((int) $ownerCheck->fetchColumn() === 0) {
            flash('That booking cannot be finalized from this account.');
            redirectTo($_POST['redirect'] ?? 'index.php?page=account');
        }

        $payment = $pdo->prepare("
            INSERT INTO payments (application_id, amount, payment_status, paid_at)
            VALUES (:application_id, :amount, 'paid', CURRENT_TIMESTAMP)
        ");
        $payment->execute(['application_id' => $applicationId, 'amount' => $amount]);

        $booking = $pdo->prepare("
            INSERT INTO bookings (application_id, scheduled_for, booking_status)
            VALUES (:application_id, :scheduled_for, 'confirmed')
        ");
        $booking->execute(['application_id' => $applicationId, 'scheduled_for' => $scheduledFor]);

        $update = $pdo->prepare("
            UPDATE applications
            SET application_status_id = (SELECT status_id FROM application_status WHERE status_name = 'completed' LIMIT 1)
            WHERE application_id = :application_id
        ");
        $update->execute(['application_id' => $applicationId]);

        flash('Booking finalized and payment recorded.');
        redirectTo($_POST['redirect'] ?? 'index.php?page=messages');
    }
}

function avatar(array $user, string $size = 'avatar'): string
{
    $url = (string) ($user['avatar_url'] ?? '');
    $file = $url !== '' ? __DIR__ . '/' . $url : '';

    if ($url !== '' && file_exists($file)) {
        return '<img class="' . h($size) . '" src="' . h($url) . '" alt="' . h($user['name'] ?? 'User') . '">';
    }

    $initials = initials((string) ($user['name'] ?? 'LL'));
    return '<span class="' . h($size) . ' avatar-fallback">' . h($initials) . '</span>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper($part[0]);
        }
        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters ?: 'LL';
}

function formatDateTime(?string $value): string
{
    if (!$value) {
        return 'Flexible';
    }

    return date('D, M j - g:i A', strtotime($value));
}

function statusClass(?string $status): string
{
    return 'status-' . preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $status));
}
