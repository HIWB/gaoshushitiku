<?php
// install.php - 首次访问运行，创建数据库表
// 运行后建议删除此文件

$dbFile = __DIR__ . '/database/papers.db';
$dbDir = dirname($dbFile);

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 创建试卷表（包含 description 字段）
$db->exec("
CREATE TABLE papers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    school TEXT NOT NULL,
    title TEXT NOT NULL,
    year TEXT NOT NULL,
    semester TEXT NOT NULL,
    description TEXT DEFAULT '',
    thumbnail TEXT,
    paper_images TEXT NOT NULL,
    answer_images TEXT,
    solution_images TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)
");

// 创建个人信息表（包含 password 和 github 字段）
$db->exec("
CREATE TABLE profile (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT DEFAULT '小源',
    role TEXT DEFAULT '高数爱好者、试题整理人',
    bio TEXT DEFAULT '在校大学生，热爱高等数学，喜欢整理各高校高等数学试题。',
    email TEXT DEFAULT 'pasy@163.com',
    wechat TEXT DEFAULT '不告诉你',
    school TEXT DEFAULT '广州大专',
    avatar TEXT DEFAULT '/uploads/avatar/default.jpg',
    password TEXT DEFAULT 'admin123',
    github TEXT DEFAULT 'https://github.com/HIWB'
)
");

// 创建公告表
$db->exec("
CREATE TABLE notice (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content TEXT DEFAULT '',
    auto_popup INTEGER DEFAULT 0,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
)
");

// 插入默认公告
$db->exec("
INSERT INTO notice (content, auto_popup)
SELECT '# 高数试题库公告
欢迎使用本高等数学试题库！
题库持续收录各高校高数真题与专项习题，长期更新扩充。所有试题配有标准答案，个别附带完整详细解题过程，方便刷题复盘、考前复习。
题库会不断新增院校试卷、优化解析内容。
若资源存在错误、有相关疑问，可点击首页【关于】查看管理员联系方式进行反馈。
祝大家高数学习顺利！', 1
WHERE NOT EXISTS (SELECT 1 FROM notice)
");

// 插入默认个人信息
$db->exec("
INSERT INTO profile (name, role, bio, email, wechat, school, avatar, password, github)
SELECT '小源', '高数爱好者、试题整理人', '在校大学生，热爱高等数学，喜欢整理各高校高等数学试题。', 'pasy@163.com', '不告诉你', '广州大专', '/uploads/avatar/default.jpg', 'admin123', 'https://github.com/HIWB'
WHERE NOT EXISTS (SELECT 1 FROM profile)
");

echo "✅ 数据库安装成功！<br>";
echo "默认密码：admin123<br>";
echo "请删除 install.php 文件，然后访问 <a href='index.php'>首页</a> 或 <a href='admin/login.php'>后台</a>";