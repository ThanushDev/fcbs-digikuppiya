<?php
// --- 1. MANDATORY PHP BACKEND LOGIC (Database & Session) ---
session_start();
include '../connect.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$user = $_SESSION['user'];
$success = '';
$error = '';

// --- LOGIC FOR PROFILE PICTURE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatePic'])) {
    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png']) && $_FILES['profilePic']['size'] <= 3 * 1024 * 1024) {
            if ($user['profilePic'] && file_exists('../' . $user['profilePic'])) {
                unlink('../' . $user['profilePic']);
            }
            if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
            $profilePic = 'uploads/' . uniqid('profile_', true) . '.' . $ext;
            move_uploaded_file($_FILES['profilePic']['tmp_name'], '../' . $profilePic);
            $sql = "UPDATE users SET profilePic=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $profilePic, $user['id']);
            if ($stmt->execute()) {
                $res = $conn->query("SELECT * FROM users WHERE id=" . $user['id']);
                $_SESSION['user'] = $res->fetch_assoc();
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=pic_updated"); 
                exit;
            } else { $error = $stmt->error; }
            $stmt->close();
        } else { $error = "Invalid image (max 3MB JPG/PNG)"; }
    }
}

// --- LOGIC: TEXT PROFILE DETAILS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateDetails'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $mobile = trim($_POST['mobile']); 
    if (!empty($firstName) && !empty($lastName) && !empty($mobile)) {
        $sql = "UPDATE users SET firstName=?, lastName=?, mobile=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $firstName, $lastName, $mobile, $user['id']);
        if ($stmt->execute()) {
            $res = $conn->query("SELECT * FROM users WHERE id=" . $user['id']);
            $_SESSION['user'] = $res->fetch_assoc();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=details_updated");
            exit;
        } else { $error = "Database update failed: " . $stmt->error; }
        $stmt->close();
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'pic_updated') $success = "Profile picture updated!";
    elseif ($_GET['success'] == 'details_updated') $success = "Profile details updated!";
}

$user = $_SESSION['user'];
$profilePicPath = '../' . ($user['profilePic'] ?: 'default.png'); 
$fullName = htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
$department = htmlspecialchars($user['department']);
$batch = htmlspecialchars($user['batch']);
$mobile = htmlspecialchars($user['mobile']);
$email = htmlspecialchars($user['email']);

// --- UPDATED VIDEO DATA (Management Information System) ---
$VIDEOS = [
    ['id' => 'Us1J4ah3Duo', 'title' => 'Global Business - Video 01', 'duration' => '01:36:52', 'views' => 'FCBS Digi Kuppiya'],
    ['id' => 'NYfHZ3bMe_s', 'title' => 'Global Business - Video 02', 'duration' => '01:55:26', 'views' => 'FCBS Digi Kuppiya'],
    ['id' => 'Biyu3jfwQlw', 'title' => 'Information Systems in Business', 'duration' => '41:23', 'views' => 'FCBS Digi Kuppiya'],
    ['id' => 'XmNmXbu-4N8', 'title' => 'Business Intelligence - Part 01', 'duration' => '44:26', 'views' => 'FCBS Digi Kuppiya'],
    ['id' => 'pEC_oE8tcy8', 'title' => 'Business Intelligence - Part 02', 'duration' => '01:01:12', 'views' => 'FCBS Digi Kuppiya'],
    ['id' => 'gRgUBQbpnFg', 'title' => 'Ethical and Social Issues', 'duration' => '01:45:54', 'views' => 'FCBS Digi Kuppiya'],
    ['id' => 'SnasZG_9eKY', 'title' => 'Past Paper Discussion - Section A', 'duration' => '54:27', 'views' => 'FCBS Digi Kuppiya'],
    ['id' => 'fZJNM-mEHDo', 'title' => 'Past Paper Discussion - Section B', 'duration' => '54:33', 'views' => 'FCBS Digi Kuppiya']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCBS Digi Kuppiya - Management Information System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        .hidden { display: none; }
    </style>
</head>
<body class="text-slate-200">

    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-5">
                <a href="year2bms.php">
                    <img src="assets/img/logo.png" alt="FCBS Logo" class="h-12 w-auto object-contain">
                </a>
                <div class="h-8 w-[1px] bg-slate-800 hidden sm:block"></div>
                <div>
                    <h1 class="text-lg font-extrabold text-white leading-tight tracking-tight">FCBS Digi Kuppiya</h1>
                    <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider">Learning Portal</div>
                </div>
            </div>

            <div class="relative">
                <button onclick="toggleDropdown()" class="flex items-center gap-3 p-1 rounded-full hover:bg-slate-800 transition-all border border-transparent hover:border-slate-700">
                    <img src="<?= $profilePicPath ?>" class="w-10 h-10 rounded-full object-cover border-2 border-indigo-500 shadow-sm" alt="User">
                    <div class="hidden md:block text-left mr-2">
                        <p class="text-xs font-bold text-white leading-none"><?= $fullName ?></p>
                        <p class="text-[10px] text-slate-400 mt-1">Student Profile</p>
                    </div>
                    <i class="fas fa-chevron-down text-slate-500 text-xs mr-2"></i>
                </button>
                
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-72 bg-slate-900 rounded-xl shadow-2xl border border-slate-800 py-4 z-50">
                    <div class="px-4 pb-3 border-b border-slate-800 flex items-center gap-3">
                        <img src="<?= $profilePicPath ?>" class="w-12 h-12 rounded-full object-cover border border-indigo-900">
                        <div class="overflow-hidden">
                            <p class="text-sm font-bold text-white truncate"><?= $fullName ?></p>
                            <p class="text-xs text-slate-400 truncate"><?= $email ?></p>
                        </div>
                    </div>
                    <div class="p-3 space-y-1">
                        <div class="px-4 py-2 text-[11px] text-slate-500 font-bold uppercase tracking-widest">Enrollment</div>
                        <div class="px-4 py-1 text-xs text-slate-300 flex items-center gap-2"><i class="fas fa-graduation-cap text-indigo-500 w-4"></i> Batch: <?= $batch ?></div>
                        <div class="px-4 py-1 text-xs text-slate-300 flex items-center gap-2"><i class="fas fa-building text-indigo-500 w-4"></i> Dept: <?= $department ?></div>
                    </div>
                    <div class="p-2 border-t border-slate-800">
                        <button onclick="openModal()" class="w-full text-left px-4 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-indigo-400 rounded-lg flex items-center gap-2 font-medium">
                            <i class="fas fa-user-edit w-4 text-indigo-500"></i> Edit Profile
                        </button>
                        <a href="../logout.php" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-950/30 rounded-lg flex items-center gap-2 font-medium">
                            <i class="fas fa-sign-out-alt w-4 text-red-500"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <nav class="flex text-xs font-bold text-indigo-400 uppercase tracking-widest mb-2 gap-2">
                    <span>Year II</span> <span class="text-slate-700">/</span> <span>Semester I</span>
                </nav>
                <h2 class="text-4xl font-black text-white tracking-tight">Management Information System</h2>
                <p class="text-slate-400 mt-2 font-medium">Course Code: BMT- 2023</p>
            </div>
            <div class="flex gap-2">
                <div class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 shadow-sm text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Available Lessons</p>
                    <p class="text-lg font-black text-white"><?= count($VIDEOS) ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($VIDEOS as $v): ?>
            <div class="bg-slate-900 rounded-2xl shadow-lg border border-slate-800 overflow-hidden hover:shadow-indigo-500/10 hover:-translate-y-1 transition-all duration-300 group">
                <div class="relative aspect-video bg-black">
                    <iframe class="w-full h-full" src="https://www.youtube.com/embed/<?= $v['id'] ?>" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="p-6">
                    <h3 class="font-bold text-white text-lg mb-4 line-clamp-2 group-hover:text-indigo-400 transition-colors"><?= $v['title'] ?></h3>
                    <div class="flex items-center justify-between">
                        <span class="bg-indigo-950 text-indigo-300 text-[10px] font-black uppercase px-2.5 py-1 rounded-md tracking-wider border border-indigo-900">🕒 <?= $v['duration'] ?></span>
                        <span class="text-slate-500 text-[11px] font-bold"><i class="fas fa-eye mr-1 text-slate-600"></i> <?= $v['views'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer class="bg-slate-950 border-t border-slate-900 py-8 mt-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-slate-500 text-sm font-bold">© <?= date('Y') ?> FCBS Digi Kuppiya. All Rights Reserved.</p>
            <p class="text-slate-600 text-[10px] mt-1 uppercase tracking-widest">Design by Mr.Thanush</p>
        </div>
    </footer>

    <div id="editModal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 w-full max-w-md rounded-3xl shadow-2xl border border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900">
                <h2 class="text-xl font-black text-white">Profile Settings</h2>
                <button onclick="closeModal()" class="h-8 w-8 rounded-full bg-slate-800 text-slate-500 hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-8">
                <?php if ($success || $error): ?>
                    <div class="mb-6 p-4 rounded-xl text-xs font-bold uppercase tracking-wider text-center <?= $success ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
                        <?= $success ?: $error ?>
                    </div>
                <?php endif; ?>

                <div class="flex gap-2 mb-8 p-1.5 bg-slate-950 rounded-2xl">
                    <button onclick="switchTab('tab-details')" id="btn-details" class="flex-1 py-2.5 text-sm font-black rounded-xl bg-slate-800 shadow-sm text-indigo-400 transition-all">My Details</button>
                    <button onclick="switchTab('tab-photo')" id="btn-photo" class="flex-1 py-2.5 text-sm font-black rounded-xl text-slate-500 hover:text-slate-400 transition-all">Profile Photo</button>
                </div>

                <form id="tab-details" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">First Name</label>
                        <input type="text" name="firstName" value="<?= htmlspecialchars($user['firstName']) ?>" required class="w-full px-5 py-3 bg-slate-950 border-2 border-slate-800 rounded-2xl focus:border-indigo-500 focus:outline-none font-bold text-white transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Last Name</label>
                        <input type="text" name="lastName" value="<?= htmlspecialchars($user['lastName']) ?>" required class="w-full px-5 py-3 bg-slate-950 border-2 border-slate-800 rounded-2xl focus:border-indigo-500 focus:outline-none font-bold text-white transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Mobile Number</label>
                        <input type="text" name="mobile" value="<?= htmlspecialchars($user['mobile']) ?>" required class="w-full px-5 py-3 bg-slate-950 border-2 border-slate-800 rounded-2xl focus:border-indigo-500 focus:outline-none font-bold text-white transition-all">
                    </div>
                    <button type="submit" name="updateDetails" class="w-full bg-indigo-600 text-white font-black py-4 rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-900/50 mt-4">Save Changes</button>
                </form>

                <form id="tab-photo" method="POST" enctype="multipart/form-data" class="hidden space-y-8 text-center">
                    <img src="<?= $profilePicPath ?>" id="preview-img" class="w-36 h-36 rounded-full mx-auto object-cover border-4 border-slate-800 shadow-xl ring-2 ring-indigo-900/50">
                    <div>
                        <input type="file" name="profilePic" id="file-input" class="hidden" accept="image/jpeg,image/png">
                        <label for="file-input" class="cursor-pointer inline-flex items-center gap-2 bg-indigo-950 border border-indigo-900 px-6 py-3 rounded-2xl text-xs font-black text-indigo-400 hover:bg-indigo-900 transition-all uppercase tracking-wider">
                            <i class="fas fa-camera"></i> <span id="file-name">Choose New Picture</span>
                        </label>
                    </div>
                    <button type="submit" name="updatePic" class="w-full bg-indigo-600 text-white font-black py-4 rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-900/50">Upload & Save</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() { document.getElementById('dropdownMenu').classList.toggle('hidden'); }
        function openModal() { document.getElementById('editModal').classList.remove('hidden'); document.getElementById('dropdownMenu').classList.add('hidden'); }
        function closeModal() { document.getElementById('editModal').classList.add('hidden'); }
        function switchTab(tabId) {
            document.getElementById('tab-details').classList.add('hidden');
            document.getElementById('tab-photo').classList.add('hidden');
            document.getElementById(tabId).classList.remove('hidden');
            const isDetails = tabId === 'tab-details';
            document.getElementById('btn-details').className = isDetails ? 'flex-1 py-2.5 text-sm font-black rounded-xl bg-slate-800 shadow-sm text-indigo-400' : 'flex-1 py-2.5 text-sm font-black rounded-xl text-slate-500 hover:text-slate-400';
            document.getElementById('btn-photo').className = !isDetails ? 'flex-1 py-2.5 text-sm font-black rounded-xl bg-slate-800 shadow-sm text-indigo-400' : 'flex-1 py-2.5 text-sm font-black rounded-xl text-slate-500 hover:text-slate-400';
        }
        window.onclick = function(e) { if (!e.target.closest('.relative')) document.getElementById('dropdownMenu').classList.add('hidden'); if (e.target.id === 'editModal') closeModal(); }
        document.getElementById('file-input').addEventListener('change', function() { if(this.files[0]) document.getElementById('file-name').textContent = this.files[0].name; });
        <?php if (isset($_GET['success']) || $error): ?>
            window.onload = function() { openModal(); <?php if(isset($_GET['success']) && $_GET['success'] == 'pic_updated') echo "switchTab('tab-photo');"; ?> };
        <?php endif; ?>
    </script>
</body>
</html>