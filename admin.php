<?php
session_start();

// --- ADMIN SESSION VALIDATION ---
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: index.php");
    exit;
}

// FIX: Set PHP's default timezone specifically to Colombo, Sri Lanka (GMT+5:30)
date_default_timezone_set('Asia/Colombo');
include 'connect.php';

// --- TOGGLE LOGIC (QUIZ, COMMENTS & BATCH PERMISSIONS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_type'])) {
        $type = $_POST['toggle_type'];
        $value = isset($_POST['toggle_value']) && $_POST['toggle_value'] == '1' ? '1' : '0';
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->bind_param("sss", $type, $value, $value);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['updateBatchPermissions'])) {
        $batches = ['20/21', '21/22', '22/23', '23/24', '24/25'];
        $semesters_list = ['Y1S1', 'Y1S2', 'Y2S1', 'Y2S2', 'Y3S1', 'Y3S2'];
        
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO batch_permissions (batch_name, allowed_sections) 
                                    VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE allowed_sections = ?");
            foreach ($batches as $batch) {
                $batchKey = str_replace('/', '_', $batch);
                $allowedSemesters = [];

                foreach ($semesters_list as $sem) {
                    $postKey = 'perm_' . $batchKey . '_' . $sem;
                    if (isset($_POST[$postKey])) {
                        $allowedSemesters[] = $sem;
                    }
                }

                $allowedSectionsString = implode(',', $allowedSemesters);
                $stmt->bind_param("sss", $batch, $allowedSectionsString, $allowedSectionsString);
                $stmt->execute();
            }
            $conn->commit();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            die("Error updating permissions: " . $e->getMessage());
        }
    }
}

// --- PASSWORD RESET LOGIC (UPDATED WITH ERROR DEBUGGING) ---
$resetMessage = '';
if (isset($_POST['reset_password_submit'])) {
    $searchKey = trim($_POST['search_key']); 
    $newPassword = $_POST['new_password'];

    if (!empty($searchKey) && !empty($newPassword)) {
        // FIX: Shared hosting වල SQL අවුල් යන නිසා LOWER/TRIM සේරම අයින් කරලා සරල කෙලින්ම මැච් එකක් කලා
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR regNumber = ? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("ss", $searchKey, $searchKey);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $hashedPassword, $user['id']);
                
                if ($updateStmt->execute()) {
                    $resetMessage = "<span class='text-emerald-500 text-xs font-medium'>Password reset successfully!</span>";
                } else {
                    $resetMessage = "<span class='text-red-500 text-xs font-medium'>Update Error: " . $conn->error . "</span>";
                }
                $updateStmt->close();
            } else {
                if ($conn->error) {
                    $resetMessage = "<span class='text-red-500 text-xs font-medium'>SQL Error: " . $conn->error . "</span>";
                } else {
                    $resetMessage = "<span class='text-red-500 text-xs font-medium'>User not found for: " . htmlspecialchars($searchKey) . "</span>";
                }
            }
            $stmt->close();
        } else {
            $resetMessage = "<span class='text-red-500 text-xs font-medium'>Prepare Failed: " . $conn->error . "</span>";
        }
    } else {
        $resetMessage = "<span class='text-amber-500 text-xs font-medium'>Please fill all fields.</span>";
    }
}

// EXPORT LOGIC
if (isset($_GET['export_emails_txt'])) {
    $res = $conn->query("SELECT email FROM users");
    $emails = [];
    while($r = $res->fetch_assoc()) { $emails[] = $r['email']; }
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="user_emails.txt"');
    echo implode("\n", $emails);
    exit;
}

// DELETE USER LOGIC
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $id");
    header("Location: admin.php");
    exit;
}

// FETCH CURRENT SETTINGS
$settingsRes = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while($row = $settingsRes->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$quizEnabled = isset($settings['quiz_enabled']) && $settings['quiz_enabled'] == '1';
$commentsEnabled = isset($settings['comments_enabled']) && $settings['comments_enabled'] == '1';

// --- SEARCH & FILTER LOGIC ---
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$fBatch = isset($_GET['batch']) ? $conn->real_escape_string($_GET['batch']) : '';
$fDept  = isset($_GET['dept']) ? $conn->real_escape_string($_GET['dept']) : '';

$whereClauses = [];
if ($search !== '') {
    $whereClauses[] = "(firstName LIKE '%$search%' OR lastName LIKE '%$search%' OR email LIKE '%$search%' OR mobile LIKE '%$search%')";
}
if ($fBatch !== '') {
    $whereClauses[] = "batch = '$fBatch'";
}
if ($fDept !== '') {
    $whereClauses[] = "department = '$fDept'";
}

$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : "";

// PAGINATION LOGIC
$limit = 20; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_res = $conn->query("SELECT COUNT(*) as c FROM users $whereSql");
$total_users_count = $total_res->fetch_assoc()['c'];
$total_pages = ceil($total_users_count / $limit);

// DATABASE STATS
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$activeNow   = $conn->query("SELECT COUNT(*) as c FROM users WHERE last_seen >= (NOW() - INTERVAL 5 MINUTE)")->fetch_assoc()['c'];
$bmsCount   = $conn->query("SELECT COUNT(*) as c FROM users WHERE department='BMS'")->fetch_assoc()['c'];
$lcsCount   = $conn->query("SELECT COUNT(*) as c FROM users WHERE department='LCS'")->fetch_assoc()['c'];

// BATCH STATS
$batchStats = [];
$qBatch = $conn->query("SELECT batch, COUNT(*) as total, 
    SUM(CASE WHEN department = 'BMS' THEN 1 ELSE 0 END) as bms,
    SUM(CASE WHEN department = 'LCS' THEN 1 ELSE 0 END) as lcs 
    FROM users WHERE batch != '' GROUP BY batch ORDER BY batch DESC LIMIT 4");
while($row = $qBatch->fetch_assoc()) { $batchStats[] = $row; }

// FETCH USERS
$usersQuery = $conn->query("SELECT * FROM users $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");

// AJAX RESPONSE
if (isset($_GET['ajax'])) {
    ob_start();
    while($u = $usersQuery->fetch_assoc()): ?>
        <tr class="hover:bg-slate-800/40 table-row-item">
            <td class="px-6 py-4 flex items-center gap-3">
                <img src="<?= $u['profilePic'] ?>" class="w-10 h-10 rounded-full object-cover border border-slate-700 cursor-pointer hover:opacity-80 transition-opacity" onclick="openFullImage(this.src)">
                <div>
                    <div class="font-bold text-white"><?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?></div>
                    <div class="text-[10px] text-slate-500">#<?= $u['id'] ?></div>
                </div>
            </td>
            <td class="px-6 py-4"><div><?= $u['email'] ?></div><div class="text-[10px] text-slate-500"><?= $u['mobile'] ?></div></td>
            <td class="px-6 py-4"><div class="dept-label"><?= strtoupper($u['department']) ?></div><div class="text-[10px] text-slate-500">Batch <?= $u['batch'] ?></div></td>
            <td class="px-6 py-4">
                <div class="font-semibold text-slate-200"><?= htmlspecialchars($u['regNumber'] ?? 'N/A') ?></div>
            </td>
            <td class="px-6 py-4 text-right flex justify-end gap-2">
                <a href="<?= $u['profilePic'] ?>" download class="p-2 bg-slate-800 rounded-lg hover:text-indigo-400"><i data-lucide="download" class="w-4 h-4"></i></a>
                <button class="p-2 bg-slate-800 rounded-lg hover:text-red-500" onclick="confirmDelete(<?= $u['id'] ?>)"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </td>
        </tr>
    <?php endwhile;
    $rowsHtml = ob_get_clean();

    $queryStr = "&search=$search&batch=$fBatch&dept=$fDept";
    ob_start();
    ?>
    <div class="text-[11px] text-slate-500">Page <span class="text-white"><?= $page ?></span> of <span class="text-white"><?= $total_pages ?></span></div>
    <div class="flex gap-2">
        <a href="?page=<?= max(1, $page-1) . $queryStr ?>" class="px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-xs <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">Previous</a>
        <a href="?page=<?= min($total_pages, $page+1) . $queryStr ?>" class="px-4 py-2 bg-indigo-600 rounded-lg text-xs text-white <?= $page >= $total_pages ? 'opacity-50 pointer-events-none' : '' ?>">Next</a>
    </div>
    <?php
    $paginationHtml = ob_get_clean();
    echo json_encode(['rows' => $rowsHtml, 'pagination' => $paginationHtml]);
    exit;
}

// FETCH COMMENTS
$commentsQuery = $conn->query("SELECT c.*, u.firstName, u.lastName, u.profilePic, u.batch FROM comments c JOIN users u ON c.user_id = u.id ORDER BY c.id DESC");

// SEMESTER PERMISSIONS
$semesters = ['Y1S1', 'Y1S2', 'Y2S1', 'Y2S2', 'Y3S1', 'Y3S2'];
$allowedBatches = ['20/21', '21/22', '22/23', '23/24', '24/25'];
$batchPermissions = [];
$resP = $conn->query("SELECT * FROM batch_permissions");
if($resP){ while($r = $resP->fetch_assoc()){ $batchPermissions[$r['batch_name']] = explode(',', strtoupper($r['allowed_sections'])); } }

function time_elapsed_string($datetime) {
    if (!$datetime) return 'Never';
    $ago = new DateTime($datetime);
    $diff = (new DateTime)->diff($ago);
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}
?>

<!DOCTYPE html>
<html lang="en" id="htmlTag" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCBS DIGI KUPPIYA ADMIN PANNEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { darkBg: '#020617', sidebarBg: '#0f172a', cardBg: '#1e293b' } } } }</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #6366f1; }
        input:checked + .slider:before { transform: translateX(22px); }
    </style>
</head>
<body class="text-slate-200 dark:bg-darkBg transition-colors duration-300">

<div id="imageOverlay" class="fixed inset-0 z-[100] hidden bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 cursor-pointer" onclick="closeFullImage()">
    <button class="absolute top-6 right-6 text-white/50 hover:text-white"><i data-lucide="x" class="w-8 h-8"></i></button>
    <img id="fullImage" src="" class="max-w-full max-h-full rounded-lg shadow-2xl cursor-default" onclick="event.stopPropagation()">
</div>

<div class="flex h-screen overflow-hidden">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-[#0f172a] border-r border-slate-800 transform -translate-x-full md:translate-x-0 transition-transform duration-300 md:static md:h-screen">
        <div class="flex flex-col h-full p-6">
            <div class="flex items-center gap-3 mb-10">
                <div class="bg-indigo-600 p-2 rounded-xl"><i data-lucide="layout-grid" class="w-6 h-6 text-white"></i></div>
                <span class="font-bold text-2xl text-white tracking-tight">FCBS DIGI KUPPIYA Admin</span>
            </div>
            <div class="flex items-center gap-4 mb-10">
                <img src="kuppiimage.png" class="w-14 h-14 rounded-full border-2 border-indigo-500 p-0.5 object-cover cursor-pointer hover:scale-105 transition-transform" onclick="openFullImage(this.src)">
                <div><h4 class="text-white font-bold text-sm">Mr.Thanush Nethsika</h4><p class="text-slate-500 text-xs">Super Admin</p></div>
            </div>
            <div class="flex-1 space-y-8 overflow-y-auto custom-scrollbar pr-2">
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Controls</h3>
                    <div class="bg-[#1e293b] rounded-2xl p-5 space-y-6 border border-slate-800/50">
                        <form method="POST" class="flex justify-between items-center" id="quizToggleForm">
                            <span class="text-sm font-medium text-slate-300">Quiz Access</span>
                            <input type="hidden" name="toggle_type" value="quiz_enabled">
                            <label class="switch">
                                <input type="checkbox" name="toggle_value" value="1" <?= $quizEnabled ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                        </form>
                        <form method="POST" class="flex justify-between items-center" id="commentsToggleForm">
                            <span class="text-sm font-medium text-slate-300">Comments</span>
                            <input type="hidden" name="toggle_type" value="comments_enabled">
                            <label class="switch">
                                <input type="checkbox" name="toggle_value" value="1" <?= $commentsEnabled ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                        </form>
                        <div class="space-y-3">
                        <button onclick="window.location.href='https://fcbsdigikuppiya22.kesug.com/qadmin.php'" class="w-full flex items-center justify-center gap-3 py-3 border border-slate-700 rounded-xl text-sm text-slate-300 hover:bg-slate-800 transition-all">Quiz Admin Panel</button>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Exports</h3>
                    <div class="space-y-3">
                        <button onclick="location.href='?export_emails_txt=1'" class="w-full flex items-center justify-center gap-3 py-3 border border-slate-700 rounded-xl text-sm text-slate-300 hover:bg-slate-800 transition-all"><i data-lucide="mail" class="w-4 h-4"></i> Export Emails</button>
                        <button class="w-full flex items-center justify-center gap-3 py-3 border border-slate-700 rounded-xl text-sm text-slate-300 hover:bg-slate-800 transition-all"><i data-lucide="file-text" class="w-4 h-4"></i> Export CSV Data</button>
                    </div>
                </div>

                <div>
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4 mt-2">Account Security</h3>
                    <div class="bg-[#1e293b] rounded-2xl p-5 space-y-4 border border-slate-800/50">
                        <h4 class="text-sm font-medium text-slate-300">Reset Password</h4>
                        <form method="POST" class="space-y-3" autocomplete="off">
                            <div>
                                <input type="text" name="search_key" placeholder="Email or Registration No" required autocomplete="off" value="" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:ring-1 ring-indigo-500 placeholder-slate-500">
                            </div>
                            <div class="relative">
                                <input type="password" id="resetNewPassword" name="new_password" placeholder="Enter New Password" required autocomplete="new-password" value="" class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-3 pr-10 py-2 text-xs text-white outline-none focus:ring-1 ring-indigo-500 placeholder-slate-500">
                                <button type="button" onclick="toggleResetPasswordView()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 focus:outline-none">
                                    <i data-lucide="eye" id="togglePasswordIcon" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <button type="submit" name="reset_password_submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 rounded-xl transition-colors mt-2">
                                Reset Password
                            </button>
                        </form>
                        <?php if(!empty($resetMessage)): ?>
                            <div class="text-center mt-2 bg-slate-900/50 py-2 rounded-lg border border-slate-800">
                                <?= $resetMessage ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="pt-6 border-t border-slate-800 space-y-4">
                <button id="themeToggle" class="w-full flex items-center gap-3 px-4 py-3 bg-[#1e293b] rounded-xl text-sm text-slate-300 hover:bg-slate-800 transition-all"><i data-lucide="moon" id="themeIcon" class="w-4 h-4"></i><span id="themeText">Dark Mode</span></button>
                <button onclick="location.href='logout.php'" class="flex items-center gap-2 text-sm text-red-500 hover:text-red-400 font-medium px-2"><i data-lucide="log-out" class="w-4 h-4"></i> Sign Out</button>
            </div>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto custom-scrollbar dark:bg-darkBg">
        <div class="p-8 max-w-[1600px] mx-auto space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-[#4f46e5] to-[#7c3aed] p-6 rounded-[24px] flex items-center justify-between shadow-xl">
                    <div><p class="text-indigo-100 text-sm font-medium mb-1">Total Users</p><h3 class="text-5xl font-bold text-white"><?= $totalUsers ?></h3></div>
                    <div class="p-4 bg-white/20 rounded-2xl"><i data-lucide="users" class="w-8 h-8 text-white"></i></div>
                </div>
                <div class="bg-[#1e293b]/50 border border-slate-800 p-6 rounded-[24px] flex items-center justify-between">
                    <div><p class="text-slate-400 text-sm font-medium mb-1">Active Now</p><h3 class="text-4xl font-bold text-white"><?= $activeNow ?></h3></div>
                    <div class="p-4 bg-emerald-500/10 rounded-2xl"><i data-lucide="activity" class="w-8 h-8 text-emerald-500"></i></div>
                </div>
                <div class="bg-[#1e293b]/50 border border-slate-800 p-6 rounded-[24px] flex items-center justify-between">
                    <div><p class="text-slate-400 text-sm font-medium mb-1">BMS Dept</p><h3 class="text-4xl font-bold text-white"><?= $bmsCount ?></h3></div>
                    <div class="p-4 bg-blue-500/10 rounded-2xl"><i data-lucide="monitor" class="w-8 h-8 text-blue-500"></i></div>
                </div>
                <div class="bg-[#1e293b]/50 border border-slate-800 p-6 rounded-[24px] flex items-center justify-between">
                    <div><p class="text-slate-400 text-sm font-medium mb-1">LCS Dept</p><h3 class="text-4xl font-bold text-white"><?= $lcsCount ?></h3></div>
                    <div class="p-4 bg-amber-500/10 rounded-2xl"><i data-lucide="graduation-cap" class="w-8 h-8 text-amber-500"></i></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($batchStats as $stat): ?>
                <div class="bg-[#1e293b]/50 border border-slate-800 p-6 rounded-[24px] space-y-6">
                    <h4 class="text-xl font-bold text-white">Batch <?= $stat['batch'] ?></h4>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-xs mb-2"><span class="text-slate-400">BMS</span><span class="text-white font-bold"><?= $stat['bms'] ?></span></div>
                            <div class="w-full bg-slate-800 rounded-full h-1.5"><div class="bg-blue-500 h-1.5 rounded-full" style="width: <?= ($stat['total'] > 0) ? ($stat['bms']/$stat['total'])*100 : 0 ?>%"></div></div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-2"><span class="text-slate-400">LCS</span><span class="text-white font-bold"><?= $stat['lcs'] ?></span></div>
                            <div class="w-full bg-slate-800 rounded-full h-1.5"><div class="bg-amber-500 h-1.5 rounded-full" style="width: <?= ($stat['total'] > 0) ? ($stat['lcs']/$stat['total'])*100 : 0 ?>%"></div></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <div class="xl:col-span-2 bg-[#1e293b]/50 border border-slate-800 rounded-[24px] overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
                        <h3 class="font-bold text-white">User Management</h3>
                        <div class="flex items-center gap-2">
                            <input type="text" id="liveSearch" placeholder="Searching all pages..." class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-xs text-white outline-none w-48 focus:ring-1 ring-indigo-500">
                            <select id="batchFilter" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none cursor-pointer">
                                <option value="">All Batches</option>
                                <option value="20/21">Batch 20/21</option>
                                <option value="21/22">Batch 21/22</option>
                                <option value="22/23">Batch 22/23</option>
                                <option value="23/24">Batch 23/24</option>
                                <option value="24/25">Batch 24/25</option>
                            </select>
                            <select id="deptFilter" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none cursor-pointer">
                                <option value="">All Departments</option>
                                <option value="BMS">BMS Dept</option>
                                <option value="LCS">LCS Dept</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-400">
                            <thead class="bg-slate-900/50 uppercase font-bold text-[10px] text-slate-500">
                                <tr>
                                    <th class="px-6 py-5">Student</th>
                                    <th class="px-6 py-5">Contact</th>
                                    <th class="px-6 py-5">Academic</th>
                                    <th class="px-6 py-5">Registration No</th>
                                    <th class="px-6 py-5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody" class="divide-y divide-slate-800">
                                <?php while($u = $usersQuery->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-800/40 table-row-item">
                                    <td class="px-6 py-4 flex items-center gap-3">
                                        <img src="<?= $u['profilePic'] ?>" class="w-10 h-10 rounded-full object-cover border border-slate-700 cursor-pointer hover:opacity-80 transition-opacity" onclick="openFullImage(this.src)">
                                        <div>
                                            <div class="font-bold text-white"><?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?></div>
                                            <div class="text-[10px] text-slate-500">#<?= $u['id'] ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><div><?= $u['email'] ?></div><div class="text-[10px] text-slate-500"><?= $u['mobile'] ?></div></td>
                                    <td class="px-6 py-4"><div class="dept-label"><?= strtoupper($u['department']) ?></div><div class="text-[10px] text-slate-500">Batch <?= $u['batch'] ?></div></td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-200"><?= htmlspecialchars($u['regNumber'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <a href="<?= $u['profilePic'] ?>" download class="p-2 bg-slate-800 rounded-lg hover:text-indigo-400"><i data-lucide="download" class="w-4 h-4"></i></a>
                                        <button class="p-2 bg-slate-800 rounded-lg hover:text-red-500" onclick="confirmDelete(<?= $u['id'] ?>)"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="paginationContainer" class="p-4 border-t border-slate-800 flex items-center justify-between bg-slate-900/30">
                        <div class="text-[11px] text-slate-500">Page <span class="text-white"><?= $page ?></span> of <span class="text-white"><?= $total_pages ?></span></div>
                        <div class="flex gap-2">
                            <a href="?page=<?= max(1, $page-1) ?>" class="px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-xs <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">Previous</a>
                            <a href="?page=<?= min($total_pages, $page+1) ?>" class="px-4 py-2 bg-indigo-600 rounded-lg text-xs text-white <?= $page >= $total_pages ? 'opacity-50 pointer-events-none' : '' ?>">Next</a>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="bg-[#1e293b]/50 border border-slate-800 rounded-[24px] h-[400px] flex flex-col">
                        <div class="p-6 border-b border-slate-800 text-white font-bold">Comment Section</div>
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                            <?php while($c = $commentsQuery->fetch_assoc()): ?>
                            <div class="flex gap-3 p-4 bg-slate-800/40 rounded-2xl border border-slate-700/50">
                                <img src="<?= $c['profilePic'] ?>" class="w-10 h-10 rounded-full object-cover cursor-pointer" onclick="openFullImage(this.src)">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-xs font-bold text-white"><?= htmlspecialchars($c['firstName'] . ' ' . $c['lastName']) ?></span>
                                        <span class="text-[9px] text-indigo-400 font-medium"><?= time_elapsed_string($c['created_at']) ?></span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 italic">"<?= htmlspecialchars($c['comment']) ?>"</p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="bg-[#1e293b]/50 border border-slate-800 rounded-[24px] overflow-hidden">
                        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                            <h3 class="font-bold text-white">Semester Access Control</h3>
                        </div>
                        <form method="POST">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-[10px] text-slate-400">
                                    <thead class="bg-slate-900/50 uppercase font-bold text-slate-500">
                                        <tr>
                                            <th class="px-4 py-4">Batch</th>
                                            <?php foreach($semesters as $s) echo "<th class='px-2 py-4 text-center'>$s</th>"; ?>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        <?php foreach($allowedBatches as $batch): ?>
                                        <tr class="hover:bg-slate-800/30">
                                            <td class="px-4 py-4 font-bold text-white"><?= $batch ?></td>
                                            <?php foreach($semesters as $s): 
                                                $batchKey = str_replace('/', '_', $batch);
                                                $isChecked = isset($batchPermissions[$batch]) && in_array($s, $batchPermissions[$batch]);
                                            ?>
                                            <td class="px-2 py-4 text-center">
                                                <label class="switch" style="transform: scale(0.7);">
                                                    <input type="checkbox" name="perm_<?= $batchKey ?>_<?= $s ?>" value="1" <?= $isChecked ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 bg-slate-900/50 flex justify-center">
                                <button type="submit" name="updateBatchPermissions" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-6 rounded-lg transition-colors">
                                    Save Access Permissions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();

    // LIVE FILTERING AJAX LOGIC
    const liveSearch = document.getElementById('liveSearch');
    const batchFilter = document.getElementById('batchFilter');
    const deptFilter = document.getElementById('deptFilter');
    const tableBody = document.getElementById('userTableBody');
    const paginationContainer = document.getElementById('paginationContainer');

    function performSearch() {
        const query = liveSearch.value;
        const batch = batchFilter.value;
        const dept = deptFilter.value;

        fetch(`?ajax=1&search=${encodeURIComponent(query)}&batch=${encodeURIComponent(batch)}&dept=${encodeURIComponent(dept)}`)
            .then(res => res.json())
            .then(data => {
                tableBody.innerHTML = data.rows;
                paginationContainer.innerHTML = data.pagination;
                lucide.createIcons(); 
            })
            .catch(err => console.error("Filter error:", err));
    }

    liveSearch.addEventListener('input', performSearch);
    batchFilter.addEventListener('change', performSearch);
    deptFilter.addEventListener('change', performSearch);

    // FULL SCREEN IMAGE
    function openFullImage(src) {
        const overlay = document.getElementById('imageOverlay');
        const img = document.getElementById('fullImage');
        img.src = src;
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeFullImage() {
        const overlay = document.getElementById('imageOverlay');
        overlay.classList.add('hidden');
        document.body.style.overflow = ''; 
    }

    // Theme Switcher
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
        const isDark = document.getElementById('htmlTag').classList.toggle('dark');
        document.getElementById('themeIcon').setAttribute('data-lucide', isDark ? 'moon' : 'sun');
        document.getElementById('themeText').innerText = isDark ? 'Dark Mode' : 'Light Mode';
        lucide.createIcons();
    });

    function confirmDelete(id) {
        if(confirm("Delete user ID #" + id + "?")) { location.href = "admin.php?delete=" + id; }
    }

    // TOGGLE PASSWORD SHOW/HIDE LOGIC
    function toggleResetPasswordView() {
        const passwordInput = document.getElementById('resetNewPassword');
        const icon = document.getElementById('togglePasswordIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            passwordInput.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        lucide.createIcons(); 
    }
</script>
</body>
</html>