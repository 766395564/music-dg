<?php
// 文件名：index.php
// 版本：V27.0 (文档严格适配 + 唯一可用源)
// 核心：舍弃 Liuzhijin(已挂)，使用 hb.ley.wang，并严格匹配文档格式

// 1. 强力清洗 (防止空格/BOM头导致 App 解析失败)
ob_start();
ob_clean();

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 2. 获取参数
// 文档要求支持 name，同时兼容 word
$name = $_GET['name'] ?? $_GET['word'] ?? $_GET['keyword'] ?? '';

// 3. 定义标准输出函数
function send_json($data) {
    ob_clean(); 
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 4. 空值检查
if (empty($name)) {
    send_json([
        "code" => 400,
        "msg" => "歌名不能为空",
        "title" => "",
        "singer" => "",
        "cover" => "",
        "link" => "",
        "music_url" => ""
    ]);
}

// 5. 【关键】使用唯一在 Zeabur 上验证成功的源 (hb.ley.wang)
// Liuzhijin 已确认屏蔽 Zeabur，绝对不能再用了
$targetUrl = "https://hb.ley.wang/qq.php?word=" . urlencode($name);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
// 伪装成浏览器
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 6. 解析数据
$data = json_decode($response, true);

// 7. 【格式重组】严格对照你的文档截图
if ($data && !empty($data['music_url'])) {
    
    // 强制 HTTPS (App 现在的硬性要求)
    $final_url = str_replace('http://', 'https://', $data['music_url']);
    $final_cover = str_replace('http://', 'https://', $data['cover']);
    
    // 构造最终数组
    $output = [
        "code"      => 200,                // 文档要求: code为200
        "title"     => (string)$data['title'],
        "singer"    => (string)$data['singer'],
        "cover"     => (string)$final_cover,
        // 文档文字描述提到了 link，我们这里填入播放链接或封面链接，确保不缺字段
        "link"      => (string)$final_url, 
        "music_url" => (string)$final_url, // 文档要求: music_url
        "lyric"     => "[00:00.00]备用源暂无歌词"
    ];

    send_json($output);

} else {
    // 失败处理 (严格按照文档格式返回空)
    send_json([
        "code" => 404, 
        "msg" => "未找到歌曲",
        "title" => "无结果",
        "singer" => "",
        "cover" => "",
        "link" => "",
        "music_url" => ""
    ]);
}
?>
