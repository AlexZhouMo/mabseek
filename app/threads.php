<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function thread_validate_title(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 120; }
function thread_validate_body(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 5000; }
function thread_valid_category(string $v): bool { return array_key_exists($v, THREAD_CATEGORIES); }
