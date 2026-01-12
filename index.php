<?php
// 文件名：index.php
// 版本：V4.0 (反爬虫伪装 + 详细诊断版)

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取参数
$word = $_GET['word'] ?? $_GET['keyword'] ?? '';

// 2. 没传歌名直接返回
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请在链接后面加上 ?word=歌名"]);
    exit;
}

// 3. 定义请求函数 (带伪装)
function curl_request($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // === 关键伪装：假装是 Windows 10 的 Chrome 浏览器 ===
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// 4. 发起请求
$apiUrl = "https://api.liuzhijin.cn/music/?type=search&word=" . urlencode($word);
$response = curl_request($apiUrl);
$data = json_decode($response, true);

// 5. 结果处理
if ($data && isset($data['data'][0])) {
    // 成功的情况
    $song = $data['data'][0];
    $output = [
        "code"      => 200,
        "title"     => $song['title'],
        "singer"    => $song['author'],
        "cover"     => $song['pic'],
        "music_url" => $song['url'],
        "lyric"     => $song['lrc']
    ];
} else {
    // 失败的情况：开启“说真话”模式
    // 如果源站返回了任何文字，直接显示出来，方便诊断
    $output = [
        "code" => 404,
        "msg" => "未找到歌曲 (可能是源站拦截了 Zeabur IP)",
        "debug_raw" => $response ? $response : "源站没有任何返回 (空)", 
        "search_word" => $word
    ];
}

// 6. 输出结果
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
