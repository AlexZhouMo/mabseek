<?php
declare(strict_types=1);

final class Collection {
    // 各表允许写入的列（白名单，防批量赋值）
    private const COLUMNS = [
        'news'          => ['date_day','date_ym','category','title','summary','image','sort','published','body'],
        'forum_posts'   => ['category','cover_type','cover_ref','cover_variant','toptag','title','tags','author_name','author_avatar_char','author_avatar_style','likes','sort','published'],
        'forum_hot'     => ['list','rank','title','category','heat','sort','published'],
        'team_members'  => ['name','affiliation','direction','role_label','role_type','avatar_char','avatar_variant','sort','published'],
        'partners'      => ['name','mark','sub','logo_image','demo','sort','published'],
        'content_cards' => ['grp','icon','title','body','extra','sort','published'],
    ];

    public function __construct(private string $table) {
        if (!isset(self::COLUMNS[$this->table])) {
            throw new InvalidArgumentException("unknown collection: {$this->table}");
        }
    }

    private function cols(): array { return self::COLUMNS[$this->table]; }

    private function filter(array $data): array {
        return array_intersect_key($data, array_flip($this->cols()));
    }

    public function all(?string $where = null, array $params = []): array {
        $sql = "SELECT * FROM {$this->table}";
        if ($where) $sql .= " WHERE $where";
        $sql .= ' ORDER BY sort ASC, id ASC';
        $st = db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** 前台用：仅 published，可加额外条件 */
    public function published(?string $extraWhere = null, array $params = []): array {
        $where = 'published = 1' . ($extraWhere ? " AND ($extraWhere)" : '');
        return $this->all($where, $params);
    }

    public function find(int $id): ?array {
        $st = db()->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function create(array $data): int {
        $data = $this->filter($data);
        $now = iso_now();
        $data['created_at'] = $now; $data['updated_at'] = $now;
        $keys = array_keys($data);
        $ph   = implode(',', array_fill(0, count($keys), '?'));
        $sql  = "INSERT INTO {$this->table} (" . implode(',', $keys) . ") VALUES ($ph)";
        db()->prepare($sql)->execute(array_values($data));
        return (int)db()->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $data = $this->filter($data);
        if (!$data) return;
        $data['updated_at'] = iso_now();
        $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $set WHERE id = ?";
        db()->prepare($sql)->execute([...array_values($data), $id]);
    }

    public function delete(int $id): void {
        db()->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }

    public function count(): int {
        return (int)db()->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }
}
