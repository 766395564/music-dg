<?php
// 文件名：index.php
// 用途：独立的点歌服务
// 核心：使用稳定源 api.liuzhijin.cn

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// 1. 获取参数
$word = $_GET['word'] ?? $_GET['keyword'] ?? '';

// 2. 没传歌名直接返回错误
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// 3. 请求源接口 (使用你确认过的稳定源)
$apiUrl = "https://api.liuzhijin.cn/music/?type=search&word=" . urlencode($word);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

// 4. 解析数据
$data = json_decode($response, true);

// 5. 格式转换 (扁平化处理)
if ($data && isset($data['data'][0])) {
    $song = $data['data'][0];
    $output = [
        "code"      => 200,
        "title"     => $song['title'] ?? "未知歌名",
        "singer"    => $song['author'] ?? "未知歌手",
        "cover"     => $song['pic'] ?? "",
        "music_url" => $song['url'] ?? "",
        "lyric"     => $song['lrc'] ?? "[00:00.00]暂无歌词"
    ];
} else {
    $output = ["code" => 404, "msg" => "未找到歌曲"];
}

// 6. 输出结果
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
