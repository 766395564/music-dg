<?php
// 文件名：index.php
// 版本：V7.0 (酷我直连 + 公共代理双保险版 - 专治IP封禁)

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取参数
$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// ==========================================
// 核心逻辑：定义两个通道
// ==========================================

// 通道A：酷我音乐官方直连 (对服务器IP最友好，版权全)
function search_kuwo($keyword) {
    // 1. 搜索获取 RID
    $searchUrl = "http://www.kuwo.cn/api/www/search/searchMusicBykeyWord?key=" . urlencode($keyword) . "&pn=1&rn=1&httpsStatus=1";
    $headers = [
        'Referer: http://www.kuwo.cn/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Cookie: kw_token=HY520', // 必要的伪装
        'csrf: HY520'             // 对应的令牌
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $searchRes = curl_exec($ch);
    curl_close($ch);
    
    $searchData = json_decode($searchRes, true);
    
    // 如果搜到了，获取第一首的 rid
    if ($searchData && isset($searchData['data']['list'][0])) {
        $song = $searchData['data']['list'][0];
        $rid = $song['rid'];
        
        // 2. 用 RID 获取播放链接
        $playUrlApi = "http://www.kuwo.cn/api/v1/www/music/playUrl?mid={$rid}&type=music&httpsStatus=1";
        
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $playUrlApi);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
        $playRes = curl_exec($ch2);
        curl_close($ch2);
        
        $playData = json_decode($playRes, true);
        
        if ($playData && isset($playData['data']['url']) && !empty($playData['data']['url'])) {
            return [
                "success" => true,
                "data" => [
                    "title"     => $song['name'],
                    "singer"    => $song['artist'],
                    "cover"     => $song['pic'] ?? "",
                    "music_url" => $playData['data']['url'],
                    "lyric"     => "[00:00.00]本源暂无歌词"
                ]
            ];
        }
    }
    return ["success" => false];
}

// 通道B：公共代理 API (借用别人的干净IP)
function search_proxy($keyword) {
    // 使用某公益 API，它会在后端帮我们请求网易/QQ
    // 这里的 url 是一个示例，这类接口通常非常稳定
    $url = "https://api.vvhan.com/api/music?type=search&txt=" . urlencode($keyword);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $res = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($res, true);
    
    // 适配 vvhan 的返回格式
    if ($data && isset($data['success']) && $data['success'] == true) {
        return [
            "success" => true,
            "data" => [
                "title"     => $data['info']['name'],
                "singer"    => $data['info']['auther'], // 没错，它API里拼写是 auther
                "cover"     => $data['info']['img'],
                "music_url" => $data['info']['mp3url'],
                "lyric"     => "[00:00.00]代理源暂无歌词"
            ]
        ];
    }
    return ["success" => false];
}

// ==========================================
// 主执行流程
// ==========================================

// 1. 优先尝试酷我 (速度快，音质好)
$result = search_kuwo($word);

// 2. 如果酷我失败，自动降级到代理源 (保底)
if (!$result['success']) {
    $result = search_proxy($word);
}

// 3. 输出最终结果
if ($result['success']) {
    echo json_encode(array_merge(["code" => 200], $result['data']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([
        "code" => 404, 
        "msg" => "所有通道均未找到歌曲，建议检查歌名或稍后再试",
        "debug" => "IP_BAN_CONFIRMED"
    ], JSON_UNESCAPED_UNICODE);
}
?>
