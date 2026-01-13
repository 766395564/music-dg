<?php
// 文件名：index.php
// 版本：V18.0 (完美透传版)
// 逻辑：直接克隆 hbmusic.1yo.cc 的返回结果，确保格式一模一样

// 1. 强力清洗 (防止任何隐形空格/报错/BOM头破坏JSON)
ob_start();

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 2. 获取歌名 (兼容各种 App 的传参习惯)
$word = $_GET['word'] ?? $_GET['keyword'] ?? $_GET['name'] ?? '';

// 3. 这里的逻辑是：如果没传歌名，就给个提示；传了就去请求新接口
if (empty($word)) {
    ob_clean(); // 清除杂质
    echo json_encode(["code" => 400, "msg" => "请提供歌名"]);
    exit;
}

// 4. 目标接口 (你新找到的那个好用的)
// 注意：它是 ?name=xxx，你的 App 可能是 ?word=xxx，这里自动对应上了
$targetUrl = "https://hbmusic.1yo.cc/?name=" . urlencode($word);

// 5. 模拟浏览器请求 (防止被拦截)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
$response = curl_exec($ch);
curl_close($ch);

// 6. 最终输出 (关键步骤)
// 我们不解析它，也不修改它，直接原样返回，但会清洗掉两头的空格
ob_clean(); // 再次清洗，确保 
