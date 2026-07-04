<?php
session_start();
include '../connect.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$user = $_SESSION['user'];
$isVerified = isset($user['is_face_verified']) ? $user['is_face_verified'] : 0;

// Settings logic
$setting = $conn->query("SELECT setting_value FROM settings WHERE setting_key='comments_enabled'")->fetch_assoc();
$commentsEnabled = $setting ? $setting['setting_value'] == '1' : false;
$qset = $conn->query("SELECT setting_value FROM settings WHERE setting_key='quiz_enabled'")->fetch_assoc();
$quizEnabled = $qset ? $qset['setting_value'] == '1' : false;

// BATCH PERMISSIONS
$allowedSections = [];
if (isset($user['batch']) && !empty($user['batch'])) {
    $stmt = $conn->prepare("SELECT allowed_sections FROM batch_permissions WHERE batch_name = ?");
    $stmt->bind_param("s", $user['batch']);
    $stmt->execute();
    $result = $stmt->get_result();
    $batchRule = $result->fetch_assoc();
    $stmt->close();
    if ($batchRule) { $allowedSections = array_filter(array_map('trim', explode(',', strtoupper($batchRule['allowed_sections'])))); }
}

$sectionMap = ['Y1S1'=>'year1bms.php','Y1S2'=>'year1bms11.php','Y2S1'=>'year1bms2.php','Y2S2'=>'year2bms2.php','Y3S1'=>'year3s1.php','Y3S2'=>'year3s2.php','Y4S1'=>'year4s1.php','Y4S2'=>'year4s2.php'];
$profilePicPath = '../' . ($user['profilePic'] ?: 'default.png');
$firstName = htmlspecialchars($user['firstName']);

// --- MENTOR DATA ---
$TEAM_MEMBERS = [
    ['name' => 'Mr.Thanush Nethsika', 'role' => 'Founder & Author', 'uni_name' => 'සයිබර්', 'batch' => '22/23', 'subject' => 'Founder & Author', 'color' => 'bg-blue-500', 'image' => 'assets/img/mentors/cyber.jpg'],
    ['name' => 'Ms. Imalsha Sathsarani', 'role' => 'Economics', 'batch' => '22/23', 'subject' => 'Economics', 'color' => 'bg-purple-500', 'image' => 'assets/img/mentors/ima.jpeg'],
    ['name' => 'Ms. Kasuni Gaurika', 'role' => 'Mathematics', 'batch' => '22/23', 'subject' => 'Mathematics', 'color' => 'bg-pink-500', 'image' => 'assets/img/mentors/kasuni.jpeg'],
    ['name' => 'Ms. Kavindi Nawodhya', 'role' => 'Mathematics', 'batch' => '22/23', 'subject' => 'Mathematics', 'color' => 'bg-orange-500', 'image' => 'assets/img/mentors/nawodhya.jpeg'],
    ['name' => 'Ms. Jayathri Indrachapa', 'role' => 'Mathematics', 'uni_name' => 'මෙඩුසා', 'batch' => '22/23', 'subject' => 'Mathematics', 'color' => 'bg-orange-500', 'image' => 'assets/img/mentors/chapa.jpeg'],
    ['name' => 'Ms. Kavithma Damindi', 'role' => 'Management', 'batch' => '22/23', 'subject' => 'Management', 'color' => 'bg-indigo-500', 'image' => 'assets/img/mentors/kavithma.jpeg'],
    ['name' => 'Ms. Naduni Rathnayaka', 'role' => 'MIS', 'batch' => '22/23', 'subject' => 'MIS', 'color' => 'bg-teal-500', 'image' => 'assets/img/mentors/naduni.jpeg'],
    ['name' => 'Ms. Liyoni Kaushalya', 'role' => 'MIS', 'uni_name' => 'ආල්‍යා', 'batch' => '21/22', 'subject' => 'MIS', 'color' => 'bg-blue-500', 'image' => 'assets/img/mentors/liyoni.jpeg'],
    ['name' => 'Ms. Thakshila Wijesekara', 'role' => 'MIS', 'uni_name' => 'රපුන්සල්', 'batch' => '21/22', 'subject' => 'MIS', 'color' => 'bg-purple-500', 'image' => 'assets/img/mentors/rapunsall.jpeg'],
    ['name' => 'Ms. Dakshila Dilshani', 'role' => 'Accounting', 'batch' => '22/23', 'subject' => 'Accounting', 'color' => 'bg-pink-500', 'image' => 'assets/img/mentors/dakshi.jpeg'],
    ['name' => 'Ms. Shashini Herath', 'role' => 'Accounting', 'uni_name' => 'ශ්‍රිනී', 'batch' => '21/22', 'subject' => 'Accounting', 'color' => 'bg-pink-500', 'image' => 'assets/img/mentors/shashini.jpeg'],
    ['name' => 'Ms. Lihini Himasha', 'role' => 'Accounting', 'uni_name' => 'ලාරා', 'batch' => '21/22', 'subject' => 'Accounting', 'color' => 'bg-orange-500', 'image' => 'assets/img/mentors/lihini.jpeg'],
    ['name' => 'Ms. Diwangani Kavindya', 'role' => 'Accounting', 'uni_name' => 'විනී', 'batch' => '21/22', 'subject' => 'Accounting', 'color' => 'bg-teal-500', 'image' => 'assets/img/mentors/diwangani.jpeg']
];

$SEMESTERS = [
    ['code' => 'Y1S1', 'year' => 1, 'sem' => 1, 'title' => 'Year I Semester I', 'theme' => ['text' => 'text-orange-600', 'bar' => 'bg-orange-500', 'light' => 'bg-orange-100', 'shadow' => 'shadow-orange-500/20']],
    ['code' => 'Y1S2', 'year' => 1, 'sem' => 2, 'title' => 'Year I Semester II', 'theme' => ['text' => 'text-orange-600', 'bar' => 'bg-orange-500', 'light' => 'bg-orange-100', 'shadow' => 'shadow-orange-500/20']],
    ['code' => 'Y2S1', 'year' => 2, 'sem' => 1, 'title' => 'Year II Semester I', 'theme' => ['text' => 'text-emerald-600', 'bar' => 'bg-emerald-500', 'light' => 'bg-emerald-100', 'shadow' => 'shadow-emerald-500/20']],
    ['code' => 'Y2S2', 'year' => 2, 'sem' => 2, 'title' => 'Year II Semester II', 'theme' => ['text' => 'text-emerald-600', 'bar' => 'bg-emerald-500', 'light' => 'bg-emerald-100', 'shadow' => 'shadow-emerald-500/20']],
    ['code' => 'Y3S1', 'year' => 3, 'sem' => 1, 'title' => 'Year III Semester I', 'theme' => ['text' => 'text-pink-600', 'bar' => 'bg-pink-500', 'light' => 'bg-pink-100', 'shadow' => 'shadow-pink-500/20']],
    ['code' => 'Y3S2', 'year' => 3, 'sem' => 2, 'title' => 'Year III Semester II', 'theme' => ['text' => 'text-pink-600', 'bar' => 'bg-pink-500', 'light' => 'bg-pink-100', 'shadow' => 'shadow-pink-500/20']],
    ['code' => 'Y4S1', 'year' => 4, 'sem' => 1, 'title' => 'Year IV Semester I', 'theme' => ['text' => 'text-amber-600', 'bar' => 'bg-amber-500', 'light' => 'bg-amber-100', 'shadow' => 'shadow-amber-500/20']],
    ['code' => 'Y4S2', 'year' => 4, 'sem' => 2, 'title' => 'Year IV Semester II', 'theme' => ['text' => 'text-amber-600', 'bar' => 'bg-amber-500', 'light' => 'bg-amber-100', 'shadow' => 'shadow-amber-500/20']]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCBS DIGI Kuppiya - BMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { background: linear-gradient(to bottom right, #0f172a, #1e1b4b, #312e81); min-height: 100vh; }
        .modal { display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter: blur(10px); }
        .modal-content { background:#1e293b; margin:5vh auto; padding:30px; border:1px solid rgba(255,255,255,0.1); width:95%; max-width:500px; border-radius:24px; color:white; }
        .modal-blur { backdrop-filter: blur(15px); background: rgba(15, 23, 42, 0.7); }

        .custom-textarea {
            width: 100%; background: rgba(255,255,255,0.07); color: #fff; border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 10px; padding: 10px 12px; font-size: 14px; resize: none; min-height: 80px; 
        }
        .custom-textarea:focus { outline: none; border-color: #ff00aa; box-shadow: 0 0 10px #ff00aa55; }

        /* ===================== PREMIUM SIDEBAR STYLES ===================== */

        /* Overlay */
        #digi-overlay {
            position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 90;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }
        #digi-overlay.sb-active { opacity: 1; pointer-events: all; }

        /* Sidebar panel */
        #digi-sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: 300px;
            background: rgba(8, 11, 24, 0.98);
            border-right: 1px solid rgba(255,255,255,0.06);
            z-index: 100;
            transform: translateX(-100%);
            transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex; flex-direction: column;
            overflow: hidden;
            box-shadow: 6px 0 50px rgba(0,0,0,0.6);
        }
        #digi-sidebar.sb-open { transform: translateX(0); }

        /* Purple-to-pink glowing right edge */
        #digi-sidebar::after {
            content: '';
            position: absolute; top: 0; right: 0; width: 1px; height: 100%;
            background: linear-gradient(to bottom,
                transparent 0%,
                rgba(168,85,247,0.6) 30%,
                rgba(236,72,153,0.6) 70%,
                transparent 100%
            );
            pointer-events: none;
        }

        /* Sidebar Header */
        .sb-header {
            padding: 20px 18px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
        }
        .sb-logo-wrap { display: flex; align-items: center; gap: 10px; }
        .sb-logo-icon {
            width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            box-shadow: 0 0 18px rgba(168,85,247,0.55);
        }
        .sb-logo-title { font-size: 14px; font-weight: 700; color: #fff; line-height: 1; letter-spacing: 0.01em; }
        .sb-logo-sub { font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 3px; }
        .sb-close-btn {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.45); cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 14px;
            transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.25s;
        }
        .sb-close-btn:hover {
            background: rgba(239,68,68,0.18);
            border-color: rgba(239,68,68,0.4);
            color: #ef4444;
            transform: rotate(90deg);
        }

        /* Scroll body */
        .sb-body {
            flex: 1; overflow-y: auto; padding: 14px 12px 24px;
            display: flex; flex-direction: column; gap: 18px;
        }
        .sb-body::-webkit-scrollbar { width: 4px; }
        .sb-body::-webkit-scrollbar-track { background: transparent; }
        .sb-body::-webkit-scrollbar-thumb { background: rgba(168,85,247,0.3); border-radius: 2px; }

        /* Section label */
        .sb-section-label {
            font-size: 9px; font-weight: 700; letter-spacing: 0.12em;
            text-transform: uppercase; color: rgba(255,255,255,0.22);
            padding: 0 4px; margin-bottom: 5px;
        }

        /* APK Download Card */
        .sb-apk-btn {
            display: flex; align-items: center; gap: 12px;
            padding: 13px 14px; border-radius: 14px;
            background: linear-gradient(135deg, rgba(61,220,132,0.08) 0%, rgba(46,168,102,0.08) 100%);
            border: 1px solid rgba(61,220,132,0.25);
            text-decoration: none; color: #fff;
            transition: border-color 0.25s, box-shadow 0.25s, transform 0.2s, background 0.25s;
            position: relative; overflow: hidden;
        }
        .sb-apk-btn::before {
            content: ''; position: absolute; inset: 0; opacity: 0;
            background: linear-gradient(135deg, rgba(61,220,132,0.12), rgba(46,168,102,0.12));
            transition: opacity 0.25s;
        }
        .sb-apk-btn:hover { border-color: rgba(61,220,132,0.55); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(61,220,132,0.2); }
        .sb-apk-btn:hover::before { opacity: 1; }
        .sb-apk-icon {
            width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
            background: linear-gradient(135deg, #3ddc84, #2ea866);
            display: flex; align-items: center; justify-content: center; font-size: 22px;
            box-shadow: 0 4px 14px rgba(61,220,132,0.45);
            position: relative; z-index: 1;
        }
        .sb-apk-info { flex: 1; min-width: 0; position: relative; z-index: 1; }
        .sb-apk-title { font-size: 12.5px; font-weight: 700; color: #fff; }
        .sb-apk-sub { font-size: 10px; color: rgba(255,255,255,0.4); margin-top: 2px; }
        .sb-apk-arrow {
            width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
            background: linear-gradient(135deg, #3ddc84, #2ea866);
            display: flex; align-items: center; justify-content: center; font-size: 14px;
            position: relative; z-index: 1;
        }

        /* Tool rows */
        .sb-tools { display: flex; flex-direction: column; gap: 3px; }
        .sb-tool-row {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 12px; border-radius: 11px;
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.05);
            text-decoration: none; color: rgba(255,255,255,0.8);
            font-size: 12.5px; font-weight: 500;
            transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.2s;
            position: relative; overflow: hidden;
        }
        .sb-tool-row::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
            border-radius: 0 3px 3px 0; opacity: 0; transition: opacity 0.2s;
        }
        .sb-tool-row:hover {
            background: rgba(255,255,255,0.065);
            border-color: rgba(255,255,255,0.1);
            color: #fff; transform: translateX(4px);
        }
        .sb-tool-row:hover::before { opacity: 1; }

        .sb-tool-icon {
            width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
            transition: transform 0.2s;
        }
        .sb-tool-row:hover .sb-tool-icon { transform: scale(1.12); }

        .sb-tool-badge {
            font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
            text-transform: uppercase; letter-spacing: 0.06em; flex-shrink: 0;
        }

        /* Color variants */
        .si-purple { background: rgba(168,85,247,0.14); }
        .sl-purple::before { background: #a855f7; }
        .si-blue   { background: rgba(59,130,246,0.14); }
        .sl-blue::before   { background: #3b82f6; }
        .si-emerald{ background: rgba(16,185,129,0.14); }
        .sl-emerald::before{ background: #10b981; }
        .si-orange { background: rgba(249,115,22,0.14); }
        .sl-orange::before { background: #f97316; }
        .si-pink   { background: rgba(236,72,153,0.14); }
        .sl-pink::before   { background: #ec4899; }
        .si-cyan   { background: rgba(6,182,212,0.14); }
        .sl-cyan::before   { background: #06b6d4; }
        .si-violet { background: rgba(139,92,246,0.14); }
        .sl-violet::before { background: #8b5cf6; }
        .si-yellow { background: rgba(234,179,8,0.14); }
        .sl-yellow::before { background: #eab308; }
        .si-red    { background: rgba(239,68,68,0.14); }
        .sl-red::before    { background: #ef4444; }

        /* Comment box */
        .sb-comment-box {
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 13px; padding: 14px;
        }
        .sb-comment-head {
            display: flex; align-items: center; gap: 7px;
            font-size: 11.5px; font-weight: 600; color: rgba(255,255,255,0.6);
            margin-bottom: 10px;
        }
        .sb-comment-textarea {
            width: 100%; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 9px; padding: 9px 11px;
            font-size: 12px; color: #fff; resize: none; min-height: 70px;
            font-family: inherit; line-height: 1.5; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .sb-comment-textarea::placeholder { color: rgba(255,255,255,0.22); }
        .sb-comment-textarea:focus { border-color: rgba(236,72,153,0.5); box-shadow: 0 0 0 3px rgba(236,72,153,0.1); }
        .sb-comment-submit {
            width: 100%; margin-top: 9px; padding: 9px;
            background: linear-gradient(135deg, rgba(168,85,247,0.18), rgba(236,72,153,0.18));
            border: 1px solid rgba(168,85,247,0.28); border-radius: 9px;
            color: rgba(255,255,255,0.75); font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: inherit;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
        }
        .sb-comment-submit:hover {
            background: linear-gradient(135deg, rgba(168,85,247,0.32), rgba(236,72,153,0.32));
            border-color: rgba(168,85,247,0.5); color: #fff;
        }

        /* ===================== END SIDEBAR STYLES ===================== */
    </style>
</head>
<body class="text-white" x-data="{ modalOpen: false, mentorModal: false, selectedMentor: {}, imagePreview: false }">

<?php if (isset($_GET['error'])): ?>
    <div id="toastError" class="fixed top-5 right-5 bg-red-600 text-white px-6 py-3 rounded-xl shadow-2xl z-[10000] border border-red-400 flex items-center gap-3 animate-bounce">
        <i data-lucide="alert-circle" class="w-5 h-5"></i><span><?php echo htmlspecialchars($_GET['error']); ?></span>
    </div>
    <script>setTimeout(() => document.getElementById('toastError').remove(), 5000);</script>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div id="toastSuccess" class="fixed top-5 right-5 bg-green-600 text-white px-6 py-3 rounded-xl shadow-2xl z-[10000] border border-green-400 flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5"></i><span>Action completed successfully!</span>
    </div>
    <script>setTimeout(() => document.getElementById('toastSuccess').remove(), 4000);</script>
<?php endif; ?>

<?php if(empty($user['batch']) || empty($user['regNumber'])): ?>
<div class="fixed inset-0 z-[9000] bg-black/95 backdrop-blur-xl flex justify-center items-center p-4">
    <div class="bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.08)] p-8 rounded-2xl max-w-md w-full shadow-[0_0_20px_rgba(255,0,128,0.3)] text-center">
        <h3 class="text-2xl font-bold text-pink-500 mb-2">Action Required!</h3>
        <p class="text-gray-400 text-sm mb-6">Please complete your missing profile details to continue.</p>
        <form method="post" action="../account.php" class="space-y-4">
            <?php if(empty($user['batch'])): ?>
            <select name="batchSelect" required class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none text-white focus:border-pink-500">
                <option value="">-- Select Your Batch --</option>
                <option value="20/21">20/21 Batch</option>
                <option value="21/22">21/22 Batch</option>
                <option value="22/23">22/23 Batch</option>
                <option value="23/24">23/24 Batch</option>
                <option value="24/25">24/25 Batch</option>
            </select>
            <?php endif; ?>
            <?php if(empty($user['regNumber'])): ?>
            <input type="text" name="regNumber" placeholder="Reg No (e.g. 19/ms/00)" required pattern="^[0-9]{2}/(ms|cs)/[0-9]+$" title="Format: YY/ms/number or YY/cs/number" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none text-white focus:border-pink-500">
            <?php endif; ?>
            <button type="submit" name="completeProfileData" class="w-full bg-gradient-to-r from-pink-500 to-purple-600 font-bold py-3 rounded-xl shadow-lg hover:scale-105 transition-transform">Save Details</button>
        </form>
    </div>
</div>

<?php elseif($isVerified == 0): ?>
<div class="fixed inset-0 z-[9000] bg-black/95 backdrop-blur-xl flex justify-center items-center p-4">
    <div class="bg-[rgba(255,0,0,0.05)] border border-red-500/30 p-8 rounded-2xl max-w-md w-full shadow-[0_0_25px_rgba(255,0,0,0.4)] text-center">
        <h3 class="text-2xl font-bold text-red-500 mb-2">Verification Required!</h3>
        <p class="text-gray-400 text-sm mb-6">Please upload a clear photo of your face to Continue your account.<br><span class="text-xs text-gray-500">(ඔබගේ ගිණුම දිගටම කරගෙන යාමට කරුණාකර ඔබගේ මුහුණේ පැහැදිලි ඡායාරූපයක් උඩුගත කරන්න. සතුන්, කාටූන්, මල් හෝ දර්ශන අවසර නැත.)</span></p>
        <form method="post" action="../account.php" enctype="multipart/form-data" class="space-y-4">
            <input type="file" name="newProfilePic" id="verifyFile" accept="image/*" required class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 bg-slate-800 rounded-xl p-2 border border-white/10">
            <p id="verifyAiStatus" class="text-xs font-bold text-orange-400">Loading AI Face Scanner...</p>
            <button type="submit" name="verify_photo" id="verifyBtn" disabled class="w-full bg-red-600 font-bold py-3 rounded-xl opacity-50 cursor-not-allowed shadow-[0_0_15px_rgba(255,0,0,0.5)] transition-all">Verify & Unlock</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===================== SIDEBAR OVERLAY ===================== -->
<div id="digi-overlay" onclick="digiSidebarClose()"></div>

<!-- ===================== PREMIUM SIDEBAR ===================== -->
<aside id="digi-sidebar">

    <!-- Header -->
    <div class="sb-header">
        <div class="sb-logo-wrap">
            <div class="sb-logo-icon">🎓</div>
            <div>
                <div class="sb-logo-title">Digi Tools</div>
                <div class="sb-logo-sub">FCBS DIGI KUPPIYA</div>
            </div>
        </div>
        <button class="sb-close-btn" onclick="digiSidebarClose()" aria-label="Close sidebar">✕</button>
    </div>

    <!-- Scrollable Body -->
    <div class="sb-body">

        <!-- APK Download -->
        <div>
            <div class="sb-section-label">App</div>
            <a href="https://www.mediafire.com/file/9vqeagotmw7wyyg/FCBS_Digi_Kuppiya.apk/file" target="_blank" class="sb-apk-btn">
                <div class="sb-apk-icon">🤖</div>
                <div class="sb-apk-info">
                    <div class="sb-apk-title">Download Android App</div>
                    <div class="sb-apk-sub">APK · FCBS Digi Kuppiya</div>
                </div>
                <div class="sb-apk-arrow">↓</div>
            </a>
        </div>

        <!-- Academic Tools -->
        <div>
            <div class="sb-section-label">Academic Tools</div>
            <div class="sb-tools">

                <?php if ($quizEnabled): ?>
                <a href="../qindex.php" class="sb-tool-row sl-purple">
                    <div class="sb-tool-icon si-purple">📝</div>
                    <span style="flex:1;">Quiz System</span>
                </a>
                <?php endif; ?>

                <a href="../atdbms/index.html" class="sb-tool-row sl-blue">
                    <div class="sb-tool-icon si-blue">📊</div>
                    <span>Attendance Calculator</span>
                </a>

                <a href="../gpa/index.php" class="sb-tool-row sl-emerald">
                    <div class="sb-tool-icon si-emerald">🎯</div>
                    <span>GPA Calculator</span>
                </a>

                <a href="../ca/index.html" class="sb-tool-row sl-orange">
                    <div class="sb-tool-icon si-orange">📐</div>
                    <span>CA Marks Calculator</span>
                </a>

                <a href="../finance/index.php" class="sb-tool-row sl-pink">
                    <div class="sb-tool-icon si-pink">💰</div>
                    <span>Finance Tracker</span>
                </a>
                
                <a href="https://thanushdev.github.io/Qr-Genarater/" class="sb-tool-row sl-cyan">
                    <div class="sb-tool-icon si-cyan">🔳</div>
                    <span>QR Code Generator</span>
                </a>

                <a href="https://thanushdev.github.io/AI-Humanizer/" class="sb-tool-row sl-violet">
                    <div class="sb-tool-icon si-violet">🤖</div>
                    <span>AI Humanizer</span>
                </a>

                <a href="https://thanushdev.github.io/DigiSolutionsCV/" class="sb-tool-row sl-yellow">
                    <div class="sb-tool-icon si-yellow">📄</div>
                    <span>CV Maker</span>
                </a>

                <a href="https://thanushdev.github.io/Pdftool/" class="sb-tool-row sl-red">
                    <div class="sb-tool-icon si-red">📁</div>
                    <span>PDF Generator</span>
                </a>

            </div>
        </div>

        

        <!-- Comment Box -->
        <?php if ($commentsEnabled): ?>
        <div>
            <div class="sb-section-label">Feedback</div>
            <div class="sb-comment-box">
                <div class="sb-comment-head">
                    <span>💬</span> Drop a Comment
                </div>
                <form method="post" action="../account.php">
                    <textarea name="comment" placeholder="Write your comment about the latest Kuppi video..." required class="sb-comment-textarea"></textarea>
                    <button type="submit" class="sb-comment-submit">Post Comment</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</aside>
<!-- ===================== END SIDEBAR ===================== -->

<header class="sticky top-0 z-50 w-full px-4 sm:px-6 py-4 flex items-center justify-between backdrop-blur-md bg-white/10 border-b border-white/20">
    <div class="flex items-center gap-2 sm:gap-4">
        <!-- Sidebar toggle button -->
        <button onclick="digiSidebarOpen()" class="p-2 bg-white/10 hover:bg-white/20 border border-white/10 rounded-full text-yellow-300 transition-all shadow-[0_0_10px_rgba(255,255,0,0.2)] hover:scale-110">
            <i data-lucide="graduation-cap" class="w-6 h-6"></i>
        </button>
        <img src="assets/img/logo.png" alt="Logo" class="h-12 sm:h-16 w-auto object-contain">
        <span class="text-xl sm:text-2xl font-bold text-white tracking-tight hidden md:block">FCBS <span class="text-yellow-300">DIGI Kuppiya</span></span>
    </div>
    <div class="flex items-center gap-4" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-3 bg-white/10 hover:bg-white/20 rounded-full pl-2 pr-4 py-1.5 transition-all border border-white/30">
            <img src="<?php echo $profilePicPath; ?>" class="w-8 h-8 rounded-full object-cover border border-white/50">
            <span class="text-white font-medium hidden sm:block"><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></span>
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" @click.away="open = false" x-transition class="absolute right-6 mt-48 w-64 bg-slate-900 rounded-2xl shadow-2xl py-2 border border-white/10">
            <button @click="modalOpen = true; open = false" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-white/10 flex items-center gap-2">
                <i data-lucide="user-cog" class="w-4 h-4"></i> Edit Account
            </button>
            <a href="../logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 border-t border-white/10 flex items-center gap-2">
                <i data-lucide="log-out" class="w-4 h-4"></i> Sign out
            </a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 pt-12 pb-20">
    <div class="mb-16">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white mb-6">
            <i data-lucide="sparkles" class="text-yellow-300 w-4 h-4"></i>
            <span class="font-medium text-sm uppercase tracking-wide">Welcome back, <?php echo $firstName; ?>!</span>
        </div>
        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 tracking-tight">
            Your Academic <br />
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-pink-300">Journey Awaits</span>
        </h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
        <?php foreach ($SEMESTERS as $sem): 
            $isAllowed = in_array($sem['code'], $allowedSections);
            $link = $isAllowed ? ($sectionMap[$sem['code']] ?? '#') : '#';
            $theme = $sem['theme'];
        ?>
        <a href="<?php echo $link; ?>" class="<?php echo $isAllowed ? 'opacity-100 hover:scale-105 shadow-2xl' : 'opacity-50 grayscale cursor-not-allowed'; ?> relative bg-white rounded-3xl p-6 shadow-xl transition-all duration-300 flex flex-col h-44 overflow-hidden group">
            <div class="absolute left-0 top-0 bottom-0 w-2 <?php echo $theme['bar']; ?>"></div>
            <div class="pl-3">
                <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $theme['light'].' '.$theme['text']; ?> mb-4">
                    Year <?php echo $sem['year']; ?> • Sem <?php echo $sem['sem']; ?>
                </span>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $sem['title']; ?></h3>
                <p class="text-sm mt-2 <?php echo $isAllowed ? 'text-gray-500' : 'text-red-500 font-bold'; ?>">
                    <?php echo $isAllowed ? 'Access Materials →' : 'Locked'; ?>
                </p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <section class="mb-20" x-data="{ 
        timer: null,
        scrollNext() { 
            let track = this.$refs.mentorTrack;
            if (track.scrollLeft + track.offsetWidth >= track.scrollWidth - 10) { track.scrollTo({ left: 0, behavior: 'smooth' }); } 
            else { track.scrollBy({ left: 320, behavior: 'smooth' }); }
        },
        scrollPrev() { 
            let track = this.$refs.mentorTrack;
            if (track.scrollLeft <= 0) { track.scrollTo({ left: track.scrollWidth, behavior: 'smooth' }); } 
            else { track.scrollBy({ left: -320, behavior: 'smooth' }); }
        },
        startAuto() { this.timer = setInterval(() => this.scrollNext(), 4000); },
        stopAuto() { clearInterval(this.timer); }
    }" x-init="startAuto()" @mouseenter="stopAuto()" @mouseleave="startAuto()">
        
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold flex items-center gap-2"><i data-lucide="users"></i> Meet Your Mentors</h2>
            <div class="flex gap-2">
                <button @click="scrollPrev()" class="p-2 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 transition-all"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                <button @click="scrollNext()" class="p-2 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 transition-all"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
            </div>
        </div>

        <div class="relative">
            <div x-ref="mentorTrack" class="flex overflow-x-auto gap-6 pb-6 no-scrollbar snap-x scroll-smooth">
                <?php foreach ($TEAM_MEMBERS as $m): ?>
                <div @click="selectedMentor = <?php echo htmlspecialchars(json_encode($m)); ?>; mentorModal = true"
                    class="flex-shrink-0 w-80 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 transition-all duration-500 hover:bg-white/20 hover:-translate-y-2 cursor-pointer snap-start">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full overflow-hidden shrink-0 border-2 border-white/20 shadow-lg">
                            <?php if (!empty($m['image'])): ?>
                                <img src="<?php echo $m['image']; ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full <?php echo $m['color']; ?> flex items-center justify-center font-bold text-xl text-white">
                                    <?php echo substr($m['name'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-white whitespace-nowrap truncate text-lg"><?php echo $m['name']; ?></h4>
                            <p class="text-xs text-white/60 whitespace-nowrap truncate"><?php echo $m['role']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<div x-show="imagePreview" class="fixed inset-0 z-[3000] flex items-center justify-center bg-black/95 backdrop-blur-xl p-4" @click="imagePreview = false" style="display: none;">
    <button class="absolute top-6 right-6 text-white/50 hover:text-white transition-colors p-2 bg-white/10 rounded-full"><i data-lucide="x" class="w-8 h-8"></i></button>
    <img :src="selectedMentor.image" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl object-contain border border-white/10">
</div>

<div x-show="mentorModal" class="fixed inset-0 z-[2000] flex items-center justify-center p-6 modal-blur" @click.self="mentorModal = false" style="display: none;">
    <div class="bg-slate-900 border border-white/10 w-full max-w-lg rounded-[32px] overflow-hidden shadow-2xl relative">
        <button @click="mentorModal = false" class="absolute top-6 right-6 p-2 bg-white/10 hover:bg-red-500 rounded-full transition-colors z-10"><i data-lucide="x" class="w-5 h-5"></i></button>
        <div class="p-8">
            <div class="flex flex-col items-center text-center mb-8">
                <div class="relative w-44 h-44 rounded-3xl overflow-hidden mb-6 ring-4 ring-yellow-300/30 shadow-2xl cursor-zoom-in" @click="if(selectedMentor.image) imagePreview = true">
                    <template x-if="selectedMentor.image"><img :src="selectedMentor.image" class="w-full h-full object-cover"></template>
                    <template x-if="!selectedMentor.image"><div :class="selectedMentor.color" class="w-full h-full flex items-center justify-center text-5xl font-bold text-white uppercase" x-text="selectedMentor.name ? selectedMentor.name.charAt(0) : ''"></div></template>
                </div>
                <h3 class="text-3xl font-bold text-white mb-2"><span x-text="selectedMentor.name"></span></h3>
                <span class="px-4 py-1 bg-yellow-300/10 text-yellow-300 border border-yellow-300/20 rounded-full text-sm font-semibold uppercase tracking-wider" x-text="selectedMentor.role"></span>
            </div>
            <div class="space-y-4 bg-white/5 p-6 rounded-2xl border border-white/5">
                <div class="flex justify-between items-center border-b border-white/5 pb-3">
                    <span class="text-white/50 text-sm">Main Subject</span>
                    <span class="font-medium text-white" x-text="selectedMentor.subject"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-white/50 text-sm">Academic Batch</span>
                    <span class="font-medium text-white" x-text="selectedMentor.batch"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" :style="modalOpen ? 'display:block' : 'display:none'">
    <div class="modal-content overflow-y-auto max-h-[90vh] no-scrollbar">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-yellow-300">Update Profile</h2>
            <button @click="modalOpen = false" class="text-gray-400 hover:text-white"><i data-lucide="x"></i></button>
        </div>
        
        <form method="post" action="../account.php" enctype="multipart/form-data" class="space-y-4">
            <div class="flex gap-4">
                <input type="text" name="fName" value="<?php echo htmlspecialchars($user['firstName']); ?>" required placeholder="First Name" class="w-1/2 bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-pink-500">
                <input type="text" name="lName" value="<?php echo htmlspecialchars($user['lastName']); ?>" required placeholder="Last Name" class="w-1/2 bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-pink-500">
            </div>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="Email" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-pink-500">
            <input type="tel" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required pattern="\d{10}" maxlength="10" placeholder="Mobile" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-pink-500">
            <input type="text" name="regNumber" value="<?php echo htmlspecialchars($user['regNumber']); ?>" required pattern="^[0-9]{2}/(ms|cs)/[0-9]+$" title="Format: YY/ms/number or YY/cs/number" placeholder="Registration Number" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-pink-500">
            
            <div class="border-t border-white/10 pt-4 mt-4">
                <p class="text-xs text-gray-400 mb-2">Change Password (Leave blank if unchanged)</p>
                <input type="password" name="currentPassword" placeholder="Current Password" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-pink-500 mb-3">
                <input type="password" name="newPassword" placeholder="New Password" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-pink-500">
            </div>

            <div class="border-t border-white/10 pt-4 mt-4">
                <p class="text-xs text-gray-400 mb-2">Change Profile Picture</p>
                <input type="file" name="profilePic" id="updateFile" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100 bg-slate-800 rounded-xl p-2 border border-white/10">
                <p id="updateAiStatus" class="text-[10px] font-bold mt-1"></p>
            </div>

            <button type="submit" name="updateProfile" id="updateBtn" class="w-full mt-4 bg-gradient-to-r from-pink-500 to-purple-600 font-bold py-3 rounded-xl shadow-[0_0_15px_rgba(255,0,128,0.4)] hover:scale-[1.02] transition-transform">Save Changes</button>
        </form>
    </div>
</div>

<footer class="mt-20 border-t border-white/10 bg-black/20 backdrop-blur-lg">
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="mt-12 pt-8 border-t border-white/10 flex justify-between items-center text-white/50 text-sm">
            <p>© 2026 FCBS DIGI Kuppiya.<br> All rights reserved.</p>
            <div class="flex items-center gap-2">Made with <i data-lucide="heart" class="text-red-500 w-4 h-4 fill-current animate-pulse"></i> for students</div>
        </div>
    </div>
</footer>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>

<!-- Sidebar JS -->
<script>
    function digiSidebarOpen() {
        document.getElementById('digi-sidebar').classList.add('sb-open');
        document.getElementById('digi-overlay').classList.add('sb-active');
        document.body.style.overflow = 'hidden';
    }
    function digiSidebarClose() {
        document.getElementById('digi-sidebar').classList.remove('sb-open');
        document.getElementById('digi-overlay').classList.remove('sb-active');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') digiSidebarClose();
    });
</script>

<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
function loadFaceApi() {
    return new Promise((resolve, reject) => {
        if (typeof faceapi !== 'undefined') { resolve(); } else {
            const interval = setInterval(() => { if (typeof faceapi !== 'undefined') { clearInterval(interval); resolve(); } }, 500);
        }
    });
}

loadFaceApi().then(async () => {
    await faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/');
    
    const verifyAiStatus = document.getElementById('verifyAiStatus');
    const updateAiStatus = document.getElementById('updateAiStatus');
    const verifyFile = document.getElementById('verifyFile');

    if(verifyAiStatus) { verifyAiStatus.innerText = '✅ AI Scanner Ready. Please upload your photo.'; verifyAiStatus.style.color = '#00ff88'; if(verifyFile) verifyFile.disabled = false; }
    if(updateAiStatus) { updateAiStatus.innerText = '✅ AI Active. New photos will be scanned.'; updateAiStatus.style.color = '#00ff88'; }
}).catch(err => console.error("AI Load Error:", err));

async function handleFaceDetection(fileInputId, btnId, statusId) {
    const fileInput = document.getElementById(fileInputId);
    const btn = document.getElementById(btnId);
    const statusText = document.getElementById(statusId);

    if(!fileInput || !fileInput.files[0]) return;

    btn.disabled = true;
    statusText.innerText = 'Scanning photo... ⏳';
    statusText.style.color = '#ff9800';

    try {
        const image = await faceapi.bufferToImage(fileInput.files[0]);
        const detection = await faceapi.detectSingleFace(image, new faceapi.TinyFaceDetectorOptions());

        if (detection) {
            statusText.innerText = '✅ Human Face Detected!';
            statusText.style.color = '#00ff88';
            btn.disabled = false;
        } else {
            statusText.innerText = '❌ No face detected! Please upload a real human photo.';
            statusText.style.color = 'red';
            fileInput.value = ''; 
        }
    } catch (e) {
        statusText.innerText = '❌ Error during scan.';
        statusText.style.color = 'red';
    }
}

const verifyFile = document.getElementById('verifyFile');
if(verifyFile) verifyFile.addEventListener('change', () => handleFaceDetection('verifyFile', 'verifyBtn', 'verifyAiStatus'));

const updateFile = document.getElementById('updateFile');
if(updateFile) updateFile.addEventListener('change', () => handleFaceDetection('updateFile', 'updateBtn', 'updateAiStatus'));
</script>
</body>
</html>