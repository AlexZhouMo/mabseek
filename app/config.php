<?php
declare(strict_types=1);

// ── 路径 ──
const APP_ROOT   = __DIR__;                       // .../mabseek/app
const BASE_ROOT  = __DIR__ . '/..';               // .../mabseek
const DATA_DIR   = BASE_ROOT . '/data';
const DB_PATH    = DATA_DIR . '/mabseek.sqlite';
const UPLOAD_DIR = BASE_ROOT . '/public/assets/images/uploads';
const UPLOAD_URL = 'assets/images/uploads';       // 前台相对 URL 前缀

// ── 会话 / 风控 ──
const SESSION_NAME       = 'mabseek_sid';
const IDLE_TIMEOUT       = 1800;                  // 空闲 30 分钟
const ABSOLUTE_TIMEOUT   = 28800;                 // 绝对 8 小时
const LOGIN_MAX_FAILS    = 5;                      // 单 (IP,用户) 连续失败上限
const LOGIN_IP_MAX_FAILS = 20;                     // 单 IP 全局失败上限（防轮换用户名规避）
const LOGIN_LOCK_SECONDS = 900;                    // 锁定 15 分钟
const LOGIN_FAIL_WINDOW  = 900;                    // 统计窗口 15 分钟

// ── 上传 ──
const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;         // 2MB
const UPLOAD_ALLOWED   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// ── 初始管理员 ──
const SEED_ADMIN_USER = 'admin';
const SEED_ADMIN_PASS = 'mabseek2026';            // 仅 seed 时哈希入库，绝不落库明文

// ── 验证码 ──
const CAPTCHA_TTL = 600;                          // 图形验证码有效期 10 分钟

// ── 论坛会员发帖 ──
const THREAD_CATEGORIES = [
    'pit'   => '# 实验踩坑',
    'proto' => '# Protocol 分享',
    'paper' => '# 文献精读',
    'bio'   => '# 生信工具',
    'job'   => '# 求职招聘',
];
const THREAD_RATE_MIN_SECONDS = 60;    // 两帖最小间隔
const THREAD_RATE_DAILY_MAX   = 20;    // 单会员 24 小时最多发帖数

// ── 环境（生产设 false）──
const APP_DEBUG = false;
