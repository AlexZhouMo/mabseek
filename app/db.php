<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $envDb = getenv('MABSEEK_DB');                 // 测试可用 :memory:
    $dsnPath = $envDb ?: DB_PATH;
    if ($dsnPath !== ':memory:' && !is_dir(dirname($dsnPath))) {
        mkdir(dirname($dsnPath), 0750, true);
    }
    $pdo = new PDO('sqlite:' . $dsnPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void {
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS news (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      date_day TEXT NOT NULL, date_ym TEXT NOT NULL,
      category TEXT NOT NULL, title TEXT NOT NULL, summary TEXT NOT NULL,
      body TEXT NOT NULL DEFAULT '',
      image TEXT, sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS forum_posts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      category TEXT NOT NULL, cover_type TEXT NOT NULL, cover_ref TEXT NOT NULL,
      cover_variant TEXT DEFAULT '', toptag TEXT DEFAULT '',
      title TEXT NOT NULL, tags TEXT DEFAULT '',
      author_name TEXT NOT NULL, author_avatar_char TEXT NOT NULL,
      author_avatar_style TEXT DEFAULT '', likes TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS forum_hot (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      list TEXT NOT NULL, rank INTEGER NOT NULL, title TEXT NOT NULL,
      category TEXT NOT NULL, heat TEXT NOT NULL,
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS team_members (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL, affiliation TEXT NOT NULL, direction TEXT NOT NULL,
      role_label TEXT NOT NULL, role_type TEXT NOT NULL,
      avatar_char TEXT NOT NULL, avatar_variant TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS partners (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL, mark TEXT NOT NULL, sub TEXT DEFAULT '',
      logo_image TEXT, demo TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS content_cards (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      grp TEXT NOT NULL, icon TEXT DEFAULT '', title TEXT NOT NULL,
      body TEXT DEFAULT '', extra TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS snippets (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      skey TEXT NOT NULL UNIQUE, value TEXT NOT NULL DEFAULT '',
      grp TEXT DEFAULT '', label TEXT DEFAULT '', type TEXT DEFAULT 'text',
      sort INTEGER DEFAULT 0,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE COLLATE NOCASE, password_hash TEXT NOT NULL,
      must_change_password INTEGER NOT NULL DEFAULT 0,
      email TEXT NOT NULL DEFAULT '',
      phone TEXT NOT NULL DEFAULT '',
      nickname TEXT NOT NULL DEFAULT '',
      role TEXT NOT NULL DEFAULT 'member',
      status TEXT NOT NULL DEFAULT 'active',
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS login_attempts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ip TEXT NOT NULL, username TEXT NOT NULL, success INTEGER NOT NULL,
      created_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS audit_log (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user TEXT NOT NULL, action TEXT NOT NULL, entity TEXT DEFAULT '',
      entity_id TEXT DEFAULT '', ip TEXT DEFAULT '', created_at TEXT NOT NULL
    );
    CREATE INDEX IF NOT EXISTS idx_attempts_ip ON login_attempts(ip, created_at);
SQL);
    add_column_if_missing($pdo, 'news', "body TEXT NOT NULL DEFAULT ''");
    // 老库增量补列（新库已带；均为代码常量，无用户输入）
    add_column_if_missing($pdo, 'users', "email TEXT NOT NULL DEFAULT ''");
    add_column_if_missing($pdo, 'users', "phone TEXT NOT NULL DEFAULT ''");
    add_column_if_missing($pdo, 'users', "nickname TEXT NOT NULL DEFAULT ''");
    add_column_if_missing($pdo, 'users', "role TEXT NOT NULL DEFAULT 'member'");
    add_column_if_missing($pdo, 'users', "status TEXT NOT NULL DEFAULT 'active'");
    // 唯一索引：username 大小写不敏感唯一（对老库亦生效，无需重建表）；email/phone 部分唯一（空值不冲突）
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users(username COLLATE NOCASE)");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email COLLATE NOCASE) WHERE email <> ''");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_phone ON users(phone) WHERE phone <> ''");
    // 升级陷阱防护：新增 role 默认 'member' 会把老库管理员锁在后台外，故以 SEED_ADMIN_USER 常量为键幂等回填
    $pdo->prepare("UPDATE users SET role='admin' WHERE username = ? COLLATE NOCASE AND role <> 'admin'")
        ->execute([SEED_ADMIN_USER]);
    $pdo->prepare("UPDATE users SET status='active' WHERE username = ? COLLATE NOCASE AND status = ''")
        ->execute([SEED_ADMIN_USER]);
}

/** 幂等补列：从 $ddl 首词取列名，PRAGMA 判断是否存在,缺失才 ALTER。
 *  注意：$table 与 $ddl 必须为代码内常量字面量，切勿传入用户输入（直接拼进 SQL）。 */
function add_column_if_missing(PDO $pdo, string $table, string $ddl): void {
    $col  = explode(' ', trim($ddl), 2)[0];
    $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array($col, $cols, true)) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $ddl");
    }
}
