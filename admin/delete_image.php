<?php
// admin/delete_image.php - 删除试卷图片

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$dbFile = __DIR__ . '/../database/papers.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 获取参数
$paperId = isset($_POST['paper_id']) ? intval($_POST['paper_id']) : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';
$index = isset($_POST['index']) ? intval($_POST['index']) : -1;

// 验证参数
if ($paperId <= 0 || empty($type) || $index < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

// 允许的类型
$allowedTypes = ['paper', 'answer', 'solution'];
if (!in_array($type, $allowedTypes)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '类型错误']);
    exit;
}

// 获取试卷数据
$stmt = $db->prepare("SELECT * FROM papers WHERE id = ?");
$stmt->execute([$paperId]);
$paper = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paper) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '试卷不存在']);
    exit;
}

// 根据类型获取对应的图片数组
$fieldMap = [
    'paper' => 'paper_images',
    'answer' => 'answer_images',
    'solution' => 'solution_images'
];
$field = $fieldMap[$type];
$images = json_decode($paper[$field], true) ?: [];

// 检查索引是否存在
if (!isset($images[$index])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '图片不存在']);
    exit;
}

// 获取要删除的图片路径
$imagePath = $images[$index];

// 从数组中移除该图片
array_splice($images, $index, 1);

// 更新数据库
$stmt = $db->prepare("UPDATE papers SET $field = ? WHERE id = ?");
$stmt->execute([json_encode($images), $paperId]);

// 尝试删除物理文件（如果存在）
$filePath = __DIR__ . '/..' . $imagePath;
if (file_exists($filePath)) {
    @unlink($filePath);
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => '删除成功']);