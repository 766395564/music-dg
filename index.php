<?php
// 文件名：index.php
// 版本：V31.0 (灰龙珠 HHL 强力突围版)
// 核心：使用“www.hhlqilongzhu.cn”的酷狗接口，这是目前公网抗封锁能力最强的源之一
// 目标：解决 Zeabur IP 被封、无会员、Meting 搜不到的问题

ob_start();
ob_clean();
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取参数
$name = $_GET['name'] ?? $_GET['word'] ?? $_GET['keyword'] ?? '';

// 2. 输出函数
function send_json($data) {
    ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 3. 空值拦截
if (empty($name)) {
    send_json(["code" => 400, "msg" => "请提供歌名"]);
}

// 4. 【核心资源】调用灰龙珠(HHL)酷狗接口
// type=json 确保返回 JSON 格式
// num=1 只要第一首
$targetUrl = "https://www.hhlqilongzhu.cn/api/dg_kugou.php?msg=" . urlencode($name) . "&n=1&type=json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
// 必须伪装 User-Agent，否则 HHL 可能拒绝
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 5. 解析数据
$data = json_decode($response, true);

// 6. 格式映射 (最关键一步)
// HHL 的返回字段通常是：music_url, cover, song_name, singer_name
// 我们要把它映射到你软件要求的：music_url, cover, title, singer, link
if ($data && !empty($data['music_url'])) {
    
    // 强制 HTTPS
    $final_url = str_replace('http://', 'https://', $data['music_url']);
    $final_cover = !empty($data['cover']) ? str_replace('http://', 'https://', $data['cover']) : "https://y.qq.com/music/photo_new/T002R300x300M0000025NhlN2yWrP4.jpg";
    
    // 智能提取歌名和歌手
    $title = $data['song_name'] ?? $data['title'] ?? $name; // 如果源没返回歌名，就用搜的词代替
    $singer = $data['singer_name'] ?? $data['singer'] ?? "未知歌手";

    // 构造最终输出 (严格符合文档)
    $output = [
        "code"      => 200,
        "title"     => (string)$title,
        "singer"    => (string)$singer,
        "cover"     => (string)$final_cover,
        // 文档要求的 link 字段，用播放链接填充
        "link"      => (string)$final_url,
        "music_url" => (string)$final_url,
        "lyric"     => "[00:00.00]灰龙珠酷狗源"
    ];

    send_json($output);

} else {
    // 失败处理
    // 把源返回的 msg 打印出来，方便调试
    $error_msg = $data['msg'] ?? "HHL源未返回有效数据";
    send_json([
        "code" => 404, 
        "msg" => "搜歌失败: " . $error_msg,
        "title" => "无结果",
        "link" => "",
        "music_url" => ""
    ]);
}
?>
