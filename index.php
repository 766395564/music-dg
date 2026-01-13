<?php
// 文件名：index.php
// 版本：V15.0 (网友接口精准适配版)
// 逻辑：Zeabur -> hb.ley.wang (QQ源) -> APP

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取搜索词
$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// 2. 目标接口 (网友提供的那个)
$targetUrl = "https://hb.ley.wang/qq.php?word=" . urlencode($word);

// 3. 代理请求
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
// 伪装成普通浏览器，防止被拦截
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 4. 解析数据
$data = json_decode($response, true);

// 5. 格式适配 (根据你提供的截图精准匹配)
if ($data && !empty($data['music_url'])) {
    
    // 强制把 http 转成 https (现在的 App 都不允许 http 了)
    $final_url = $data['music_url'];
    $final_cover = $data['cover'];
    
    if (strpos($final_url, 'http://') === 0) {
        $final_url = str_replace('http://', 'https://', $final_url);
    }
    if (strpos($final_cover, 'http://') === 0) {
        $final_cover = str_replace('http://', 'https://', $final_cover);
    }

    // 输出给 APP
    echo json_encode([
        "code"      => 200,
        "title"     => $data['title'] ?? "未知歌名",
        "singer"    => $data['singer'] ?? "未知歌手",
        "cover"     => $final_cover,
        "music_url" => $final_url,
        "lyric"     => "[00:00.00]此源暂无歌词"
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    // 失败处理
    $msg = $data['msg'] ?? "未找到歌曲或接口报错";
    echo json_encode([
        "code" => 404, 
        "msg" => "网友接口未返回有效链接: " . $msg,
        "debug_raw" => $data // 调试用
    ], JSON_UNESCAPED_UNICODE);
}
?>
