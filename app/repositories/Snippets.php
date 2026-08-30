<?php
declare(strict_types=1);

final class Snippets {
    private static ?array $cache = null;

    private static function load(): array {
        if (self::$cache === null) {
            $rows = db()->query('SELECT skey, value FROM snippets')->fetchAll();
            self::$cache = [];
            foreach ($rows as $r) self::$cache[$r['skey']] = $r['value'];
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string {
        $all = self::load();
        return array_key_exists($key, $all) ? (string)$all[$key] : $default;
    }

    /** 幂等 upsert；seed 与后台共用 */
    public static function set(string $key, string $value, string $grp = '', string $label = '', string $type = 'text', int $sort = 0): void {
        $now = iso_now();
        db()->prepare(
            'INSERT INTO snippets(skey,value,grp,label,type,sort,created_at,updated_at)
             VALUES(:k,:v,:g,:l,:t,:s,:n,:n)
             ON CONFLICT(skey) DO UPDATE SET value=excluded.value, updated_at=:n'
        )->execute([':k'=>$key, ':v'=>$value, ':g'=>$grp, ':l'=>$label, ':t'=>$type, ':s'=>$sort, ':n'=>$now]);
        self::$cache = null;
    }

    /** 仅当不存在时写入（seed 保护后台已改文案） */
    public static function seed(string $key, string $value, string $grp, string $label, string $type = 'text', int $sort = 0): void {
        $exists = db()->prepare('SELECT 1 FROM snippets WHERE skey = ?');
        $exists->execute([$key]);
        if ($exists->fetchColumn()) return;
        self::set($key, $value, $grp, $label, $type, $sort);
    }

    public static function updateValue(int $id, string $value): void {
        db()->prepare('UPDATE snippets SET value = ?, updated_at = ? WHERE id = ?')
            ->execute([$value, iso_now(), $id]);
        self::$cache = null;
    }

    public static function allGrouped(): array {
        $rows = db()->query('SELECT * FROM snippets ORDER BY grp, sort, id')->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[$r['grp']][] = $r;
        return $out;
    }
}

/** 模板便捷函数：转义输出文案片段 */
function snip(string $key, string $default = ''): string {
    return e(Snippets::get($key, $default));
}
/** 原样（不转义，用于本身含既定 markup 的极少数片段——默认不用） */
function snip_raw(string $key, string $default = ''): string {
    return Snippets::get($key, $default);
}
