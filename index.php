<?php
// 文件名：index.php
// 版本：V5.0 (直连网易云官方版 - 解决IP被聚合源拉黑问题)

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取参数
$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// 2. 定义请求网易云官方的函数
function wy_search($keyword) {
    // 网易云官方搜索接口 (Legacy)
    $url = "http://music.163.com/api/search/get/web?csrf_token=";
    $post_data = "s=" . urlencode($keyword) . "&type=1&offset=0&total=true&limit=5";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // 关键：伪装 Referer，让网易云以为我们是网页版
    $headers = [
        'Referer: https://music.163.com/',
        'Cookie: appver=2.0.2',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output, true);
}

// 3. 执行搜索
$wy_data = wy_search($word);

// 4. 解析数据并转换格式
if ($wy_data && isset($wy_data['result']['songs'][0])) {
    $song = $wy_data['result']['songs'][0]; // 取第一首
    
    // 组装播放链接 (网易云外链公式)
    $music_id = $song['id'];
    $music_url = "http://music.163.com/song/media/outer/url?id=$music_id.mp3";
    
    // 组装封面 (如果有)
    $cover = $song['album']['picUrl'] ?? "";
    if(strpos($cover, 'http') === 0) {
        $cover = str_replace("http://", "https://", $cover); // 强转 https
    }

    // 组装歌手
    $singer = $song['artists'][0]['name'] ?? "未知歌手";

    $output = [
        "code"      => 200,
        "title"     => $song['name'],
        "singer"    => $singer,
        "cover"     => $cover,
        "music_url" => $music_url,
        "lyric"     => "[00:00.00]本接口为极速版，暂不解析歌词" // 搜索接口不带歌词，保证速度
    ];
} else {
    // 失败处理
    $output = [
        "code" => 404, 
        "msg" => "未找到歌曲 (网易云官方无结果)",
        "debug" => isset($wy_data['code']) ? $wy_data['code'] : "无响应"
    ];
}

// 5. 输出
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
