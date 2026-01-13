<?php
// 文件名：index.php
// 版本：V24.0 (Liuzhijin源回归版)
// 核心：使用你提供的解析代码里的源 (liuzhijin)，但强制输出为软件要求的 hbmusic 格式

// 1. 强力清洗
ob_start();
ob_clean();

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 2. 获取参数
$name = $_GET['name'] ?? $_GET['word'] ?? $_GET['keyword'] ?? '';

// 3. 定义输出函数
function send_json($data) {
    ob_clean(); 
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 4. 空值检查
if (empty($name)) {
    send_json(["code" => 400, "msg" => "请在链接后输入歌名"]);
}

// 5. 【核心更换】使用 backup 文件里的 Liuzhijin 源
$targetUrl = "https://api.liuzhijin.cn/music/?type=search&word=" . urlencode($name);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 6. 解析 Liuzhijin 的数据
$data = json_decode($response, true);

// 7. 【关键逻辑】Liuzhijin 返回的是列表，我们需要提取第一首，并整容成 hbmusic 格式
if ($data && isset($data['data'][0])) {
    $song = $data['data'][0]; // 取第一首
    
    // 提取字段 (根据 backup 文件里的字段名)
    $title = $song['title'] ?? "未知歌曲";
    $singer = $song['author'] ?? "未知歌手";
    $cover = $song['pic'] ?? "";
    $music_url = $song['url'] ?? "";
    $lyric = $song['lrc'] ?? "";

    // 强制 https
    $final_url = str_replace('http://', 'https://', $music_url);
    $final_cover = str_replace('http://', 'https://', $cover);
    
    // 构造最终数组 (保持 link 字段，不动其他结构)
    $output = [
        "code"      => 200,
        "title"     => (string)$title,
        "singer"    => (string)$singer,
        "cover"     => (string)$final_cover,
        // 伪装关键：补全 link
        "link"      => (string)$final_url, 
        "music_url" => (string)$final_url,
        "lyric"     => (string)$lyric
    ];

    send_json($output);

} else {
    // 失败处理
    send_json([
        "code" => 404, 
        "msg" => "Liuzhijin源未找到歌曲或被拦截",
        "title" => "无结果",
        "link" => "",
        "music_url" => ""
    ]);
}
?>
