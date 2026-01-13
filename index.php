<?php
// 文件名：index.php
// 版本：V7.0 (代理中转版 - 彻底绕过Zeabur IP被封问题)

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// ==========================================
// 通道1：公益代理 API (核心方案)
// 原理：让别人的服务器帮我们搜，绕过本机IP封锁
// ==========================================
function search_proxy($keyword) {
    // 使用韩小韩(vvhan)公益API，非常稳定
    $url = "https://api.vvhan.com/api/music?type=search&txt=" . urlencode($keyword);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $res = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($res, true);
    
    // 适配 vvhan 返回格式
    if ($data && isset($data['success']) && $data['success'] == true) {
        $info = $data['info'];
        return [
            "success" => true,
            "data" => [
                "title"     => $info['name'],
                "singer"    => $info['auther'], // API原文确是 auther
                "cover"     => $info['img'],
                "music_url" => $info['mp3url'],
                "lyric"     => "[00:00.00]本源暂无歌词"
            ]
        ];
    }
    return ["success" => false];
}

// ==========================================
// 通道2：酷我直连 (备用方案)
// ==========================================
function search_kuwo($keyword) {
    $searchUrl = "http://www.kuwo.cn/api/www/search/searchMusicBykeyWord?key=" . urlencode($keyword) . "&pn=1&rn=1&httpsStatus=1";
    $headers = [
        'Referer: http://www.kuwo.cn/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
        'Cookie: kw_token=HY520',
        'csrf: HY520'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($res, true);
    if ($data && isset($data['data']['list'][0])) {
        $song = $data['data']['list'][0];
        $rid = $song['rid'];
        
        // 获取播放链
        $playUrl = "http://www.kuwo.cn/api/v1/www/music/playUrl?mid={$rid}&type=music&httpsStatus=1";
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $playUrl);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        $playRes = curl_exec($ch2);
        curl_close($ch2);
        
        $playData = json_decode($playRes, true);
        if ($playData && !empty($playData['data']['url'])) {
            return [
                "success" => true,
                "data" => [
                    "title"     => $song['name'],
                    "singer"    => $song['artist'],
                    "cover"     => $song['pic'],
                    "music_url" => $playData['data']['url'],
                    "lyric"     => "[00:00.00]备用源暂无歌词"
                ]
            ];
        }
    }
    return ["success" => false];
}

// ==========================================
// 执行逻辑：先试代理，再试酷我
// ==========================================

// 1. 优先尝试代理 (最稳)
$result = search_proxy($word);

// 2. 如果代理没结果，尝试酷我 (保底)
if (!$result['success']) {
    $result = search_kuwo($word);
}

// 3. 输出
if ($result['success']) {
    echo json_encode(array_merge(["code" => 200], $result['data']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([
        "code" => 404, 
        "msg" => "所有线路均繁忙，请稍后再试",
        "debug" => "PROXY_AND_DIRECT_FAILED"
    ]);
}
?>
