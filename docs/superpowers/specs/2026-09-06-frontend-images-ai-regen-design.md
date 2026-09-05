# 设计文档：全站前端配图 AI 重生

- 日期：2026-09-06
- 状态：已批准，待实现

## 背景

MabSeek（清华团队 × AI 的抗体研究平台）前端配图需用 AI 统一重生：修复唯一断图 `location.png`，并把现有配图重生成风格统一的深色科技抽象风。站点为深色科技 UI（紫 #6D3BEB 系 / neon green 主色）。

## 范围

### 重生同名覆盖（7 张真实展示配图）

| 文件 | 比例 | 用途语境 | 内容要点 |
|---|---|---|---|
| `hero-home.jpg` | 16:9 | 首页 hero 全屏背景（`background-size:cover`，右锚，左侧 92%→0 深色遮罩压字） | 深色科技抗体/分子主视觉，主体偏右，左侧低细节暗区可压字 |
| `agent-hero.png` | 3:2 | about `#platform` 平台介绍右列卡片（圆角+阴影） | AI 干实验 + 湿实验交付一体化工作流示意 |
| `antibody-structure.png` | 1:1 | agent `#wetlab` 右图 + edu 第04课缩略图（16:10 裁切） | Y 形抗体三维分子/晶体结构渲染 |
| `education-banner.png` | 3:2 | edu 顶部 340px Banner（左遮罩叠标题）+ 播放器画面 + 01/03课缩略图 | 《疫苗的力量》课程主视觉，疫苗/免疫科普感，左侧可压暗放字 |
| `lab-scene.png` | 3:2 | edu 第02课缩略图（16:10） | 实验室科研场景 |
| `international.png` | 3:2 | about `#intl` 国际合作左列卡片 | 全球联动/国际科研合作意象（地图连线/多国协作） |
| `location.png` | 3:2 | about 团队区 `.loc-block`（**文件缺失，现 onerror 回退 🏛️ emoji**） | 清华医学院/北京地点感实景 |

### 新生成 + 接入论坛（3 张）

`forum-adc.png` / `forum-bioinfo.png` / `forum-protocol.png`（均 1:1）：

- **修正**：seed 现有 3 个论坛帖主题为「公开课回顾 / 抗体设计工作坊 / AI 抗体发现讲座」（`bin/seed.php` 第 48-50 行），**无** ADC/生信/protocol 对应帖。ADC/生信/protocol 仅出现在 forum_line 版块介绍文字里。
- **方案**：沿用三个文件名（不改前端引用逻辑），图片内容对齐现有 3 个帖主题：
  - `forum-adc.png` → 公开课回顾主视觉
  - `forum-bioinfo.png` → 抗体设计工作坊主视觉
  - `forum-protocol.png` → AI 抗体发现讲座主视觉
- **接入**：`bin/seed.php` 三个帖的 `cover` 字段（现为 `''`）填对应图路径。重建库后前台论坛显示封面（否则回退渐变+🧬 emoji）。

### 排除（不生成）

- `logo.png`：透明底品牌标识，AI 重生不合适。
- `team/ma.png`、`team/zhang.png`：真实团队成员（张林琦、马维英）肖像，不生成虚构真人。

## 视觉风格（统一）

深色科技抽象风；紫（#6D3BEB 系）绿（neon green）渐变呼应站点主色；抽象分子/抗体结构可视化；现代、干净、高质感。横图 3:2 或 16:9，方图 1:1。需压字的（hero-home、education-banner）左侧留深色低细节区。

## 工具与流程

1. 用 `image-gen-pro` skill 生成，科研/专业配图走高质量模型（如 GPT-image-2）。
2. **生成前备份**：现有图复制到 `public/assets/images/_backup/`（含 hero-home.jpg 等 9 张现存图；location.png 本就不存在无需备份）。
3. **逐张写 prompt**：含用途语境、构图方向、留白/压暗要求、风格关键词。
4. **覆盖同名**：新图覆盖 `public/assets/images/` 同名文件；前端代码零改动。
5. **seed 接入**：仅改 `bin/seed.php` 三个论坛帖 cover 字段。

## 非目标（YAGNI）

- 不重做 logo、不生成团队真人头像。
- 不改前端 HTML/CSS 引用（文件名与比例保持不变）。
- 不新增论坛帖（复用现有 3 帖）。

## 测试与验证

- `tests/run.php` 全套回归（改 seed 后确认不破坏测试）。
- 浏览器逐页验证：
  - 首页 hero 背景显示新图、文案可读（左侧压暗有效）。
  - about `#platform`/`#intl`/`.loc-block` 三图正常，location 不再触发 🏛️ emoji。
  - agent `#wetlab`、edu Banner/播放器/缩略图正常显示。
  - 重建库后论坛 3 帖显示封面图。
- `_backup/` 保留旧图，可回滚。
