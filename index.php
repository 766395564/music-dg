<?php
// 文件名：index.php
// 版本：V10.0 (终极合体版 - 伪造大陆IP + 咪咕正版曲库)
// 目的：在印尼服务器上，骗过咪咕音乐，拿到周杰伦原唱

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// 1. 生成随机中国大陆 IP (欺诈核心)
function get_random_china_ip() {
    $prefixes = ['116.25', '116.76', '113.65', '119.123', '14.23', '14.116', '211.136'];
    $prefix = $prefixes[array_rand($prefixes)];
    $suffix = rand(1, 254) . '.' . rand(1, 254);
    return $prefix . '.' . $suffix;
}

// 2. 请求咪咕接口 (带上伪造的身份证)
function request_migu($keyword) {
    $fake_ip = get_random_china_ip();
    $url = "https://m.music.migu.cn/migu/remoting/scr_search_tag?rows=10&type=2&keyword=" . urlencode($keyword) . "&pgc=1";

    $headers = [
        'Referer: https://m.music.migu.cn/',
        'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        // === 关键：告诉咪咕我在国内 ===
        'X-Real-IP: ' . $fake_ip,
        'X-Forwarded-For: ' . $fake_ip,
        'Client-IP: ' . $fake_ip
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output, true);
}

// 3. 执行搜索
$migu_data = request_migu($word);

// 4. 解析结果
if ($migu_data && isset($migu_data['musics'][0])) {
    $song = $migu_data['musics'][0]; // 取第一首
    
    // 咪咕的字段
    $title = $song['songName'];
    $singer = $song['singerName'];
    $cover = $song['cover'] ?? "";
    $music_url = $song['mp3'] ?? ""; // 咪咕直接给mp3链接
    $lyric = $song['lyrics'] ?? "";

    // 协议修复 (http -> https)
    if (strpos($music_url, 'http://') === 0) {
        $music_url = str_replace('http://', 'https://', $music_url);
    }
    if (strpos($cover, 'http://') === 0) {
        $cover = str_replace('http://', 'https://', $cover);
    }

    echo json_encode([
        "code"      => 200,
        "title"     => $title,
        "singer"    => $singer,
        "cover"     => $cover,
        "music_url" => $music_url,
        "lyric"     => $lyric
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    // 失败
    echo json_encode([
        "code" => 404, 
        "msg" => "未找到歌曲 (伪装IP成功，但咪咕搜索无结果)",
        "debug_raw" => "MIGU_EMPTY"
    ]);
}
?>
