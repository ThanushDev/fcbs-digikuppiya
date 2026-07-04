<?php
/**
 * THORANA API - MySQL Comments Backend
 * api.php?action=get  → returns approved comments (JSON)
 * api.php POST action=add → inserts new comment
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Colombo');

// ── DB CONFIG — update these 4 lines ──
define('DB_HOST', 'sql112.infinityfree.com');
define('DB_NAME', 'if0_41943903_thorana_db');
define('DB_USER', 'if0_41943903');
define('DB_PASS', 'Thanush21122003');

function getDB() {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['status'=>'error','msg'=>'DB error: '.$e->getMessage()]));
    }
    return $pdo;
}

// Function to get OS from User Agent
function getOS($user_agent) {
    $os_platform = "Unknown OS";
    $os_array = array(
        '/windows nt 10/i'      =>  'Windows 10/11',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/macintosh|mac os x/i' =>  'Mac OS',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    );
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
            break;
        }
    }
    return $os_platform;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get') {
    $db = getDB();
    $rows = $db->query(
        'SELECT name, comment, DATE_FORMAT(date,"%Y-%m-%d %H:%i") as date
         FROM comments WHERE approved=1 ORDER BY id DESC LIMIT 50'
    )->fetchAll();
    echo json_encode($rows);

} elseif ($action === 'add') {
    $name    = trim($_POST['name']    ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    // JS Data
    $local_time = trim($_POST['localTime'] ?? 'Unknown');
    $time_on_page = trim($_POST['timeOnPage'] ?? 'Unknown');
    
    if (!$name || !$comment)
        die(json_encode(['status'=>'error','msg'=>'Missing fields']));
    if (mb_strlen($name)>100 || mb_strlen($comment)>1000)
        die(json_encode(['status'=>'error','msg'=>'Too long']));

    // PHP Data
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
    $os  = getOS($ua);
    $ref = substr($_SERVER['HTTP_REFERER'] ?? 'Direct', 0, 255);
    if(empty($ref)) $ref = 'Direct';
    $date = date('Y-m-d H:i:s');
    
    // API Call for Location & ISP
    $location = 'Unknown';
    $isp = 'Unknown';
    if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
        $geo = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,country,isp,status");
        if ($geo) {
            $geoData = json_decode($geo, true);
            if (isset($geoData['status']) && $geoData['status'] === 'success') {
                $location = ($geoData['city'] ?? '') . ', ' . ($geoData['country'] ?? '');
                $isp = $geoData['isp'] ?? 'Unknown';
            }
        }
    }

    $db = getDB();
    $db->prepare(
        'INSERT INTO comments (name,comment,ip,location,isp,os,browser,referrer,local_time,time_on_page,date,approved) 
         VALUES (?,?,?,?,?,?,?,?,?,?,?,1)'
    )->execute([$name, $comment, $ip, $location, $isp, $os, $ua, $ref, $local_time, $time_on_page, $date]);

    echo json_encode(['status'=>'ok','date'=>date('Y-m-d H:i'),'name'=>$name,'comment'=>$comment]);

} else {
    echo json_encode(['status'=>'error','msg'=>'Unknown action']);
}