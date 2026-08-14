<?php
// admin/notice.php - 公告管理

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$dbFile = __DIR__ . '/../database/papers.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 获取公告数据
$stmt = $db->query("SELECT * FROM notice LIMIT 1");
$notice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notice) {
    // 如果没有公告数据，插入默认
    $db->exec("
        INSERT INTO notice (content, auto_popup)
        VALUES ('欢迎来到高数试题库！本站持续收录广东各高校高等数学期中期末试卷，每套均附标准答案与个人解答过程。如有问题请联系管理员。', 0)
    ");
    $stmt = $db->query("SELECT * FROM notice LIMIT 1");
    $notice = $stmt->fetch(PDO::FETCH_ASSOC);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $auto_popup = isset($_POST['auto_popup']) ? 1 : 0;

    $stmt = $db->prepare("
        UPDATE notice SET
            content = ?,
            auto_popup = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$content, $auto_popup, $notice['id']]);

    $message = '公告保存成功！';
    // 刷新数据
    $stmt = $db->query("SELECT * FROM notice LIMIT 1");
    $notice = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>公告管理 · 后台管理</title>
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
        .card-title { font-size: 1.2rem; font-weight: 650; color: #1a2634; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px dashed rgba(0, 0, 0, 0.06); }

        .btn { display: inline-block; padding: 8px 24px; border-radius: 40px; font-size: 0.85rem; font-weight: 500; border: none; cursor: pointer; transition: background 0.15s, transform 0.1s; font-family: inherit; text-decoration: none; color: #fff; }
        .btn:active { transform: scale(0.97); }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .btn-outline { background: transparent; color: #4a5b6e; border: 1px solid rgba(30, 43, 60, 0.1); }
        .btn-outline:hover { background: rgba(30, 43, 60, 0.04); }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 500; color: #4a5b6e; margin-bottom: 4px; }
        .form-group label .required { color: #e74c3c; margin-left: 2px; }
        .form-group textarea { width: 100%; padding: 10px 16px; border-radius: 20px; border: 1px solid rgba(30, 43, 60, 0.08); background: rgba(255, 255, 255, 0.5); font-size: 0.9rem; outline: none; transition: border-color 0.15s; font-family: inherit; color: #1a2634; resize: vertical; min-height: 150px; }
        .form-group textarea:focus { border-color: #1a2634; }

        .form-group .checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 4px;
        }
        .form-group .checkbox-wrap input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #1a2634;
            cursor: pointer;
        }
        .form-group .checkbox-wrap label {
            font-size: 0.9rem;
            font-weight: 400;
            color: #1a2634;
            cursor: pointer;
            margin-bottom: 0;
        }

        .hint-text {
            font-size: 0.75rem;
            color: #7a8a9e;
            margin-top: 4px;
            display: block;
        }

        .form-actions { display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap; }

        .message { padding: 12px 20px; border-radius: 40px; margin-bottom: 16px; font-size: 0.9rem; }
        .message.success { background: rgba(39, 174, 96, 0.1); color: #1a7a4a; border: 1px solid rgba(39, 174, 96, 0.2); }
        .message.error { background: rgba(231, 76, 60, 0.1); color: #c0392b; border: 1px solid rgba(231, 76, 60, 0.2); }

        @media (max-width: 700px) {
            .admin-nav { padding: 10px 16px; border-radius: 32px; flex-direction: column; align-items: stretch; text-align: center; }
            .admin-nav .nav-links { justify-content: center; flex-wrap: wrap; }
            .admin-nav .user-info { justify-content: center; }
            .card { padding: 20px 16px 24px; border-radius: 32px; }
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
                <a href="notice.php" class="active">公告管理</a>
                <a href="profile.php">个人信息</a>
            </div>
            <div class="user-info">
                管理员
                <a href="logout.php" class="logout" onclick="return confirm('确认退出登录？')">退出</a>
            </div>
        </div>

        <!-- ===== 公告管理 ===== -->
        <div class="card">
            <div class="card-title">公告管理</div>

            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>公告内容</label>
                    <textarea name="content" rows="6" placeholder="输入公告内容，支持换行..."><?= htmlspecialchars($notice['content'] ?? '') ?></textarea>
                    <span class="hint-text">支持换行，会完整显示在前端公告弹窗中</span>
                </div>

                <div class="form-group">
                    <div class="checkbox-wrap">
                        <input type="checkbox" name="auto_popup" id="auto_popup" <?= ($notice['auto_popup'] ?? 0) == 1 ? 'checked' : '' ?> />
                        <label for="auto_popup">访问网站时自动弹出公告</label>
                    </div>
                    <span class="hint-text">开启后，用户打开首页会自动弹出公告弹窗（用户可勾选"下次不再弹出"）</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">保存公告</button>
                </div>
            </form>
        </div>

    </div>

</body>
</html>