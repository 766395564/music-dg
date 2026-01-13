<?php
// 文件名：index.php
// 版本：V12.0 (移花接木版)
// 核心逻辑：结合 V9 的成功网络伪装 + 酷我的周杰伦曲库

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// 1. 核心科技：生成中国大陆 IP (这是之前 V9 成功的关键)
function get_fake_ip() {
    $prefixes = ['116.25', '116.76', '113.65', '119.123', '14.23', '211.136'];
    $prefix = $prefixes[array_rand($prefixes)];
    return $prefix . '.' . rand(1, 254) . '.' . rand(1, 254);
}

// 2. 酷我请求函数 (带全套伪装)
function kuwo_request($url) {
    $fake_ip = get_fake_ip();
    $headers = [
        'Referer: http://www.kuwo.cn/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
        'Cookie: kw_token=HY520',
        'csrf: HY520',
        // === 关键：把 Zeabur 的印尼 IP 伪装成国内 IP ===
        'X-Real-IP: ' . $fake_ip,
        'X-Forwarded-For: ' . $fake_ip,
        'Client-IP: ' . $fake_ip
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

// 3. 第一步：搜索歌曲 (获取 RID)
$searchUrl = "http://www.kuwo.cn/api/www/search/searchMusicBykeyWord?key=" . urlencode($word) . "&pn=1&rn=1&httpsStatus=1";
$searchData = kuwo_request($searchUrl);

if ($searchData && isset($searchData['data']['list'][0])) {
    $song = $searchData['data']['list'][0];
    $rid = $song['rid'];
    $title = $song['name'];
    $singer = $song['artist'];
    $cover = $song['pic'] ?? "";
    
    // 4. 第二步：获取播放链接
    $playUrlApi = "http://www.kuwo.cn/api/v1/www/music/playUrl?mid={$rid}&type=music&httpsStatus=1";
    $playData = kuwo_request($playUrlApi);

    if ($playData && !empty($playData['data']['url'])) {
        $music_url = $playData['data']['url'];
        
        // 确保 https (App通常强制要求)
        $music_url = str_replace("http://", "https://", $music_url);
        $cover = str_replace("http://", "https://", $cover);

        // 成功输出
        echo json_encode([
            "code"      => 200,
            "title"     => $title,
            "singer"    => $singer, // 这里应该是“周杰伦”
            "cover"     => $cover,
            "music_url" => $music_url,
            "lyric"     => "[00:00.00]酷我源暂不解析歌词"
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // 搜到了但没链接（可能是付费歌）
        echo json_encode([
            "code" => 404, 
            "msg" => "找到歌曲但无法播放 (可能是VIP付费歌曲)", 
            "debug_raw" => "KUWO_PLAY_FAIL"
        ]);
    }
} else {
    // 连搜都搜不到
    echo json_encode([
        "code" => 404, 
        "msg" => "未找到歌曲 (酷我接口连接失败)", 
        "debug_raw" => "KUWO_SEARCH_FAIL"
    ]);
}
?>
