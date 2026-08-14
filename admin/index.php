<?php
// admin/index.php - 后台首页

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$dbFile = __DIR__ . '/../database/papers.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 获取所有试卷
$stmt = $db->query("SELECT * FROM papers ORDER BY created_at DESC");
$papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 分页
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$total = count($papers);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$papers = array_slice($papers, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>后台管理 · 高数试题库</title>
    <link rel="stylesheet" href="../assets/style.css" />
    <style>
        /* 后台专用样式 */
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

        .admin-nav .logo {
            font-size: 1rem;
            font-weight: 650;
            color: #1a2634;
        }

        .admin-nav .logo span {
            color: #4a5b6e;
            font-weight: 400;
        }

        .admin-nav .nav-links {
            display: flex;
            gap: 20px;
            font-size: 0.85rem;
            font-weight: 480;
            color: #4a5b6e;
        }

        .admin-nav .nav-links a {
            transition: color 0.15s;
            padding: 4px 0;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            text-decoration: none;
            color: #4a5b6e;
        }

        .admin-nav .nav-links a:hover,
        .admin-nav .nav-links a.active {
            color: #1a2634;
            border-bottom-color: #1a2634;
        }

        .admin-nav .nav-links a.active {
            color: #1a2634;
            font-weight: 550;
        }

        .admin-nav .user-info {
            font-size: 0.8rem;
            color: #4a5b6e;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-nav .user-info .logout {
            color: #c0392b;
            cursor: pointer;
            font-weight: 500;
        }

        .admin-nav .user-info .logout:hover {
            text-decoration: underline;
        }

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

        .card-title {
            font-size: 1.2rem;
            font-weight: 650;
            color: #1a2634;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px dashed rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-title .sub {
            font-size: 0.85rem;
            font-weight: 400;
            color: #4a5b6e;
        }

        .btn {
            display: inline-block;
            padding: 8px 24px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            font-family: inherit;
            text-decoration: none;
            color: #fff;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #219a52;
        }

        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-outline {
            background: transparent;
            color: #4a5b6e;
            border: 1px solid rgba(30, 43, 60, 0.1);
            padding: 6px 16px;
            font-size: 0.75rem;
        }
        .btn-outline:hover {
            background: rgba(30, 43, 60, 0.04);
        }

        .btn-sm {
            padding: 4px 14px;
            font-size: 0.75rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        table.admin-table th {
            text-align: left;
            padding: 10px 8px 8px 0;
            font-weight: 600;
            color: #4a5b6e;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        table.admin-table td {
            padding: 12px 8px 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            color: #1f2d3d;
            vertical-align: middle;
        }

        table.admin-table tr:last-child td {
            border-bottom: none;
        }

        table.admin-table .col-school { width: 18%; }
        table.admin-table .col-title { width: 32%; }
        table.admin-table .col-meta { width: 20%; }
        table.admin-table .col-actions { width: 30%; text-align: right; }

        table.admin-table .tag {
            display: inline-block;
            background: rgba(30, 43, 60, 0.05);
            padding: 0 12px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 500;
            color: #4a5b6e;
            line-height: 1.9;
        }

        table.admin-table .btn-group {
            display: flex;
            gap: 4px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            color: #7a8a9e;
            padding: 30px 0 10px;
            font-size: 0.95rem;
        }

        .pagination-bar {
            margin-top: 16px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .pagination-bar .page-info {
            font-size: 0.8rem;
            color: #4a5b6e;
        }

        .pagination-bar a {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 0.8rem;
            background: rgba(30, 43, 60, 0.04);
            color: #4a5b6e;
            text-decoration: none;
            transition: background 0.15s;
        }

        .pagination-bar a:hover {
            background: rgba(30, 43, 60, 0.1);
        }

        .pagination-bar a.active {
            background: #1a2634;
            color: #fff;
        }

        @media (max-width: 700px) {
            .admin-nav { padding: 10px 16px; border-radius: 32px; flex-direction: column; align-items: stretch; text-align: center; }
            .admin-nav .nav-links { justify-content: center; flex-wrap: wrap; }
            .admin-nav .user-info { justify-content: center; }
            .card { padding: 20px 16px 24px; border-radius: 32px; }
            table.admin-table { font-size: 0.8rem; }
            table.admin-table .col-school { width: 22%; }
            table.admin-table .col-title { width: 28%; }
            table.admin-table .col-meta { width: 18%; }
            table.admin-table .col-actions { width: 32%; }
            table.admin-table .btn { font-size: 0.65rem; padding: 2px 10px; }
        }

        @media (max-width: 480px) {
            table.admin-table .col-meta { display: none; }
            table.admin-table .col-school { width: 28%; }
            table.admin-table .col-title { width: 32%; }
            table.admin-table .col-actions { width: 40%; }
        }
    </style>
</head>
<body>

    <div style="max-width:1100px;margin:0 auto;padding:24px;">

        <!-- ===== 导航栏 ===== -->
        <div class="admin-nav">
            <div class="logo">后台管理 <span>· 高数试题库</span></div>
            <div class="nav-links">
                <a href="index.php" class="active">试卷列表</a>
                <a href="add.php">添加试卷</a>
                <a href="notice.php">公告管理</a>
                <a href="profile.php">个人信息</a>
            </div>
            <div class="user-info">
                管理员
                <a href="logout.php" class="logout" onclick="return confirm('确认退出登录？')">退出</a>
            </div>
        </div>

        <!-- ===== 试卷列表 ===== -->
        <div class="card">
            <div class="card-title">
                已收录试卷
                <span class="sub">共 <?= $total ?> 套</span>
            </div>

            <div class="table-wrap">
                <?php if (empty($papers)): ?>
                    <div class="empty-state">暂无试卷，<a href="add.php" style="color:#1a2634;font-weight:500;">去添加第一套</a></div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-school">学校</th>
                                <th class="col-title">试卷名称</th>
                                <th class="col-meta">年份 · 学期</th>
                                <th class="col-actions">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($papers as $paper): ?>
                                <tr>
                                    <td class="col-school"><span class="tag"><?= htmlspecialchars($paper['school']) ?></span></td>
                                    <td class="col-title"><?= htmlspecialchars($paper['title']) ?></td>
                                    <td class="col-meta"><?= htmlspecialchars($paper['year']) ?> · <?= htmlspecialchars($paper['semester']) ?></td>
                                    <td class="col-actions">
                                        <div class="btn-group">
                                            <a href="edit.php?id=<?= $paper['id'] ?>" class="btn btn-sm btn-outline">编辑</a>
                                            <a href="delete.php?id=<?= $paper['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除这套试卷？')">删除</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-bar">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>">← 上一页</a>
                            <?php endif; ?>
                            <span class="page-info">第 <?= $page ?> / <?= $totalPages ?> 页</span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>">下一页 →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</body>
</html>