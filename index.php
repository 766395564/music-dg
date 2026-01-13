<?php
// 文件名：index.php
// 版本：V16.0 (格式强力清洗版)
// 专治：浏览器能看但App报错的问题（去除隐形空格/BOM头）

// 1. 开启缓冲区 (这是清洗的关键)
ob_start();

// 2. 基础设置
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 3. 获取参数
$word = $_GET['word'] ?? $_GET['keyword'] ?? '';

// 4. 定义输出函数 (统一出口，防止杂乱输出)
function send_json($data) {
    // === 核心清洗步骤 ===
    ob_clean(); // 清除之前所有可能的空格、报错、杂质
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit; // 立即结束，防止后面有空行
}

// 5. 空参数处理
if (empty($word)) {
    send_json(["code" => 400, "msg" => "请输入歌名"]);
}

// 6. 请求网友接口 (hb.ley.wang)
$targetUrl = "https://hb.ley.wang/qq.php?word=" . urlencode($word);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20); // 延长超时时间到20秒
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 7. 解析数据
$data = json_decode($response, true);

// 8. 格式适配
if ($data && !empty($data['music_url'])) {
    // 强制 https
    $final_url = str_replace('http://', 'https://', $data['music_url']);
    $final_cover = str_replace('http://', 'https://', $data['cover']);

    // 构造完全符合文档的纯净结构
    $output = [
        "code"      => 200,
        "title"     => (string)($data['title'] ?? "未知歌名"),
        "singer"    => (string)($data['singer'] ?? "未知歌手"),
        "cover"     => (string)$final_cover,
        "music_url" => (string)$final_url,
        "lyric"     => "[00:00.00]此源暂无歌词"
    ];
    
    send_json($output);

} else {
    // 失败处理
    send_json([
        "code" => 404, 
        "msg" => "未找到歌曲",
        "debug_info" => "Proxy response invalid"
    ]);
}
?>
