<?php
// 文件名：index.php
// 版本：V19.0 (HTTP 302 跳转模式)
// 逻辑：不再由服务器代理抓取，而是直接指挥 App 跳转到可用接口
// 优势：完美绕过 Zeabur 服务器 IP 被封的问题，同时利用了手机端的干净网络

error_reporting(0);

// 1. 获取歌名 (兼容所有参数)
$word = $_GET['word'] ?? $_GET['keyword'] ?? $_GET['name'] ?? '';

// 2. 如果没传歌名，提示一下
if (empty($word)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// 3. 构建目标 URL (那个好用的接口)
// 注意：该接口使用 name 参数
$targetUrl = "https://hbmusic.1yo.cc/?name=" . urlencode($word);

// 4. === 核心动作：302 跳转 ===
// 告诉 App/浏览器："我这里不处理，请直接去访问 $targetUrl"
header("Location: $targetUrl");

// 5. 结束
exit;
?>
