<?php
// 文件名：index.php
// 版本：V6.0 (咪咕音乐专版 - 专治IP封禁和版权缺歌)

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. 获取参数
$word = $_GET['word'] ?? $_GET['keyword'] ?? '';
if (empty($word)) {
    echo json_encode(["code" => 400, "msg" => "请输入歌名"]);
    exit;
}

// 2. 请求咪咕音乐接口 (该接口对云服务器IP非常友好)
function migu_search($keyword) {
    // 咪咕官方搜索 API
    $url = "https://m.music.migu.cn/migu/remoting/scr_search_tag?rows=10&type=2&keyword=" . urlencode($keyword) . "&pgc=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    // 伪装成手机端访问
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1');
    // 必须添加 Referer
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: https://m.music.migu.cn/']);
    
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

// 3. 执行搜索
$migu_data = migu_search($word);

// 4. 解析结果
if ($migu_data && isset($migu_data['musics'][0])) {
    $song = $migu_data['musics'][0]; // 取第一首
    
    // 提取字段 (咪咕的字段结构)
    $title = $song['songName'] ?? "未知歌名";
    $singer = $song['singerName'] ?? "未知歌手";
    $cover = $song['cover'] ?? ""; // 咪咕封面
    $lyric_url = $song['lyrics'] ?? ""; // 歌词链接
    
    // 关键：获取播放链接 (优先高品质)
    $music_url = $song['mp3'] ?? ""; 

    // 再次确认：如果是 http 开头，强转 https (防止 App 报错)
    if (strpos($music_url, 'http://') === 0) {
        $music_url = str_replace('http://', 'https://', $music_url);
    }
    if (strpos($cover, 'http://') === 0) {
        $cover = str_replace('http://', 'https://', $cover);
    }

    $output = [
        "code"      => 200,
        "title"     => $title,
        "singer"    => $singer,
        "cover"     => $cover,
        "music_url" => $music_url,
        "lyric"     => $lyric_url
    ];
} else {
    // 失败处理
    $output = [
        "code" => 404, 
        "msg" => "未找到歌曲 (请确认歌名)",
        "debug_raw" => "咪咕接口返回空，可能是歌名太偏或暂时无结果"
    ];
}

// 5. 输出
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
