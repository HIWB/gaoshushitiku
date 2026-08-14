<?php
// index.php - 首页

$dbFile = __DIR__ . '/database/papers.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 获取所有试卷
$stmt = $db->query("SELECT * FROM papers ORDER BY created_at DESC");
$papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取个人信息（用于关于弹窗）
$profileStmt = $db->query("SELECT * FROM profile LIMIT 1");
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

// 获取公告内容
$noticeStmt = $db->query("SELECT * FROM notice LIMIT 1");
$notice = $noticeStmt->fetch(PDO::FETCH_ASSOC);

// 获取所有学校（用于筛选和统计）
$schoolStmt = $db->query("SELECT DISTINCT school FROM papers ORDER BY school");
$allSchools = $schoolStmt->fetchAll(PDO::FETCH_COLUMN);

// 获取年份（用于筛选）
$yearStmt = $db->query("SELECT DISTINCT year FROM papers ORDER BY year DESC");
$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

// 处理搜索和筛选
$where = [];
$params = [];

if (isset($_GET['school']) && $_GET['school'] !== '') {
    $where[] = "school = ?";
    $params[] = $_GET['school'];
}
if (isset($_GET['year']) && $_GET['year'] !== '') {
    $where[] = "year = ?";
    $params[] = $_GET['year'];
}
if (isset($_GET['keyword']) && $_GET['keyword'] !== '') {
    $where[] = "title LIKE ?";
    $params[] = '%' . $_GET['keyword'] . '%';
}

$sql = "SELECT * FROM papers";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取当前搜索参数
$currentSchool = $_GET['school'] ?? '';
$currentYear = $_GET['year'] ?? '';
$currentKeyword = $_GET['keyword'] ?? '';

// ---- 使用 json_encode 安全处理个人信息数据 ----
$profileDataJson = json_encode([
    'name' => $profile['name'] ?? '姓名',
    'role' => $profile['role'] ?? '',
    'bio' => $profile['bio'] ?? '',
    'email' => $profile['email'] ?? '-',
    'wechat' => $profile['wechat'] ?? '-',
    'school' => $profile['school'] ?? '-',
    'avatar' => $profile['avatar'] ?? '',
    'github' => $profile['github'] ?? ''
]);

// 公告数据
$noticeContent = $notice['content'] ?? '';
$noticeAutoPopup = $notice['auto_popup'] ?? 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>高数试题库</title>
    <link rel="stylesheet" href="assets/style.css" />
    <style>
        /* ===== 公告弹窗 ===== */
        .notice-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 350;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }
        .notice-overlay.open {
            display: flex;
        }

        .notice-modal {
            background: rgba(248, 250, 252, 0.96);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 48px;
            max-width: 560px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 32px 36px 36px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.25);
            position: relative;
        }

        .notice-modal .close-btn {
            position: sticky;
            top: 0;
            float: right;
            background: rgba(30, 43, 60, 0.06);
            border: none;
            border-radius: 40px;
            width: 40px;
            height: 40px;
            font-size: 1.4rem;
            cursor: pointer;
            color: #4a5b6e;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notice-modal .close-btn:hover {
            background: rgba(30, 43, 60, 0.14);
        }

        .notice-modal .notice-title {
            font-size: 1.4rem;
            font-weight: 650;
            color: #1a2634;
            margin-bottom: 12px;
            padding-right: 50px;
        }

        .notice-modal .notice-content {
            font-size: 1rem;
            line-height: 1.8;
            color: #2d4055;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-bottom: 20px;
        }

        .notice-modal .notice-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid rgba(0, 0, 0, 0.04);
        }

        .notice-modal .notice-footer .checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #4a5b6e;
            cursor: pointer;
        }

        .notice-modal .notice-footer .checkbox-wrap input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1a2634;
            cursor: pointer;
        }

        .notice-modal .notice-footer .btn-close-notice {
            padding: 8px 28px;
            border-radius: 40px;
            border: none;
            background: #1a2634;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
            font-family: inherit;
        }

        .notice-modal .notice-footer .btn-close-notice:hover {
            background: #2c3e50;
        }

        @media (max-width: 500px) {
            .notice-modal {
                padding: 24px 20px 28px;
                border-radius: 32px;
            }
            .notice-modal .notice-title {
                font-size: 1.15rem;
            }
            .notice-modal .notice-content {
                font-size: 0.92rem;
            }
            .notice-modal .notice-footer {
                flex-direction: column;
                align-items: stretch;
            }
            .notice-modal .notice-footer .btn-close-notice {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- ===== 导航栏 ===== -->
    <nav class="navbar">
        <div class="logo">高数试题库</div>
        <div class="nav-links">
            <a class="active" id="navHome">首页</a>
            <a id="navNotice">公告</a>
            <a id="navAbout">关于</a>
        </div>
    </nav>

    <!-- ============================================================ -->
    <!-- 首页 -->
    <!-- ============================================================ -->
    <div class="container" id="homePage">

        <!-- 统计条 -->
        <div class="stats-bar">
            <span class="stat-item">共收录 <strong><?= count($papers) ?></strong> 套试卷</span>
            <span class="stat-divider"></span>
            <span class="stat-item"><strong>高校试题库</strong></span>
            <span class="stat-divider"></span>
            <span class="stat-item"><strong>持续更新中</strong></span>
        </div>

        <!-- 筛选栏 -->
        <form method="GET" class="filter-bar" id="filterForm">
            <select name="school" onchange="this.form.submit()">
                <option value="">全部学校</option>
                <?php foreach ($allSchools as $school): ?>
                    <option value="<?= htmlspecialchars($school) ?>" <?= $currentSchool === $school ? 'selected' : '' ?>>
                        <?= htmlspecialchars($school) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="year" onchange="this.form.submit()">
                <option value="">全部年份</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= htmlspecialchars($year) ?>" <?= $currentYear === $year ? 'selected' : '' ?>>
                        <?= htmlspecialchars($year) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="keyword" placeholder="搜索试卷名称..." value="<?= htmlspecialchars($currentKeyword) ?>" />
            <button type="submit" style="display:none;">搜索</button>
        </form>

        <!-- 试卷卡片网格 -->
        <div class="paper-grid">
            <?php if (empty($papers)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px 0;color:#7a8a9e;font-size:1rem;">
                    暂无试卷，敬请期待
                </div>
            <?php else: ?>
                <?php foreach ($papers as $paper): ?>
                    <?php
                    $paperImages = json_decode($paper['paper_images'], true) ?: [];
                    $answerImages = json_decode($paper['answer_images'], true) ?: [];
                    $solutionImages = json_decode($paper['solution_images'], true) ?: [];
                    $thumbnail = $paper['thumbnail'] ?: ($paperImages[0] ?? '');
                    ?>
                    <a href="detail.php?id=<?= $paper['id'] ?>" class="paper-card">
                        <div class="thumbnail">
                            <?php if ($thumbnail): ?>
                                <img src="<?= htmlspecialchars($thumbnail) ?>" alt="<?= htmlspecialchars($paper['title']) ?>" loading="lazy" />
                            <?php else: ?>
                                <div style="color:#b7c2cf;font-size:0.9rem;">暂无预览</div>
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <div class="school"><?= htmlspecialchars($paper['school']) ?></div>
                            <div class="title"><?= htmlspecialchars($paper['title']) ?></div>
                            <div class="meta">
                                <span><?= htmlspecialchars($paper['year']) ?> · <?= htmlspecialchars($paper['semester']) ?>季</span>
                                <span class="tag">试卷 <?= count($paperImages) ?> 页</span>
                                <span class="tag">答案 <?= count($answerImages) ?> 张</span>
                                <span class="tag">解答 <?= count($solutionImages) ?> 张</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 分页（简单版） -->
        <?php if (count($papers) > 6): ?>
            <div class="pagination">
                <a href="#" class="active">1</a>
                <a href="#">2</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================ -->
    <!-- 关于弹窗 -->
    <!-- ============================================================ -->
    <div class="about-overlay" id="aboutOverlay">
        <div class="about-modal">
            <button class="close-btn" id="closeAbout">✕</button>
            <div class="about-grid">
                <div class="avatar-col">
                    <div class="avatar">
                        <img id="profileAvatar" src="<?= htmlspecialchars($profile['avatar'] ?? '') ?>" alt="头像" />
                    </div>
                </div>
                <div class="info-col">
                    <div class="name" id="profileName"><?= htmlspecialchars($profile['name'] ?? '姓名') ?></div>
                    <div class="role" id="profileRole"><?= htmlspecialchars($profile['role'] ?? '') ?></div>
                    <div class="bio" id="profileBio"><?= htmlspecialchars($profile['bio'] ?? '') ?></div>
                    <div class="contact-item">
                        <span class="label">GitHub</span>
                        <span class="value" id="profileGithub"><?= htmlspecialchars($profile['github'] ?? '-') ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="label">邮箱</span>
                        <span class="value" id="profileEmail"><?= htmlspecialchars($profile['email'] ?? '-') ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="label">微信</span>
                        <span class="value" id="profileWechat"><?= htmlspecialchars($profile['wechat'] ?? '-') ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="label">学校</span>
                        <span class="value" id="profileSchool"><?= htmlspecialchars($profile['school'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 公告弹窗 -->
    <!-- ============================================================ -->
    <div class="notice-overlay" id="noticeOverlay">
        <div class="notice-modal">
            <button class="close-btn" id="closeNotice">✕</button>
            <div class="notice-title">公告</div>
            <div class="notice-content" id="noticeContent">
                <?= nl2br(htmlspecialchars($noticeContent)) ?>
            </div>
            <div class="notice-footer" id="noticeFooter">
                <label class="checkbox-wrap" id="noticeCheckboxWrap">
                    <input type="checkbox" id="noticeDontShow" />
                    下次不再弹出
                </label>
                <button class="btn-close-notice" id="closeNoticeBtn">关闭</button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 图片预览器 -->
    <!-- ============================================================ -->
    <div class="lightbox-overlay" id="lightboxOverlay">
        <button class="lightbox-close" id="lightboxClose">✕</button>
        <button class="lightbox-arrow prev" id="lightboxPrev">‹</button>
        <button class="lightbox-arrow next" id="lightboxNext">›</button>
        <div class="lightbox-main" id="lightboxMain">
            <div class="image-wrapper">
                <img id="lightboxImage" src="" alt="预览" />
            </div>
        </div>
        <div class="lightbox-bottom">
            <div class="lightbox-counter">
                <strong id="currentIdxDisplay">1</strong> / <span id="totalCountDisplay">1</span>
            </div>
            <div class="lightbox-group-labels" id="groupLabels"></div>
            <div class="lightbox-thumbs" id="lightboxThumbs"></div>
        </div>
    </div>

    <script src="assets/script.js"></script>
    <script>
        // 个人信息数据（使用 json_encode 安全处理）
        var profileData = <?= $profileDataJson ?>;

        // 关于弹窗
        document.getElementById('navAbout').addEventListener('click', function(e) {
            e.preventDefault();
            openAbout(profileData);
        });

        document.getElementById('closeAbout').addEventListener('click', closeAbout);

        // 首页导航
        document.getElementById('navHome').addEventListener('click', function(e) {
            e.preventDefault();
        });

        // ============================================================
        // 公告功能
        // ============================================================

        var noticeAutoPopup = <?= $noticeAutoPopup ?>;
        var noticeContent = <?= json_encode($noticeContent) ?>;

        function openNotice(showCheckbox) {
            var overlay = document.getElementById('noticeOverlay');
            var footer = document.getElementById('noticeFooter');
            var checkboxWrap = document.getElementById('noticeCheckboxWrap');
            var content = document.getElementById('noticeContent');

            // 更新内容
            content.innerHTML = noticeContent.replace(/\n/g, '<br>');

            // 控制"不再显示"复选框的显示
            if (showCheckbox) {
                checkboxWrap.style.display = 'flex';
            } else {
                checkboxWrap.style.display = 'none';
            }

            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeNotice() {
            var overlay = document.getElementById('noticeOverlay');
            var checkbox = document.getElementById('noticeDontShow');

            // 如果勾选了"下次不再弹出"，写入 Cookie（30天）
            if (checkbox.checked) {
                var expires = new Date();
                expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = 'notice_hidden=1; expires=' + expires.toUTCString() + '; path=/';
            }

            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        // 点击"公告"按钮（手动打开，不显示"不再显示"）
        document.getElementById('navNotice').addEventListener('click', function(e) {
            e.preventDefault();
            openNotice(false);
        });

        // 关闭按钮
        document.getElementById('closeNotice').addEventListener('click', closeNotice);
        document.getElementById('closeNoticeBtn').addEventListener('click', closeNotice);

        // 点击背景关闭
        document.getElementById('noticeOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeNotice();
        });

        // ESC 关闭
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('noticeOverlay').classList.contains('open')) {
                    closeNotice();
                }
            }
        });

        // ============================================================
        // 页面加载时自动弹出公告
        // ============================================================

        (function() {
            // 检查是否开启自动弹出
            if (noticeAutoPopup != 1) return;

            // 检查 Cookie 是否已记录"不再显示"
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var c = cookies[i].trim();
                if (c.indexOf('notice_hidden=') === 0) {
                    return; // 已勾选"不再显示"，不弹出
                }
            }

            // 延迟1秒弹出，让页面先加载
            setTimeout(function() {
                openNotice(true);
            }, 1000);
        })();
    </script>

</body>
</html>