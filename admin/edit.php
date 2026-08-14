<?php
// admin/edit.php - 编辑试卷

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$dbFile = __DIR__ . '/../database/papers.db';
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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school = trim($_POST['school'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($school) || empty($title) || empty($year) || empty($semester)) {
        $error = '请填写所有必填项';
    } else {
        // 处理新图片上传
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $paperDir = $uploadDir . 'paper_' . time() . '/';
        mkdir($paperDir, 0755, true);

        function uploadEditImages($files, $targetDir, $prefix) {
            $paths = [];
            if (isset($files['name']) && is_array($files['name'])) {
                foreach ($files['name'] as $key => $name) {
                    if ($files['error'][$key] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $filename = $prefix . '_' . ($key + 1) . '.' . $ext;
                        $targetPath = $targetDir . $filename;
                        if (move_uploaded_file($files['tmp_name'][$key], $targetPath)) {
                            $paths[] = '/uploads/' . basename($targetDir) . '/' . $filename;
                        }
                    }
                }
            }
            return $paths;
        }

        $newPaperImages = uploadEditImages($_FILES['paper_images'], $paperDir, 'paper');
        $newAnswerImages = uploadEditImages($_FILES['answer_images'], $paperDir, 'answer');
        $newSolutionImages = uploadEditImages($_FILES['solution_images'], $paperDir, 'solution');

        // 合并已有图片和新上传的图片
        $finalPaper = array_merge($paperImages, $newPaperImages);
        $finalAnswer = array_merge($answerImages, $newAnswerImages);
        $finalSolution = array_merge($solutionImages, $newSolutionImages);

        // 缩略图
        $thumbnail = $paper['thumbnail'];
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $thumbPath = $paperDir . 'thumbnail.' . $ext;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbPath)) {
                $thumbnail = '/uploads/' . basename($paperDir) . '/thumbnail.' . $ext;
            }
        }

        // 更新数据库（包含 description 字段）
        $stmt = $db->prepare("
            UPDATE papers SET
                school = ?,
                title = ?,
                year = ?,
                semester = ?,
                description = ?,
                thumbnail = ?,
                paper_images = ?,
                answer_images = ?,
                solution_images = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $school,
            $title,
            $year,
            $semester,
            $description,
            $thumbnail,
            json_encode($finalPaper),
            json_encode($finalAnswer),
            json_encode($finalSolution),
            $id
        ]);

        $message = '✅ 试卷更新成功！';
        // 刷新数据
        $stmt = $db->prepare("SELECT * FROM papers WHERE id = ?");
        $stmt->execute([$id]);
        $paper = $stmt->fetch(PDO::FETCH_ASSOC);
        $paperImages = json_decode($paper['paper_images'], true) ?: [];
        $answerImages = json_decode($paper['answer_images'], true) ?: [];
        $solutionImages = json_decode($paper['solution_images'], true) ?: [];
    }
}

// 获取所有学校
$schoolStmt = $db->query("SELECT DISTINCT school FROM papers ORDER BY school");
$existingSchools = $schoolStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>编辑试卷 · 后台管理</title>
    <link rel="stylesheet" href="../assets/style.css" />
    <style>
        .admin-nav {
            background: rgba(248, 250, 252, 0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 12px 28px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-nav .logo { font-size: 1rem; font-weight: 650; color: #1a2634; }
        .admin-nav .logo span { color: #4a5b6e; font-weight: 400; }
        .admin-nav .nav-links { display: flex; gap: 20px; font-size: 0.85rem; font-weight: 480; color: #4a5b6e; }
        .admin-nav .nav-links a { transition: color 0.15s; padding: 4px 0; border-bottom: 2px solid transparent; cursor: pointer; text-decoration: none; color: #4a5b6e; }
        .admin-nav .nav-links a:hover, .admin-nav .nav-links a.active { color: #1a2634; border-bottom-color: #1a2634; }
        .admin-nav .nav-links a.active { color: #1a2634; font-weight: 550; }
        .admin-nav .user-info { font-size: 0.8rem; color: #4a5b6e; display: flex; align-items: center; gap: 12px; }
        .admin-nav .user-info .logout { color: #c0392b; cursor: pointer; font-weight: 500; }
        .admin-nav .user-info .logout:hover { text-decoration: underline; }

        .card {
            background: rgba(248, 250, 252, 0.88);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 40px -16px rgba(0, 0, 0, 0.20);
            padding: 32px 36px 36px;
            margin-bottom: 28px;
        }
        .card-title { font-size: 1.2rem; font-weight: 650; color: #1a2634; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px dashed rgba(0, 0, 0, 0.06); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }

        .btn { display: inline-block; padding: 8px 24px; border-radius: 40px; font-size: 0.85rem; font-weight: 500; border: none; cursor: pointer; transition: background 0.15s, transform 0.1s; font-family: inherit; text-decoration: none; color: #fff; }
        .btn:active { transform: scale(0.97); }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .btn-outline { background: transparent; color: #4a5b6e; border: 1px solid rgba(30, 43, 60, 0.1); }
        .btn-outline:hover { background: rgba(30, 43, 60, 0.04); }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 500; color: #4a5b6e; margin-bottom: 4px; }
        .form-group label .required { color: #e74c3c; margin-left: 2px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 16px; border-radius: 40px; border: 1px solid rgba(30, 43, 60, 0.08); background: rgba(255, 255, 255, 0.5); font-size: 0.9rem; outline: none; transition: border-color 0.15s; font-family: inherit; color: #1a2634; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #1a2634; }
        .form-group textarea { border-radius: 20px; resize: vertical; min-height: 80px; }
        .form-group .file-input-wrapper { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .form-group .file-input-wrapper input[type="file"] { padding: 6px 0; border: none; background: transparent; width: auto; flex: 1; min-width: 160px; }
        .form-group .file-input-wrapper .file-info { font-size: 0.8rem; color: #7a8a9e; background: rgba(30, 43, 60, 0.04); padding: 2px 14px; border-radius: 40px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }

        .form-actions { display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap; }

        /* ---- 图片列表样式（含删除按钮） ---- */
        .existing-images {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
        }
        .existing-images .image-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.06);
            overflow: hidden;
            background: #e8ecf0;
            flex-shrink: 0;
        }
        .existing-images .image-item .img-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .existing-images .image-item .delete-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: none;
            background: rgba(231, 76, 60, 0.85);
            color: #fff;
            font-size: 14px;
            line-height: 22px;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
            padding: 0;
            font-weight: 700;
        }
        .existing-images .image-item .delete-btn:hover {
            background: #c0392b;
        }
        .existing-images .image-item .image-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
            color: #fff;
            font-size: 0.55rem;
            text-align: center;
            padding: 2px 0;
            letter-spacing: 0.5px;
        }
        .existing-images .empty-msg {
            color: #7a8a9e;
            font-size: 0.85rem;
            padding: 6px 0;
        }

        .message { padding: 12px 20px; border-radius: 40px; margin-bottom: 16px; font-size: 0.9rem; }
        .message.success { background: rgba(39, 174, 96, 0.1); color: #1a7a4a; border: 1px solid rgba(39, 174, 96, 0.2); }
        .message.error { background: rgba(231, 76, 60, 0.1); color: #c0392b; border: 1px solid rgba(231, 76, 60, 0.2); }

        .hint-text {
            font-size: 0.75rem;
            color: #7a8a9e;
            margin-top: 4px;
            display: block;
        }

        @media (max-width: 700px) {
            .admin-nav { padding: 10px 16px; border-radius: 32px; flex-direction: column; align-items: stretch; text-align: center; }
            .admin-nav .nav-links { justify-content: center; flex-wrap: wrap; }
            .admin-nav .user-info { justify-content: center; }
            .card { padding: 20px 16px 24px; border-radius: 32px; }
            .existing-images .image-item { width: 60px; height: 60px; }
        }
    </style>
</head>
<body>

    <div style="max-width:900px;margin:0 auto;padding:24px;">

        <!-- ===== 导航栏 ===== -->
        <div class="admin-nav">
            <div class="logo">后台管理 <span>· 高数试题库</span></div>
            <div class="nav-links">
                <a href="index.php">试卷列表</a>
                <a href="add.php">添加试卷</a>
                <a href="notice.php">公告管理</a>
                <a href="profile.php">个人信息</a>
            </div>
            <div class="user-info">
                管理员
                <a href="logout.php" class="logout" onclick="return confirm('确认退出登录？')">退出</a>
            </div>
        </div>

        <!-- ===== 编辑试卷 ===== -->
        <div class="card">
            <div class="card-title">
                编辑试卷
                <span style="font-size:0.8rem;font-weight:400;color:#4a5b6e;">ID: <?= $id ?></span>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="editForm">
                <div class="form-group">
                    <label>试卷名称 <span class="required">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($paper['title']) ?>" required />
                </div>

                <div class="form-group">
                    <label>试卷介绍</label>
                    <textarea name="description" rows="4" placeholder="介绍这套试卷的内容、难度、考查范围、适用专业等..."><?= htmlspecialchars($paper['description'] ?? '') ?></textarea>
                    <span class="hint-text">支持换行，会完整显示在试卷详情页的顶部</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>所属学校 <span class="required">*</span></label>
                        <select name="school" required>
                            <?php foreach ($existingSchools as $school): ?>
                                <option value="<?= htmlspecialchars($school) ?>" <?= $school === $paper['school'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($school) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__new__">+ 添加新学校</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>新学校名称</label>
                        <input type="text" name="new_school" placeholder="输入新学校名称" />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>年份 <span class="required">*</span></label>
                        <input type="text" name="year" value="<?= htmlspecialchars($paper['year']) ?>" placeholder="例如：2026" required />
                    </div>
                    <div class="form-group">
                        <label>学期 <span class="required">*</span></label>
                        <select name="semester" required>
                            <option value="秋" <?= $paper['semester'] === '秋' ? 'selected' : '' ?>>秋季</option>
                            <option value="春" <?= $paper['semester'] === '春' ? 'selected' : '' ?>>春季</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>缩略图（选填，替换当前缩略图）</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="thumbnail" accept="image/*" />
                        <span class="file-info">当前：<?= $paper['thumbnail'] ? basename($paper['thumbnail']) : '无' ?></span>
                    </div>
                </div>

                <!-- ===== 试卷图片 ===== -->
                <div class="form-group">
                    <label>试卷图片（追加新图片，不会覆盖已有）</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="paper_images[]" accept="image/*" multiple />
                        <span class="file-info">已有 <?= count($paperImages) ?> 张</span>
                    </div>
                    <div class="existing-images" id="paperImagesContainer">
                        <?php if (empty($paperImages)): ?>
                            <span class="empty-msg">暂无图片</span>
                        <?php else: ?>
                            <?php foreach ($paperImages as $index => $img): ?>
                                <div class="image-item" data-type="paper" data-index="<?= $index ?>" data-path="<?= htmlspecialchars($img) ?>">
                                    <img src="<?= htmlspecialchars($img) ?>" class="img-thumb" alt="试卷图" />
                                    <button type="button" class="delete-btn" onclick="deleteImage(this, 'paper', <?= $id ?>, <?= $index ?>)">✕</button>
                                    <span class="image-label">试卷</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ===== 标准答案图片 ===== -->
                <div class="form-group">
                    <label>标准答案图片（追加新图片）</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="answer_images[]" accept="image/*" multiple />
                        <span class="file-info">已有 <?= count($answerImages) ?> 张</span>
                    </div>
                    <div class="existing-images" id="answerImagesContainer">
                        <?php if (empty($answerImages)): ?>
                            <span class="empty-msg">暂无图片</span>
                        <?php else: ?>
                            <?php foreach ($answerImages as $index => $img): ?>
                                <div class="image-item" data-type="answer" data-index="<?= $index ?>" data-path="<?= htmlspecialchars($img) ?>">
                                    <img src="<?= htmlspecialchars($img) ?>" class="img-thumb" alt="答案图" />
                                    <button type="button" class="delete-btn" onclick="deleteImage(this, 'answer', <?= $id ?>, <?= $index ?>)">✕</button>
                                    <span class="image-label">答案</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ===== 我的解答图片 ===== -->
                <div class="form-group">
                    <label>我的解答图片（追加新图片）</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="solution_images[]" accept="image/*" multiple />
                        <span class="file-info">已有 <?= count($solutionImages) ?> 张</span>
                    </div>
                    <div class="existing-images" id="solutionImagesContainer">
                        <?php if (empty($solutionImages)): ?>
                            <span class="empty-msg">暂无图片</span>
                        <?php else: ?>
                            <?php foreach ($solutionImages as $index => $img): ?>
                                <div class="image-item" data-type="solution" data-index="<?= $index ?>" data-path="<?= htmlspecialchars($img) ?>">
                                    <img src="<?= htmlspecialchars($img) ?>" class="img-thumb" alt="解答图" />
                                    <button type="button" class="delete-btn" onclick="deleteImage(this, 'solution', <?= $id ?>, <?= $index ?>)">✕</button>
                                    <span class="image-label">解答</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">保存修改</button>
                    <a href="index.php" class="btn btn-outline">返回列表</a>
                </div>
            </form>
        </div>

    </div>

    <script>
        // ===== 删除图片函数 =====
        function deleteImage(btn, type, paperId, index) {
            if (!confirm('确定要删除这张图片吗？此操作不可恢复！')) {
                return;
            }

            // 显示加载状态
            btn.textContent = '…';
            btn.disabled = true;

            fetch('delete_image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'paper_id=' + paperId + '&type=' + type + '&index=' + index
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 删除成功，移除图片元素
                    var imageItem = btn.closest('.image-item');
                    var container = imageItem.parentElement;
                    imageItem.remove();

                    // 如果容器内没有图片了，显示"暂无图片"
                    var remainingImages = container.querySelectorAll('.image-item');
                    if (remainingImages.length === 0) {
                        container.innerHTML = '<span class="empty-msg">暂无图片</span>';
                    }

                    // 刷新页面显示最新的数据（可选）
                    // location.reload();
                } else {
                    alert('删除失败：' + data.message);
                    btn.textContent = '✕';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('请求失败，请重试');
                btn.textContent = '✕';
                btn.disabled = false;
                console.error('Error:', error);
            });
        }

        // 处理"添加新学校"逻辑
        document.querySelector('select[name="school"]').addEventListener('change', function() {
            const newSchoolInput = document.querySelector('input[name="new_school"]');
            if (this.value === '__new__') {
                newSchoolInput.style.display = 'block';
                newSchoolInput.required = true;
                newSchoolInput.focus();
            } else {
                newSchoolInput.style.display = 'none';
                newSchoolInput.required = false;
            }
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const select = document.querySelector('select[name="school"]');
            const newSchoolInput = document.querySelector('input[name="new_school"]');
            if (select.value === '__new__') {
                if (newSchoolInput.value.trim() === '') {
                    e.preventDefault();
                    alert('请输入新学校名称');
                    newSchoolInput.focus();
                    return;
                }
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'school';
                hidden.value = newSchoolInput.value.trim();
                select.name = 'school_old';
                this.appendChild(hidden);
            }
        });
    </script>

</body>
</html>