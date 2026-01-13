<?php
// 文件名：index.php
// 版本：V30.0 (Meting 酷狗源专版)
// 核心：使用 Meting 公共接口搜索酷狗音乐 (Kugou)
// 优势：酷狗源对海外IP友好，且拥有周杰伦版权，格式严格适配 APP

ob_start();
ob_clean();
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取参数
$name = $_GET['name'] ?? $_GET['word'] ?? $_GET['keyword'] ?? '';

// 2. 基础输出函数
function send_json($data) {
    ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 3. 空值检查
if (empty($name)) {
    send_json(["code" => 400, "msg" => "请提供歌名"]);
}

// ==================================================
// 核心逻辑：调用 Meting API (Meting 是开源界最稳的解析聚合)
// 我们选择 server=kugou (酷狗)，因为你之前的成功案例就是酷狗源
// ==================================================

// 第一步：搜索歌曲获取 ID
// 备用接口：如果 i-meto 挂了，可以换成 api.injahow.com/meting
$searchApi = "https://api.i-meto.com/meting/api?server=kugou&type=search&keyword=" . urlencode($name);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $searchApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/110.0.0.0 Safari/537.36');
$searchRes = curl_exec($ch);
curl_close($ch);

$searchData = json_decode($searchRes, true);

// 检查搜索结果
if ($searchData && is_array($searchData) && !empty($searchData)) {
    // 默认取第一个匹配结果
    $song = $searchData[0];
    
    // 提取基础信息
    $title  = $song['title']  ?? "未知歌名";
    $singer = $song['author'] ?? "未知歌手";
    $pic    = $song['pic']    ?? ""; // 酷狗搜索有时不直接返回封面，后面可能要补
    $id     = $song['songid'] ?? $song['id']; // 获取歌曲 ID

    // 第二步：通过 ID 获取详细播放链接
    $playApi = "https://api.i-meto.com/meting/api?server=kugou&type=url&id=" . $id;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $playApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $playRes = curl_exec($ch);
    curl_close($ch);
    
    $playData = json_decode($playRes, true);
    
    // 检查是否有播放链接
    if ($playData && !empty($playData['url'])) {
        
        // 强制 HTTPS
        $final_url = str_replace('http://', 'https://', $playData['url']);
        // 如果第一步没拿到封面，酷狗的 pic 往往在第二步里也没有，我们给个默认图或者用之前的
        $final_cover = !empty($pic) ? str_replace('http://', 'https://', $pic) : "https://y.qq.com/music/photo_new/T002R300x300M0000025NhlN2yWrP4.jpg";

        // === 最终格式封装 (严格适配文档) ===
        $output = [
            "code"      => 200,
            "title"     => (string)$title,
            "singer"    => (string)$singer,
            "cover"     => (string)$final_cover,
            // 补全 link 字段
            "link"      => (string)$final_url,
            "music_url" => (string)$final_url,
            "lyric"     => "[00:00.00]Meting酷狗源"
        ];
        
        send_json($output);
        
    } else {
        // 搜到了ID但拿不到链接 (可能是付费限制)
        send_json([
            "code" => 404, 
            "msg" => "歌曲存在但无法获取播放链接(可能需VIP)", 
            "title" => "无结果", 
            "link" => "", 
            "music_url" => ""
        ]);
    }

} else {
    // 连搜都搜不到
    send_json([
        "code" => 404, 
        "msg" => "Meting源未找到歌曲", 
        "title" => "无结果", 
        "link" => "", 
        "music_url" => ""
    ]);
}
?>
