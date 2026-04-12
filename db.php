<?php

function db_connect(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDir = __DIR__ . '/uploads';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    $dbPath = $dataDir . '/portal.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    db_migrate($pdo);
    return $pdo;
}

function db_migrate(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            email TEXT PRIMARY KEY,
            full_name TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            picture TEXT,
            is_admin INTEGER NOT NULL DEFAULT 0,
            last_login_at TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS charter_acceptances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            charter_version TEXT NOT NULL,
            accepted_at TEXT NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            UNIQUE(email, charter_version)
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_charter_email ON charter_acceptances(email)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_charter_version ON charter_acceptances(charter_version)');
}

function current_charter_version(array $config = []): string {
    return (string)($config['charter']['version'] ?? '2026-04-12');
}

function now_iso_utc(): string {
    return gmdate('c');
}

function db_upsert_user_from_session(PDO $pdo, array $user, bool $isAdmin): void {
    $email = trim((string)($user['email'] ?? ''));
    if ($email === '') {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (email, full_name, first_name, last_name, picture, is_admin, last_login_at, created_at, updated_at)
         VALUES (:email, :full_name, :first_name, :last_name, :picture, :is_admin, :last_login_at, :created_at, :updated_at)
         ON CONFLICT(email) DO UPDATE SET
            full_name = excluded.full_name,
            first_name = excluded.first_name,
            last_name = excluded.last_name,
            picture = excluded.picture,
            is_admin = excluded.is_admin,
            last_login_at = excluded.last_login_at,
            updated_at = excluded.updated_at'
    );

    $now = now_iso_utc();
    $stmt->execute([
        ':email' => $email,
        ':full_name' => (string)($user['name'] ?? $email),
        ':first_name' => (string)($user['firstName'] ?? ''),
        ':last_name' => (string)($user['lastName'] ?? ''),
        ':picture' => (string)($user['picture'] ?? ''),
        ':is_admin' => $isAdmin ? 1 : 0,
        ':last_login_at' => $now,
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);
}

function db_has_accepted_charter(PDO $pdo, string $email, string $version): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM charter_acceptances WHERE email = :email AND charter_version = :version LIMIT 1');
    $stmt->execute([':email' => $email, ':version' => $version]);
    return (bool)$stmt->fetchColumn();
}

function db_accept_charter(PDO $pdo, string $email, string $version, ?string $ip, ?string $ua): void {
    $stmt = $pdo->prepare(
        'INSERT INTO charter_acceptances (email, charter_version, accepted_at, ip_address, user_agent)
         VALUES (:email, :version, :accepted_at, :ip_address, :user_agent)
         ON CONFLICT(email, charter_version) DO UPDATE SET
            accepted_at = excluded.accepted_at,
            ip_address = excluded.ip_address,
            user_agent = excluded.user_agent'
    );
    $stmt->execute([
        ':email' => $email,
        ':version' => $version,
        ':accepted_at' => now_iso_utc(),
        ':ip_address' => $ip,
        ':user_agent' => $ua,
    ]);
}

function db_reset_charter_acceptance(PDO $pdo, string $email, string $version): void {
    $stmt = $pdo->prepare('DELETE FROM charter_acceptances WHERE email = :email AND charter_version = :version');
    $stmt->execute([
        ':email' => $email,
        ':version' => $version,
    ]);
}
