<?php
// 文件名：index.php
// 版本：V26.0 (韩小韩单源版)
// 核心：只用一个最稳的源 (vvhan)，并严格伪装成 hbmusic 格式

// 1. 强力清洗 (确保 App 不报错)
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

// 5. 【核心源】使用韩小韩 (vvhan) 公益 API
// 这是一个长期稳定的源，通常不会屏蔽海外 IP
$targetUrl = "https://api.vvhan.com/api/music?type=search&txt=" . urlencode($name);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
// 伪装成浏览器
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 6. 解析数据
$data = json_decode($response, true);

// 7. 【关键步骤】格式整容
// 韩小韩返回的是 success:true, 我们要把它改成你软件要的 code:200
if ($data && isset($data['success']) && $data['success'] == true) {
    $info = $data['info'];
    
    // 提取并清洗字段
    $song_title = $info['name'] ?? "未知歌曲";
    $song_singer = $info['auther'] ?? $info['author'] ?? "未知歌手"; // 兼容它的拼写
    $song_cover = $info['img'] ?? "";
    $song_url = $info['mp3url'] ?? $info['url'] ?? "";

    // 强制 https
    $final_url = str_replace('http://', 'https://', $song_url);
    $final_cover = str_replace('http://', 'https://', $song_cover);
    
    // 构造最终数组 (完全符合 hbmusic 格式)
    $output = [
        "code"      => 200,
        "title"     => (string)$song_title,
        "singer"    => (string)$song_singer,
        "cover"     => (string)$final_cover,
        // 伪装关键：补全 link 字段
        "link"      => (string)$final_url, 
        "music_url" => (string)$final_url,
        "lyric"     => "[00:00.00]此源暂无歌词"
    ];

    send_json($output);

} else {
    // 失败处理
    send_json([
        "code" => 404, 
        "msg" => "未找到歌曲",
        "title" => "无结果",
        "link" => "",
        "music_url" => ""
    ]);
}
?>
