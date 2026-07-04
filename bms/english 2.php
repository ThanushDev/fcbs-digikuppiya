<?php
// --- 1. MANDATORY PHP BACKEND LOGIC ---
session_start();
include '../connect.php'; 

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$user = $_SESSION['user'];

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
                header("Location: " . $_SERVER['PHP_SELF']); 
                exit;
            }
        }
    }
}

// --- LOGIC: TEXT PROFILE DETAILS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateDetails'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $mobile = trim($_POST['mobile']); 

    $sql = "UPDATE users SET firstName=?, lastName=?, mobile=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $firstName, $lastName, $mobile, $user['id']);
    if ($stmt->execute()) {
        $res = $conn->query("SELECT * FROM users WHERE id=" . $user['id']);
        $_SESSION['user'] = $res->fetch_assoc();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$user = $_SESSION['user'];
$profilePicPath = ($user['profilePic']) ? '../' . $user['profilePic'] : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user['firstName'];

/**
 * ResourceCard function
 */
function renderResourceCard($title, $count, $icon, $color, $link) {
    $colorStyles = [
        'blue'    => 'text-blue-400 border-blue-500/20 bg-blue-500/10',
        'purple'  => 'text-violet-400 border-violet-500/20 bg-violet-500/10',
        'emerald' => 'text-emerald-400 border-emerald-500/20 bg-emerald-500/10',
        'orange'  => 'text-orange-400 border-orange-500/20 bg-orange-500/10'
    ];
    $styleClass = $colorStyles[$color] ?? $colorStyles['blue'];
?>
    <a href="<?= $link ?>" class="resource-card group relative overflow-hidden rounded-3xl border p-8 min-h-[210px] flex flex-col justify-between transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 bg-white/5 <?= $styleClass ?> no-underline">
        <div class="flex items-start justify-between">
            <div class="icon-container rounded-2xl p-4 bg-white/5 border border-white/10 transition-transform duration-500">
                <i data-lucide="<?= $icon ?>" class="h-8 w-8"></i>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/50 mb-1"><?= $title ?></p>
                <h3 class="text-5xl font-black text-white tracking-tighter"><?= $count ?></h3>
            </div>
        </div>
        <div class="mt-6">
            <div class="flex items-center text-sm font-semibold text-white">
                <span>Access Files</span>
                <i data-lucide="arrow-right" class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-2"></i>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 h-1.5 w-0 bg-current opacity-30 transition-all duration-700 group-hover:w-full"></div>
    </a>
<?php } ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCBS Digi Kuppiya - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background: #0f172a; min-height: 100vh; }
        .resource-card:hover .icon-container { transform: scale(1.1) rotate(5deg); }
        .modal { display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter: blur(10px); }
        .modal-content { background:#1e293b; margin:10vh auto; padding:30px; border-radius:24px; width:90%; max-width:450px; color:white; border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="text-white" x-data="{ modalOpen: false, activeTab: 'details', profileDropdown: false }">

    <header class="sticky top-0 z-40 w-full border-b border-white/10 bg-white/5 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <img src="assets/img/logo.png" alt="Logo" class="h-10 w-auto">
                <span class="text-xl font-bold tracking-tight text-white">
                    FCBS <span class="text-yellow-300">Digi Kuppiya</span>
                </span>
            </div>

            <div class="relative">
                <button @click="profileDropdown = !profileDropdown" class="flex items-center gap-3 bg-white/10 hover:bg-white/20 rounded-full pl-2 pr-4 py-1.5 transition-all border border-white/20">
                    <img src="<?= $profilePicPath ?>" class="w-8 h-8 rounded-full object-cover">
                    <span class="text-white font-medium hidden sm:block"><?= htmlspecialchars($user['firstName']) ?></span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                </button>
                <div x-show="profileDropdown" @click.away="profileDropdown = false" class="absolute right-0 mt-3 w-56 bg-slate-900 rounded-2xl shadow-2xl py-2 border border-white/10">
                    <button @click="modalOpen = true; profileDropdown = false" class="w-full text-left px-4 py-3 text-sm text-gray-300 hover:bg-white/10 flex items-center gap-2">
                        <i data-lucide="user-cog" class="w-4 h-4 text-yellow-300"></i> Edit Profile
                    </button>
                    <a href="../logout.php" class="block px-4 py-3 text-sm text-red-400 hover:bg-red-500/10 border-t border-white/10 flex items-center gap-2">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Sign out
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-[2.5rem] bg-white/5 p-10 border border-white/10 relative overflow-hidden mb-12">
            <div class="absolute top-0 right-0 -mt-8 -mr-8 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="relative z-10">
                <div class="mb-4 inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-yellow-300 uppercase tracking-widest">
                    BMT- 1073
                </div>
                <h1 class="text-4xl font-black text-white sm:text-6xl mb-2">Business English II</h1>
                <p class="text-white/60 text-lg">Year I Semester II • Academic Resource Portal</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <?php
            renderResourceCard("Lecture Notes", "Upload Soon", "book-open", "blue", "");
            renderResourceCard("Past Papers", "04", "file-text", "purple", "english 2 pp.php");
            renderResourceCard("Short Notes", "Upload Soon", "sticky-note", "emerald", "");
            renderResourceCard("Kuppi Video", "Upload Soon", "video", "orange", "");
            ?>
        </div>
    </main>

    <div class="modal" :style="modalOpen ? 'display:block' : 'display:none'">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-yellow-300">Account Settings</h2>
                <button @click="modalOpen = false" class="text-gray-400 hover:text-white"><i data-lucide="x"></i></button>
            </div>
            <div class="flex gap-2 mb-6 bg-white/5 p-1 rounded-xl text-xs font-bold">
                <button @click="activeTab = 'details'" :class="activeTab === 'details' ? 'bg-white text-slate-900' : 'text-white'" class="flex-1 py-2.5 rounded-lg transition-all">Details</button>
                <button @click="activeTab = 'picture'" :class="activeTab === 'picture' ? 'bg-white text-slate-900' : 'text-white'" class="flex-1 py-2.5 rounded-lg transition-all">Photo</button>
            </div>
            
            <div x-show="activeTab === 'details'">
                <form method="post" class="space-y-4">
                    <input type="text" name="firstName" value="<?= htmlspecialchars($user['firstName']); ?>" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-yellow-300 text-sm">
                    <input type="text" name="lastName" value="<?= htmlspecialchars($user['lastName']); ?>" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-yellow-300 text-sm">
                    <input type="text" name="mobile" value="<?= htmlspecialchars($user['mobile']); ?>" class="w-full bg-slate-800 border border-white/10 rounded-xl p-3 outline-none focus:border-yellow-300 text-sm">
                    <button type="submit" name="updateDetails" class="w-full bg-gradient-to-r from-yellow-300 to-orange-400 text-slate-900 font-bold py-3.5 rounded-xl mt-2 shadow-lg">Update Profile</button>
                </form>
            </div>
            
            <div x-show="activeTab === 'picture'" class="text-center">
                <img src="<?= $profilePicPath; ?>" class="w-24 h-24 rounded-3xl mx-auto mb-6 object-cover ring-4 ring-yellow-300/20 shadow-xl">
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="profilePic" id="file-upload" class="hidden" accept="image/*" onchange="this.form.submit()">
                    <input type="hidden" name="updatePic" value="1">
                    <label for="file-upload" class="cursor-pointer inline-block bg-white/10 border border-white/10 px-8 py-3 rounded-xl text-sm font-bold mb-4 hover:bg-white/20 transition-colors">Choose New Photo</label>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>