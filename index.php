<?php
// 文件名：index.php
// 版本：V23.0 (韩小韩源 + 完美伪装版)
// 核心：里子换成了更稳的 api.vvhan.com，面子依然伪装成 hbmusic.1yo.cc

// 1. 强力清洗 (防止 App 报错)
ob_start();
ob_clean();

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 2. 获取参数 (支持 ?name= 和 ?word=)
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

// 5. 【核心更换】里子换成“韩小韩 API”
// 这是一个长期稳定的公益源，比之前的个人源更靠谱
$targetUrl = "https://api.vvhan.com/api/music?type=search&txt=" . urlencode($name);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
// 伪装 User-Agent
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 6. 解析韩小韩的数据
$data = json_decode($response, true);

// 7. 【格式整容】把韩小韩的数据，整容成 hbmusic 的样子
// 韩小韩返回 success:true 和 info 字段
if ($data && isset($data['success']) && $data['success'] == true) {
    $info = $data['info'];
    
    // 提取字段
    $song_title = $info['name'] ?? "未知歌曲";
    $song_singer = $info['auther'] ?? $info['author'] ?? "未知歌手"; // 兼容它的拼写
    $song_cover = $info['img'] ?? "";
    $song_url = $info['mp3url'] ?? $info['url'] ?? "";

    // 强制 https
    $final_url = str_replace('http://', 'https://', $song_url);
    $final_cover = str_replace('http://', 'https://', $song_cover);
    
    // 构造最终数组 (完全符合你的文档要求)
    $output = [
        "code"      => 200,
        "title"     => (string)$song_title,
        "singer"    => (string)$song_singer,
        "cover"     => (string)$final_cover,
        // === 关键：补全 link 字段 ===
        "link"      => (string)$final_url, 
        "music_url" => (string)$final_url,
        "lyric"     => "[00:00.00]备用源暂无歌词"
    ];

    send_json($output);

} else {
    // 失败处理
    send_json([
        "code" => 404, 
        "msg" => "备用源未找到歌曲",
        "title" => "无结果",
        "link" => "",
        "music_url" => ""
    ]);
}
?>
