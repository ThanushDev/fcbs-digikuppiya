<?php
/**
 * THORANA ADMIN PANEL
 * Visit: yoursite.com/admin.php
 * Change the password below before uploading!
 */
session_start();
date_default_timezone_set('Asia/Colombo');

// ── CHANGE THIS PASSWORD ──
define('ADMIN_PASS', 'Tharindi2003');

// ── DB CONFIG (same as api.php) ──
define('DB_HOST', 'sql112.infinityfree.com');
define('DB_NAME', 'if0_41943903_thorana_db');
define('DB_USER', 'if0_41943903');
define('DB_PASS', 'Thanush21122003');

function getDB() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    return $pdo;
}

// Handle login/logout/delete
if ($_POST['action'] ?? '' === 'login') {
    if ($_POST['pass'] === ADMIN_PASS) { $_SESSION['admin'] = true; }
    else { $loginError = 'වැරදි මුරපදය!'; }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (isset($_GET['delete']) && $_SESSION['admin']) {
    $db = getDB();
    $db->prepare('DELETE FROM comments WHERE id=?')->execute([(int)$_GET['delete']]);
    header('Location: admin.php?deleted=1'); exit;
}
if (isset($_GET['approve']) && $_SESSION['admin']) {
    $db = getDB();
    $db->prepare('UPDATE comments SET approved=1 WHERE id=?')->execute([(int)$_GET['approve']]);
    header('Location: admin.php'); exit;
}

$isAdmin = !empty($_SESSION['admin']);
$comments = [];
$total = 0;
if ($isAdmin) {
    $db = getDB();
    $total = $db->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    $comments = $db->query('SELECT * FROM comments ORDER BY id DESC LIMIT 200')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · Thorana</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Sinhala:wght@400;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#02020E;color:#f0e0c0;font-family:'Noto Serif Sinhala',serif;min-height:100vh;}
.topbar{background:rgba(255,215,0,0.08);border-bottom:1px solid rgba(255,215,0,0.2);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;}
.topbar h1{color:#FFD700;font-size:1.2rem;text-shadow:0 0 10px rgba(255,215,0,0.5);}
.topbar a{color:rgba(255,215,0,0.6);font-size:0.82rem;text-decoration:none;border:1px solid rgba(255,215,0,0.25);padding:5px 14px;border-radius:20px;}
.topbar a:hover{background:rgba(255,215,0,0.12);}
.wrap{max-width:1300px;margin:0 auto;padding:32px 20px;}

/* Login */
.loginBox{max-width:360px;margin:80px auto;background:rgba(255,215,0,0.05);border:1px solid rgba(255,215,0,0.2);border-radius:14px;padding:36px;}
.loginBox h2{color:#FFD700;text-align:center;margin-bottom:22px;font-size:1.2rem;}
.loginBox input{width:100%;background:rgba(255,255,255,0.05);border:1px solid rgba(255,215,0,0.25);border-radius:8px;padding:10px 14px;color:#f0e0c0;font-size:1rem;outline:none;margin-bottom:14px;}
.loginBox input:focus{border-color:#FFD700;}
.loginBox button{width:100%;background:linear-gradient(135deg,#B8860B,#FFA500);color:#07041A;font-weight:700;border:none;padding:12px;border-radius:8px;cursor:pointer;font-size:1rem;}
.err{color:#ff6b6b;font-size:0.85rem;text-align:center;margin-bottom:10px;}

/* Stats */
.stats{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap;}
.stat{background:rgba(255,215,0,0.06);border:1px solid rgba(255,215,0,0.18);border-radius:10px;padding:16px 24px;flex:1;min-width:140px;}
.stat .num{font-size:2rem;color:#FFD700;font-weight:700;}
.stat .lbl{font-size:0.78rem;color:rgba(255,215,0,0.55);margin-top:4px;}

/* Table */
.tblWrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:0.85rem;}
th{background:rgba(255,215,0,0.1);color:#FFD700;padding:10px 12px;text-align:left;white-space:nowrap;border-bottom:1px solid rgba(255,215,0,0.2);}
td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,0.05);vertical-align:top;}
tr:hover td{background:rgba(255,215,0,0.03);}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:0.72rem;}
.badge.ok{background:rgba(100,220,100,0.15);color:#90ee90;border:1px solid rgba(100,220,100,0.3);}
.badge.pend{background:rgba(255,180,0,0.12);color:#FFD700;border:1px solid rgba(255,180,0,0.3);}
.actBtn{text-decoration:none;font-size:0.75rem;padding:4px 10px;border-radius:6px;margin-right:5px;display:inline-block; margin-bottom:5px;}
.del{background:rgba(220,50,50,0.15);color:#ff8080;border:1px solid rgba(220,50,50,0.3);}
.del:hover{background:rgba(220,50,50,0.3);}
.appr{background:rgba(100,220,100,0.12);color:#90ee90;border:1px solid rgba(100,220,100,0.25);}
.appr:hover{background:rgba(100,220,100,0.25);}
.deleted-msg{background:rgba(100,220,100,0.1);border:1px solid rgba(100,220,100,0.3);color:#90ee90;padding:12px 18px;border-radius:8px;margin-bottom:18px;font-size:0.9rem;}
.empty{text-align:center;padding:40px;color:rgba(255,215,0,0.3);font-size:0.9rem;}
.commentText{max-width:250px;word-break:break-word;}
.subText {font-size: 0.72rem; color: rgba(255,215,0,0.55); margin-top:3px;}
.ellipsis {max-width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block;}
</style>
</head>
<body>

<?php if (!$isAdmin): ?>
<div class="wrap">
  <div class="loginBox">
    <h2>🔐 Admin Login</h2>
    <?php if (!empty($loginError)) echo '<div class="err">'.htmlspecialchars($loginError).'</div>'; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <input type="password" name="pass" placeholder="Admin Password" autofocus>
      <button type="submit">ඇතුළු වන්න</button>
    </form>
  </div>
</div>

<?php else: ?>
<div class="topbar">
  <h1>✦ Thorana Admin Panel</h1>
  <a href="admin.php?logout=1">⏏ Logout</a>
</div>
<div class="wrap">
  <?php if (isset($_GET['deleted'])): ?>
    <div class="deleted-msg">✓ Comment deleted successfully.</div>
  <?php endif; ?>

  <div class="stats">
    <div class="stat"><div class="num"><?= $total ?></div><div class="lbl">Total Comments</div></div>
    <div class="stat"><div class="num"><?= array_sum(array_column($comments,'approved')) ?></div><div class="lbl">Approved</div></div>
    <div class="stat"><div class="num"><?= $total - array_sum(array_column($comments,'approved')) ?></div><div class="lbl">Pending</div></div>
  </div>

  <?php if (empty($comments)): ?>
    <div class="empty">Comments නොමැත.</div>
  <?php else: ?>
  <div class="tblWrap">
    <table>
      <thead><tr>
        <th>Name</th>
        <th>Comment</th>
        <th>Location & ISP</th>
        <th>System Details</th>
        <th>Time Data</th>
        <th>Status</th>
        <th>Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($comments as $c): ?>
      <tr>
        <td style="color:#FFD700;font-weight:600;"><?= htmlspecialchars($c['name']) ?></td>
        <td class="commentText"><?= htmlspecialchars($c['comment']) ?></td>
        
        <td>
            <div style="color:#b8ebd0;"><?= htmlspecialchars($c['location'] ?? '-') ?></div>
            <div class="subText"><?= htmlspecialchars($c['isp'] ?? '-') ?></div>
            <div class="subText">IP: <?= htmlspecialchars($c['ip']??'') ?></div>
        </td>
        
        <td>
            <div style="color:#f0e0c0;"><?= htmlspecialchars($c['os'] ?? 'Unknown OS') ?></div>
            <div class="subText ellipsis" title="<?= htmlspecialchars($c['browser']??'') ?>">
                <?= htmlspecialchars($c['browser']??'-') ?>
            </div>
            <div class="subText ellipsis" title="<?= htmlspecialchars($c['referrer']??'') ?>">
                Ref: <?= htmlspecialchars($c['referrer']??'Direct') ?>
            </div>
        </td>

        <td>
            <div style="white-space:nowrap"><?= htmlspecialchars($c['date']) ?></div>
            <div class="subText">User Time: <?= htmlspecialchars($c['local_time'] ?? '-') ?></div>
            <div class="subText">Time on Site: <?= htmlspecialchars($c['time_on_page'] ?? '-') ?></div>
        </td>
        
        <td><?php if($c['approved']): ?>
          <span class="badge ok">Approved</span>
        <?php else: ?>
          <span class="badge pend">Pending</span>
        <?php endif; ?></td>
        
        <td style="white-space:nowrap">
          <?php if(!$c['approved']): ?>
          <a class="actBtn appr" href="admin.php?approve=<?= $c['id'] ?>">✓ Approve</a><br>
          <?php endif; ?>
          <a class="actBtn del" href="admin.php?delete=<?= $c['id'] ?>" onclick="return confirm('Delete this comment?')">✕ Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
</body>
</html>