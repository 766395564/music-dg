<?php
// 文件名：index.php
// 版本：V28.0 (解决“该账号没有会员”问题)
// 核心：代码格式保持 V27 的完美状态，但把内核换成“韩小韩 API”，解决 VIP 歌曲无法播放的问题

ob_start();
ob_clean();
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取参数 (严格适配文档)
$name = $_GET['name'] ?? $_GET['word'] ?? $_GET['keyword'] ?? '';

// 2. 没传歌名直接返回空结构
if (empty($name)) {
    echo json_encode([
        "code" => 400,
        "msg" => "请提供歌名",
        "title" => "",
        "singer" => "",
        "cover" => "",
        "link" => "",
        "music_url" => ""
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. 【核心更换】使用韩小韩 (vvhan) 搜歌接口
// 之前的 hb.ley.wang 因为没会员被抛弃了
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

// 4. 解析数据
$data = json_decode($response, true);

// 5. 【格式严格对齐】
// 韩小韩返回 success:true 代表成功
if ($data && isset($data['success']) && $data['success'] == true) {
    $info = $data['info'];
    
    // 提取字段
    $song_title = $info['name'] ?? "未知歌曲";
    $song_singer = $info['auther'] ?? $info['author'] ?? "未知歌手";
    $song_cover = $info['img'] ?? "";
    $song_url = $info['mp3url'] ?? $info['url'] ?? "";

    // 强转 HTTPS
    $final_url = str_replace('http://', 'https://', $song_url);
    $final_cover = str_replace('http://', 'https://', $song_cover);
    
    // 构造最终输出 (完全照搬你V27验证通过的格式)
    echo json_encode([
        "code"      => 200,
        "title"     => (string)$song_title,
        "singer"    => (string)$song_singer,
        "cover"     => (string)$final_cover,
        // 既然 V27 验证了 link 必须有，我们就填进去
        "link"      => (string)$final_url, 
        "music_url" => (string)$final_url,
        "lyric"     => "[00:00.00]此源暂无歌词"
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    // 失败处理
    echo json_encode([
        "code" => 404, 
        "msg" => "未找到歌曲",
        "title" => "无结果",
        "singer" => "",
        "cover" => "",
        "link" => "",
        "music_url" => ""
    ], JSON_UNESCAPED_UNICODE);
}
?>
