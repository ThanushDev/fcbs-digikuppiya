<?php
// --- 1. MANDATORY PHP BACKEND LOGIC ---
session_start();
include '../connect.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$user = $_SESSION['user'];
$success = '';
$error = '';

// --- PROFILE UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateDetails'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $mobile = trim($_POST['mobile']); 
    if (!empty($firstName) && !empty($lastName)) {
        $sql = "UPDATE users SET firstName=?, lastName=?, mobile=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $firstName, $lastName, $mobile, $user['id']);
        if ($stmt->execute()) {
            $res = $conn->query("SELECT * FROM users WHERE id=" . $user['id']);
            $_SESSION['user'] = $res->fetch_assoc();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        }
    }
}

$user = $_SESSION['user'];
$profilePicPath = '../' . ($user['profilePic'] ?: 'default.png'); 
$fullName = htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
$academicYear = htmlspecialchars($user['batch']);

// Subject Data
$subjectName = "Marketing Management";
$subjectCode = "MKT 1013";

// Drive Links Data
$lessons = [
    ['year' => '19/20', 'title' => 'Past Paper 01', 'link' => 'https://drive.google.com/file/d/1A7tLxpFTqXccX9vkd7Qy_52L8VFy1NGC/view?usp=drive_link'],
    ['year' => '21/22', 'title' => 'Past Paper 02', 'link' => 'https://drive.google.com/file/d/1fRQuVa_PUGN9Ts9ojLWpqvN_R51Iefqt/view?usp=drive_link'],
    ['year' => '22/23', 'title' => 'Past Paper 03', 'link' => 'https://drive.google.com/file/d/1tjwWtsaGdmnZpljsZQyktFIzFKFPzHxr/view?usp=drive_link'],

];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $subjectName ?> | Study Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #0b0f1a;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(79, 70, 229, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(147, 51, 234, 0.1) 0%, transparent 40%),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M10 10H90V90H10V10Z' fill='none' stroke='%23ffffff' stroke-opacity='0.03' stroke-width='0.5'/%3E%3C/svg%3E");
        }
        .resource-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .resource-card:hover {
            border-color: rgba(99, 102, 241, 0.5);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px -10px rgba(79, 70, 229, 0.3);
        }
        .hidden { display: none; }
    </style>
</head>
<body class="text-slate-200 min-h-screen">

    <header class="bg-slate-900/60 backdrop-blur-xl border-b border-slate-800 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="mkt.php" class="hover:text-indigo-400 transition-colors">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                <div class="h-8 w-px bg-slate-800 hidden sm:block"></div>
                <div>
                    <h1 class="text-lg font-black text-white leading-none"><?= $subjectName ?></h1>
                    <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest"><?= $subjectCode ?></span>
                </div>
            </div>

            <div class="relative">
                <button onclick="toggleDropdown()" class="flex items-center gap-3 p-1 rounded-full hover:bg-slate-800 transition-all border border-slate-700 bg-slate-900/50">
                    <img src="<?= $profilePicPath ?>" class="w-10 h-10 rounded-full object-cover border-2 border-indigo-500 shadow-lg">
                    <div class="hidden md:block text-left mr-2">
                        <p class="text-xs font-bold text-white"><?= $fullName ?></p>
                        <p class="text-[9px] text-slate-500 font-bold uppercase">Batch: <?= $academicYear ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-slate-500 text-xs mr-2"></i>
                </button>
                
                <div id="dropdownMenu" class="hidden absolute right-0 mt-3 w-64 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl py-2 z-50 overflow-hidden">
                    <button onclick="openModal()" class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-800 hover:text-indigo-400 transition-colors flex items-center gap-3">
                        <i class="fas fa-cog text-slate-500"></i> Settings
                    </button>
                    <a href="../logout.php" class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-red-900/20 transition-colors flex items-center gap-3">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-12">
            <h2 class="text-4xl font-black text-white tracking-tight">Past Paper</h2>
            <p class="text-slate-400 mt-2 font-medium">Access your study materials and documents for this semester.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($lessons as $lesson): ?>
            <div class="resource-card rounded-[2rem] p-8 transition-all duration-300 group text-center flex flex-col items-center">
                
                <div class="relative mb-6">
                    <div class="w-20 h-20 bg-slate-900 rounded-2xl border-2 border-slate-800 flex items-center justify-center transition-all group-hover:border-indigo-500/50 shadow-inner">
                        <i class="fas fa-layer-group text-indigo-500 text-3xl group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="absolute -top-2 -right-2 bg-indigo-600 text-white text-[9px] font-black px-2 py-1 rounded shadow-lg border border-indigo-400">
                        <?= $lesson['year'] ?>
                    </div>
                </div>

                <h3 class="font-bold text-white text-lg leading-tight mb-2"><?= $lesson['title'] ?></h3>
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-8">Course Material</p>
                
                <a href="<?= $lesson['link'] ?>" target="_blank" class="w-full py-4 rounded-2xl bg-slate-800 text-white font-black text-xs uppercase tracking-widest hover:bg-indigo-600 transition-all flex items-center justify-center gap-3 border border-slate-700 hover:border-indigo-400">
                    <i class="fas fa-external-link-alt"></i> View Resource
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="editModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 w-full max-w-md rounded-[2.5rem] border border-slate-800 shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-slate-800 flex justify-between items-center">
                <h2 class="text-xl font-black text-white">Profile Update</h2>
                <button onclick="closeModal()" class="text-slate-500 hover:text-white transition-colors"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-10">
                <form method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">First Name</label>
                        <input type="text" name="firstName" value="<?= htmlspecialchars($user['firstName']) ?>" class="w-full bg-slate-950 border-2 border-slate-800 rounded-2xl px-5 py-3.5 focus:border-indigo-600 outline-none transition-all font-bold text-white">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Last Name</label>
                        <input type="text" name="lastName" value="<?= htmlspecialchars($user['lastName']) ?>" class="w-full bg-slate-950 border-2 border-slate-800 rounded-2xl px-5 py-3.5 focus:border-indigo-600 outline-none transition-all font-bold text-white">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Mobile</label>
                        <input type="text" name="mobile" value="<?= htmlspecialchars($user['mobile']) ?>" class="w-full bg-slate-950 border-2 border-slate-800 rounded-2xl px-5 py-3.5 focus:border-indigo-600 outline-none transition-all font-bold text-white">
                    </div>
                    <button type="submit" name="updateDetails" class="w-full bg-indigo-600 text-white font-black py-4 rounded-2xl shadow-xl shadow-indigo-900/40 hover:bg-indigo-500 transition-all mt-4 uppercase tracking-widest text-xs">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() { document.getElementById('dropdownMenu').classList.toggle('hidden'); }
        function openModal() { document.getElementById('editModal').classList.remove('hidden'); document.getElementById('dropdownMenu').classList.add('hidden'); }
        function closeModal() { document.getElementById('editModal').classList.add('hidden'); }
        
        window.onclick = function(e) {
            if (!e.target.closest('.relative')) document.getElementById('dropdownMenu').classList.add('hidden');
            if (e.target.id === 'editModal') closeModal();
        }
    </script>
</body>
</html>