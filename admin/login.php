<?php
// admin/login.php - 管理员登录

session_start();

$dbFile = __DIR__ . '/../database/papers.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 获取数据库中的密码
$stmt = $db->query("SELECT password FROM profile LIMIT 1");
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
$dbPassword = $profile['password'] ?? 'admin123';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password === $dbPassword) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = '密码错误，请重试';
    }
}

// 如果已经登录，直接跳转后台
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>管理员登录 · 高数试题库</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background-color: #d1d6dd;
            background-image:
                linear-gradient(rgba(180, 188, 198, 0.35) 1px, transparent 1px),
                linear-gradient(90deg, rgba(180, 188, 198, 0.35) 1px, transparent 1px);
            background-size: 40px 40px;
            background-position: center center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            max-width: 400px;
            width: 100%;
            background: rgba(248, 250, 252, 0.92);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border-radius: 48px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 24px 48px -18px rgba(0, 0, 0, 0.25);
            padding: 40px 36px 44px;
            text-align: center;
        }

        .login-card .lock-icon {
            font-size: 2.4rem;
            margin-bottom: 6px;
            color: #4a5b6e;
        }

        .login-card h2 {
            font-size: 1.4rem;
            font-weight: 650;
            color: #1a2634;
            margin-bottom: 4px;
        }

        .login-card .sub {
            font-size: 0.85rem;
            color: #4a5b6e;
            margin-bottom: 24px;
        }

        .login-card .form-group {
            text-align: left;
            margin-bottom: 16px;
        }

        .login-card .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #4a5b6e;
            margin-bottom: 4px;
        }

        .login-card .form-group input {
            width: 100%;
            padding: 10px 16px;
            border-radius: 40px;
            border: 1px solid rgba(30, 43, 60, 0.08);
            background: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.15s;
            font-family: inherit;
        }

        .login-card .form-group input:focus {
            border-color: #1a2634;
        }

        .login-card .error-msg {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .btn {
            display: inline-block;
            padding: 10px 32px;
            border-radius: 40px;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            font-family: inherit;
            width: 100%;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background: #1a2634;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2c3e50;
        }

        .login-card .hint {
            margin-top: 16px;
            font-size: 0.75rem;
            color: #7a8a9e;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="lock-icon">🔐</div>
        <h2>管理员登录</h2>
        <p class="sub">请输入密码进入后台管理</p>

        <?php if (isset($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="请输入管理密码" autofocus />
            </div>
            <button type="submit" class="btn btn-primary">登录</button>
        </form>

        <p class="hint">默认密码：admin123（请及时修改）</p>
    </div>
</body>
</html>