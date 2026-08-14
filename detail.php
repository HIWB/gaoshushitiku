<?php
// detail.php - 详情页

$dbFile = __DIR__ . '/database/papers.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM papers WHERE id = ?");
$stmt->execute([$id]);
$paper = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paper) {
    header('Location: index.php');
    exit;
}

$paperImages = json_decode($paper['paper_images'], true) ?: [];
$answerImages = json_decode($paper['answer_images'], true) ?: [];
$solutionImages = json_decode($paper['solution_images'], true) ?: [];

// 获取个人信息（关于弹窗用）
$profileStmt = $db->query("SELECT * FROM profile LIMIT 1");
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

// 获取公告内容
$noticeStmt = $db->query("SELECT * FROM notice LIMIT 1");
$notice = $noticeStmt->fetch(PDO::FETCH_ASSOC);
$noticeContent = $notice['content'] ?? '';

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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($paper['title']) ?> · 高数试题库</title>
    <link rel="stylesheet" href="assets/style.css" />
    <style>
        .detail-description {
            background: rgba(248, 250, 252, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border-radius: 20px;
            padding: 16px 22px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            font-size: 0.95rem;
            line-height: 1.7;
            color: #2d4055;
        }
        .detail-description .desc-label {
            font-weight: 600;
            color: #1a2634;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 4px;
        }
        .detail-description .desc-meta {
            color: #7a8a9e;
            font-size: 0.85rem;
            display: block;
            margin-top: 4px;
        }
        .detail-description .desc-text {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

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
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid rgba(0, 0, 0, 0.04);
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
        }
    </style>
</head>
<body>

    <!-- ===== 导航栏 ===== -->
    <nav class="navbar">
        <div class="logo">高数试题库</div>
        <div class="nav-links">
            <a href="index.php">首页</a>
            <a id="navNotice">公告</a>
            <a id="navAbout">关于</a>
        </div>
    </nav>

    <!-- ============================================================ -->
    <!-- 详情页 -->
    <!-- ============================================================ -->
    <div class="detail-page open" style="display:block;">
        <a href="index.php" class="back-link" style="font-weight:700;">← 返回试卷列表</a>

        <div class="detail-header">
            <div class="detail-title"><?= htmlspecialchars($paper['title']) ?></div>
            <div class="detail-school"><?= htmlspecialchars($paper['school']) ?> · <?= htmlspecialchars($paper['year']) ?> 年 <?= htmlspecialchars($paper['semester']) ?>季</div>
        </div>

        <!-- ===== 试卷介绍区域 ===== -->
        <div class="detail-description">
            <span class="desc-label">介绍</span>
            <?php if (!empty($paper['description'])): ?>
                <div class="desc-text"><?= nl2br(htmlspecialchars($paper['description'])) ?></div>
            <?php else: ?>
                <span style="color:#7a8a9e;">暂无介绍</span>
            <?php endif; ?>
            <span class="desc-meta">
                共 <?= count($paperImages) ?> 页试卷 · <?= count($answerImages) ?> 张标准答案 · <?= count($solutionImages) ?> 张个人解答
            </span>
        </div>

        <div class="detail-grid">
            <!-- 试卷 -->
            <div class="detail-col">
                <div class="col-title">试卷 <span class="badge-count"><?= count($paperImages) ?> 张</span></div>
                <div class="image-grid" id="paperImages">
                    <?php if (empty($paperImages)): ?>
                        <div class="empty-msg">暂无图片</div>
                    <?php else: ?>
                        <?php foreach ($paperImages as $img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="试卷图片" loading="lazy" data-group="paper" data-src="<?= htmlspecialchars($img) ?>" />
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 标准答案 -->
            <div class="detail-col">
                <div class="col-title">标准答案 <span class="badge-count"><?= count($answerImages) ?> 张</span></div>
                <div class="image-grid" id="answerImages">
                    <?php if (empty($answerImages)): ?>
                        <div class="empty-msg">暂无图片</div>
                    <?php else: ?>
                        <?php foreach ($answerImages as $img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="标准答案" loading="lazy" data-group="answer" data-src="<?= htmlspecialchars($img) ?>" />
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 我的解答 -->
            <div class="detail-col">
                <div class="col-title">我的解答 <span class="badge-count"><?= count($solutionImages) ?> 张</span></div>
                <div class="image-grid" id="solutionImages">
                    <?php if (empty($solutionImages)): ?>
                        <div class="empty-msg">暂无图片</div>
                    <?php else: ?>
                        <?php foreach ($solutionImages as $img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="我的解答" loading="lazy" data-group="solution" data-src="<?= htmlspecialchars($img) ?>" />
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
            <div class="notice-footer">
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

        document.getElementById('navAbout').addEventListener('click', function(e) {
            e.preventDefault();
            openAbout(profileData);
        });

        document.getElementById('closeAbout').addEventListener('click', closeAbout);

        // ---- 收集当前试卷所有图片，带分组标记 ----
        const paperImages = <?= json_encode(array_map(function($img) { return ['src' => $img, 'group' => 'paper']; }, $paperImages)) ?>;
        const answerImages = <?= json_encode(array_map(function($img) { return ['src' => $img, 'group' => 'answer']; }, $answerImages)) ?>;
        const solutionImages = <?= json_encode(array_map(function($img) { return ['src' => $img, 'group' => 'solution']; }, $solutionImages)) ?>;
        const allItems = [...paperImages, ...answerImages, ...solutionImages];

        // ---- 修复：点击图片打开预览器，正确显示被点击的图片 ----
        document.querySelectorAll('.detail-col .image-grid img').forEach(img => {
            img.addEventListener('click', function() {
                const src = this.dataset.src || this.src;
                const matchedIndex = allItems.findIndex(item => item.src === src);
                if (matchedIndex !== -1) {
                    lightboxItems = allItems;
                    currentLightboxIndex = matchedIndex;
                    scale = 1;
                    translateX = 0;
                    translateY = 0;
                    updateImageTransform();
                    document.getElementById('lightboxOverlay').classList.add('open');
                    document.body.style.overflow = 'hidden';
                    renderLightbox(currentLightboxIndex);
                } else {
                    const group = this.dataset.group || 'paper';
                    const groupItems = allItems.filter(item => item.group === group);
                    if (groupItems.length > 0) {
                        lightboxItems = groupItems;
                        const idx = groupItems.findIndex(item => item.src === src);
                        currentLightboxIndex = idx >= 0 ? idx : 0;
                        scale = 1;
                        translateX = 0;
                        translateY = 0;
                        updateImageTransform();
                        document.getElementById('lightboxOverlay').classList.add('open');
                        document.body.style.overflow = 'hidden';
                        renderLightbox(currentLightboxIndex);
                    } else {
                        lightboxItems = [{ src: src, group: group }];
                        currentLightboxIndex = 0;
                        scale = 1;
                        translateX = 0;
                        translateY = 0;
                        updateImageTransform();
                        document.getElementById('lightboxOverlay').classList.add('open');
                        document.body.style.overflow = 'hidden';
                        renderLightbox(currentLightboxIndex);
                    }
                }
            });
        });

        window.openLightbox = function(imageSrc, allItems) {
            if (!allItems || allItems.length === 0) return;
            lightboxItems = allItems;
            const index = allItems.findIndex(item => item.src === imageSrc);
            currentLightboxIndex = index >= 0 ? index : 0;
            scale = 1;
            translateX = 0;
            translateY = 0;
            updateImageTransform();
            document.getElementById('lightboxOverlay').classList.add('open');
            document.body.style.overflow = 'hidden';
            renderLightbox(currentLightboxIndex);
        };

        // ============================================================
        // 公告功能（详情页：手动点击打开，不自动弹出）
        // ============================================================

        var noticeContent = <?= json_encode($noticeContent) ?>;

        function openNotice() {
            var overlay = document.getElementById('noticeOverlay');
            var content = document.getElementById('noticeContent');
            content.innerHTML = noticeContent.replace(/\n/g, '<br>');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeNotice() {
            var overlay = document.getElementById('noticeOverlay');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('navNotice').addEventListener('click', function(e) {
            e.preventDefault();
            openNotice();
        });

        document.getElementById('closeNotice').addEventListener('click', closeNotice);
        document.getElementById('closeNoticeBtn').addEventListener('click', closeNotice);

        document.getElementById('noticeOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeNotice();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('noticeOverlay').classList.contains('open')) {
                    closeNotice();
                }
            }
        });
    </script>

</body>
</html>