<?php
// 文件名：index.php
// 版本：V9.0 (海外突围版 - 深度伪造中国大陆IP)

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// ==========================================
// 核心技术：生成随机的中国大陆 IP 地址
// ==========================================
function get_random_china_ip() {
    // 这里的网段都是常见的国内家庭宽带网段
    $prefixes = ['116.25', '116.76', '113.65', '119.123', '14.23', '14.116'];
    $prefix = $prefixes[array_rand($prefixes)];
    $suffix = rand(1, 254) . '.' . rand(1, 254);
    return $prefix . '.' . $suffix;
}

// 请求函数 (带全套伪装)
function request_netease($url, $data = null) {
    $fake_ip = get_random_china_ip();
    
    $headers = [
        'Referer: https://music.163.com/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
        // === 关键：伪造 IP ===
        'X-Real-IP: ' . $fake_ip,
        'X-Forwarded-For: ' . $fake_ip,
        'Client-IP: ' . $fake_ip,
        'Cookie: os=pc; appver=2.9.7;' // 伪装成 PC 客户端
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output, true);
}

// 1. 调用网易云官方搜索接口
$searchUrl = "http://music.163.com/api/search/get/web?csrf_token=";
$postData = "s=" . urlencode($word) . "&type=1&offset=0&total=true&limit=5";

$wy_data = request_netease($searchUrl, $postData);

// 2. 解析结果
if ($wy_data && isset($wy_data['result']['songs'][0])) {
    $song = $wy_data['result']['songs'][0];
    
    // 3. 获取 ID 拼装链接 (这种方式在海外最稳)
    $music_id = $song['id'];
    $music_url = "http://music.163.com/song/media/outer/url?id=$music_id.mp3";
    
    $cover = $song['album']['picUrl'] ?? "";
    $cover = str_replace("http://", "https://", $cover);

    $singer = $song['artists'][0]['name'] ?? "未知歌手";

    echo json_encode([
        "code"      => 200,
        "title"     => $song['name'],
        "singer"    => $singer,
        "cover"     => $cover,
        "music_url" => $music_url,
        "lyric"     => "[00:00.00]海外版暂不解析歌词" 
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    // 失败诊断
    $debug_msg = "IP伪装失效";
    if (isset($wy_data['code']) && $wy_data['code'] != 200) {
        $debug_msg = "网易云拒绝了伪装请求 (Code: " . $wy_data['code'] . ")";
    } else if (empty($wy_data)) {
        $debug_msg = "网络请求无返回 (可能是Zeabur出口被封)";
    }

    echo json_encode([
        "code" => 404, 
        "msg" => "未找到歌曲 (服务器在印尼，已尝试伪装IP但仍可能被拦截)",
        "debug" => $debug_msg
    ], JSON_UNESCAPED_UNICODE);
}
?>
