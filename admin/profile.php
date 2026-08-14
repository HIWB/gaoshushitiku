<?php
// admin/profile.php - 个人信息编辑 + 修改密码

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$dbFile = __DIR__ . '/../database/papers.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 获取当前个人信息
$stmt = $db->query("SELECT * FROM profile LIMIT 1");
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    // 如果没有数据，插入默认（已修改为你提供的信息）
    $db->exec("
        INSERT INTO profile (name, role, bio, email, wechat, school, avatar, password, github)
        VALUES ('小源', '高数爱好者、试题整理人', '在校大学生，热爱高等数学，喜欢整理各高校高等数学试题。', 'pasy@163.com', '不告诉你', '广州大专', '/uploads/avatar/default.jpg', 'admin123', '')
    ");
    $stmt = $db->query("SELECT * FROM profile LIMIT 1");
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'password') {
        // ===== 修改密码 =====
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // 验证旧密码
        $currentPassword = $profile['password'] ?? 'admin123';
        if ($old_password !== $currentPassword) {
            $error = '原密码错误';
        } elseif (strlen($new_password) < 4) {
            $error = '新密码长度至少4位';
        } elseif ($new_password !== $confirm_password) {
            $error = '两次输入的新密码不一致';
        } else {
            $stmt = $db->prepare("UPDATE profile SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $profile['id']]);
            $message = '密码修改成功！';
            // 刷新数据
            $stmt = $db->query("SELECT * FROM profile LIMIT 1");
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        // ===== 修改个人信息 =====
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $wechat = trim($_POST['wechat'] ?? '');
        $school = trim($_POST['school'] ?? '');
        $github = trim($_POST['github'] ?? '');

        if (empty($name)) {
            $error = '请填写姓名';
        } else {
            $avatar = $profile['avatar'];

            // 处理头像上传
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/avatar/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                    $avatar = '/uploads/avatar/' . $filename;
                }
            }

            $stmt = $db->prepare("
                UPDATE profile SET
                    name = ?,
                    role = ?,
                    bio = ?,
                    email = ?,
                    wechat = ?,
                    school = ?,
                    avatar = ?,
                    github = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $role, $bio, $email, $wechat, $school, $avatar, $github, $profile['id']]);

            $message = '个人信息保存成功！';
            // 刷新数据
            $stmt = $db->query("SELECT * FROM profile LIMIT 1");
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>个人信息 · 后台管理</title>
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
        .form-group input, .form-group textarea { width: 100%; padding: 10px 16px; border-radius: 40px; border: 1px solid rgba(30, 43, 60, 0.08); background: rgba(255, 255, 255, 0.5); font-size: 0.9rem; outline: none; transition: border-color 0.15s; font-family: inherit; color: #1a2634; }
        .form-group input:focus, .form-group textarea:focus { border-color: #1a2634; }
        .form-group textarea { border-radius: 20px; resize: vertical; min-height: 70px; }
        .form-group .file-input-wrapper { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .form-group .file-input-wrapper input[type="file"] { padding: 6px 0; border: none; background: transparent; width: auto; flex: 1; min-width: 160px; }
        .form-group .file-input-wrapper .file-info { font-size: 0.8rem; color: #7a8a9e; background: rgba(30, 43, 60, 0.04); padding: 2px 14px; border-radius: 40px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }

        .form-actions { display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap; }

        .message { padding: 12px 20px; border-radius: 40px; margin-bottom: 16px; font-size: 0.9rem; }
        .message.success { background: rgba(39, 174, 96, 0.1); color: #1a7a4a; border: 1px solid rgba(39, 174, 96, 0.2); }
        .message.error { background: rgba(231, 76, 60, 0.1); color: #c0392b; border: 1px solid rgba(231, 76, 60, 0.2); }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            margin-top: 6px;
        }

        /* 密码修改区域分隔 */
        .section-divider {
            border-top: 2px dashed rgba(0, 0, 0, 0.06);
            margin: 28px 0 24px;
            padding-top: 6px;
        }
        .section-divider .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a2634;
            display: inline-block;
            background: rgba(248, 250, 252, 0.9);
            padding-right: 16px;
        }

        .password-hint {
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
                <a href="profile.php" class="active">个人信息</a>
            </div>
            <div class="user-info">
                管理员
                <a href="logout.php" class="logout" onclick="return confirm('确认退出登录？')">退出</a>
            </div>
        </div>

        <!-- ===== 个人信息 ===== -->
        <div class="card">
            <div class="card-title">个人信息</div>

            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="profile" />

                <div class="form-group">
                    <label>姓名 <span class="required">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required />
                </div>

                <div class="form-group">
                    <label>身份简介</label>
                    <input type="text" name="role" value="<?= htmlspecialchars($profile['role']) ?>" />
                </div>

                <div class="form-group">
                    <label>个人简介</label>
                    <textarea name="bio" rows="3"><?= htmlspecialchars($profile['bio']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>邮箱</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" />
                    </div>
                    <div class="form-group">
                        <label>微信</label>
                        <input type="text" name="wechat" value="<?= htmlspecialchars($profile['wechat']) ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label>GitHub 项目地址</label>
                    <input type="url" name="github" value="<?= htmlspecialchars($profile['github'] ?? '') ?>" placeholder="例如：https://github.com/yourname/yourrepo" />
                </div>

                <div class="form-group">
                    <label>学校</label>
                    <input type="text" name="school" value="<?= htmlspecialchars($profile['school']) ?>" />
                </div>

                <div class="form-group">
                    <label>头像</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="avatar" accept="image/*" />
                        <span class="file-info">推荐 400×400，自动裁剪为圆形</span>
                    </div>
                    <?php if (!empty($profile['avatar'])): ?>
                        <img src="<?= htmlspecialchars($profile['avatar']) ?>" class="avatar-preview" alt="当前头像" />
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">保存个人信息</button>
                </div>
            </form>

            <!-- ===== 修改密码区域 ===== -->
            <div class="section-divider">
                <span class="section-title">修改密码</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="password" />

                <div class="form-group">
                    <label>原密码 <span class="required">*</span></label>
                    <input type="password" name="old_password" placeholder="请输入当前密码" required />
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>新密码 <span class="required">*</span></label>
                        <input type="password" name="new_password" placeholder="至少4位" required />
                    </div>
                    <div class="form-group">
                        <label>确认新密码 <span class="required">*</span></label>
                        <input type="password" name="confirm_password" placeholder="再次输入新密码" required />
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">修改密码</button>
                </div>
            </form>

        </div>

    </div>

</body>
</html>