<?php
// admin/delete.php - 删除试卷

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

// 获取试卷信息（用于删除图片文件）
$stmt = $db->prepare("SELECT * FROM papers WHERE id = ?");
$stmt->execute([$id]);
$paper = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paper) {
    header('Location: index.php');
    exit;
}

// 删除数据库记录
$stmt = $db->prepare("DELETE FROM papers WHERE id = ?");
$stmt->execute([$id]);

// 删除关联的图片文件夹（可选）
$paperDir = __DIR__ . '/../uploads/paper_' . $id . '/';
if (is_dir($paperDir)) {
    // 递归删除文件夹
    $files = array_diff(scandir($paperDir), ['.', '..']);
    foreach ($files as $file) {
        unlink($paperDir . $file);
    }
    rmdir($paperDir);
}

header('Location: index.php?deleted=1');
exit;