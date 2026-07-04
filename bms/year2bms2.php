<?php
// --- 1. MANDATORY PHP BACKEND LOGIC (PROTECTED) ---
session_start();
include '../connect.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$user = $_SESSION['user'];
$success = '';
$error = '';

// --- GROQ AI API INTEGRATION HANDLER (NEW) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'chat') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';
    $lang = $input['target_lang'] ?? 'en';

    $apiKey = "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"; 
    
    // Updated system prompt for Year 2 Semester 1 subjects
    $systemPrompt = "You are a helpful academic assistant for FCBS Digi Kuppiya Year 2 Semester 1. Help students with subjects like Macro Economics, Cost and Management Accounting, MIS, Business Law, and Career Guidance. Answer in $lang language.";

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $query]
        ]
    ]));

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(['response' => "Error: " . $err]);
    } else {
        $resData = json_decode($response, true);
        $aiText = $resData['choices'][0]['message']['content'] ?? "Sorry, I couldn't process that.";
        echo json_encode(['response' => $aiText]);
    }
    exit; 
}

// --- DATABASE UPDATE LOGIC (PROTECTED) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updatePic'])) {
        if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png']) && $_FILES['profilePic']['size'] <= 3 * 1024 * 1024) {
                if ($user['profilePic'] && file_exists('../' . $user['profilePic'])) unlink('../' . $user['profilePic']);
                $profilePic = 'uploads/' . uniqid('profile_', true) . '.' . $ext;
                move_uploaded_file($_FILES['profilePic']['tmp_name'], '../' . $profilePic);
                $stmt = $conn->prepare("UPDATE users SET profilePic=? WHERE id=?");
                $stmt->bind_param("si", $profilePic, $user['id']);
                if ($stmt->execute()) {
                    $res = $conn->query("SELECT * FROM users WHERE id=" . $user['id']);
                    $_SESSION['user'] = $res->fetch_assoc();
                    header("Location: " . $_SERVER['PHP_SELF'] . "?success=pic_updated"); exit;
                }
            }
        }
    }
    if (isset($_POST['updateDetails'])) {
        $fName = trim($_POST['firstName']); $lName = trim($_POST['lastName']); $mob = trim($_POST['mobile']);
        $stmt = $conn->prepare("UPDATE users SET firstName=?, lastName=?, mobile=? WHERE id=?");
        $stmt->bind_param("sssi", $fName, $lName, $mob, $user['id']);
        if ($stmt->execute()) {
            $res = $conn->query("SELECT * FROM users WHERE id=" . $user['id']);
            $_SESSION['user'] = $res->fetch_assoc();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=details_updated"); exit;
        }
    }
}

// User count for the "12+" style indicator
$userCountResult = $conn->query("SELECT COUNT(id) as total FROM users");
$row = $userCountResult->fetch_assoc();
$displayCount = ($row['total'] > 1) ? "+" . ($row['total'] - 1) : "0";

$profilePicPath = '../' . ($user['profilePic'] ?: 'default.png');
$fullName = htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
$email = htmlspecialchars($user['email']);
$academicYear = htmlspecialchars($user['batch'] ?? '2022/2023'); 

// --- Subject Configuration ---
$subjects = [
    ['title' => 'Operations Management', 'link' => 'om.php', 'icon' => 'eye', 'color' => 'from-blue-500 to-cyan-400', 'bg' => 'bg-blue-500/10', 'border' => 'border-blue-500/20', 'text' => 'text-blue-400'],
    ['title' => 'Financial Management', 'link' => 'fm.php', 'icon' => 'infinity', 'color' => 'from-emerald-500 to-teal-400', 'bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/20', 'text' => 'text-emerald-400'],
    ['title' => 'Business Skills II', 'link' => 'skill2.php', 'icon' => 'database', 'color' => 'from-violet-500 to-purple-400', 'bg' => 'bg-violet-500/10', 'border' => 'border-violet-500/20', 'text' => 'text-violet-400'],
    ['title' => 'Enterprenurship and Innovation', 'link' => 'entr.php', 'icon' => 'nut', 'color' => 'from-orange-500 to-yellow-400', 'bg' => 'bg-orange-500/10', 'border' => 'border-orange-500/20', 'text' => 'text-orange-400'],
    ['title' => 'Fundamental Sociology and Psychology', 'link' => 'funda.php', 'icon' => 'shuffle', 'color' => 'from-rose-500 to-pink-400', 'bg' => 'bg-rose-500/10', 'border' => 'border-rose-500/20', 'text' => 'text-rose-400'],
    ['title' => 'Peace and Social Harmony', 'link' => 'peace.php', 'icon' => 'star', 'color' => 'from-amber-500 to-orange-400', 'bg' => 'bg-amber-500/10', 'border' => 'border-amber-500/20', 'text' => 'text-orange-400'],
    ['title' => 'Basic Science', 'link' => 'science.php', 'icon' => 'star', 'color' => 'from-amber-500 to-orange-400', 'bg' => 'bg-amber-500/10', 'border' => 'border-amber-500/20', 'text' => 'text-orange-400']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCBS DIGI Kuppiya - Year II Semester II</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { background: linear-gradient(to bottom right, #0f172a, #1e1b4b, #312e81); min-height: 100vh; }
        .modal { display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter: blur(10px); }
        .modal-content { background:#1e293b; margin:10vh auto; padding:30px; border:1px solid rgba(255,255,255,0.1); width:90%; max-width:450px; border-radius:24px; color:white; }
        .loading-dots { display: inline-block; animation: blink 1s infinite; }
        @keyframes blink { 0% { opacity: 0; } 50% { opacity: 1; } 100% { opacity: 0; } }
    </style>
</head>
<body class="text-white font-sans" x-data="{ modalOpen: false, activeTab: 'details', profileDropdown: false }">

<header class="sticky top-0 z-50 w-full px-6 py-4 flex items-center justify-between backdrop-blur-md bg-white/5 border-b border-white/10">
    <div class="flex items-center gap-4">
        <img src="assets/img/logo.png" alt="Logo" class="h-12 w-auto">
        <span class="text-2xl font-bold text-white tracking-tight">FCBS <span class="text-yellow-300">Digi Kuppiya</span></span>
    </div>
    <div class="relative">
        <button @click="profileDropdown = !profileDropdown" class="flex items-center gap-3 bg-white/10 hover:bg-white/20 rounded-full pl-2 pr-4 py-1.5 transition-all border border-white/20">
            <img src="<?= $profilePicPath ?>" class="w-8 h-8 rounded-full object-cover border border-white/50">
            <div class="text-left hidden sm:block">
                <p class="text-white text-xs font-bold leading-none"><?= htmlspecialchars($user['firstName']) ?></p>
                <p class="text-[10px] text-yellow-300 font-medium"><?= $academicYear ?></p>
            </div>
            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
        </button>
        <div x-show="profileDropdown" @click.away="profileDropdown = false" class="absolute right-0 mt-3 w-56 bg-slate-900 rounded-2xl shadow-2xl py-2 border border-white/10">
            <div class="px-4 py-3 border-b border-white/5">
                <p class="text-xs text-white/50">Logged in as</p>
                <p class="text-sm font-bold truncate text-yellow-300"><?= $email ?></p>
                <p class="text-[10px] mt-1 text-blue-400 uppercase tracking-tighter font-bold"><?= $academicYear ?></p>
            </div>
            <button @click="modalOpen = true; profileDropdown = false" class="w-full text-left px-4 py-3 text-sm text-gray-300 hover:bg-white/10 flex items-center gap-2">
                <i data-lucide="user-cog" class="w-4 h-4"></i> Edit Account
            </button>
            <a href="../logout.php" class="block px-4 py-3 text-sm text-red-400 hover:bg-red-500/10 border-t border-white/10 flex items-center gap-2">
                <i data-lucide="log-out" class="w-4 h-4"></i> Sign out
            </a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 pt-12 pb-20">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-12 gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/20 text-white mb-6">
                <i data-lucide="sparkles" class="text-yellow-300 w-4 h-4"></i>
                <span class="font-medium text-xs uppercase tracking-widest">Year II Semester II</span>
            </div>
            <h1 class="text-5xl md:text-7xl font-bold text-white tracking-tight leading-tight">
                FCBS Digi Kuppiya <br />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-pink-300">2.1 Starts Here</span>
            </h1>
        </div>

        <div class="bg-white/5 border border-white/10 p-4 rounded-2xl flex items-center gap-4 shadow-2xl min-w-[200px]">
            <div class="h-12 w-12 rounded-xl bg-yellow-300/10 flex items-center justify-center border border-yellow-300/20">
                <i data-lucide="calendar" class="text-yellow-300 h-6 w-6"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-widest text-white/40 font-bold">Academic Year</p>
                <p class="text-lg font-bold text-white"><?= $academicYear ?></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($subjects as $s): ?>
        <a href="<?= $s['link'] ?>" class="group relative bg-white/5 rounded-3xl p-6 border <?= $s['border'] ?> shadow-xl transition-all duration-300 hover:-translate-y-2 hover:bg-white/10">
            <div class="mb-4 flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl <?= $s['bg'] ?> border <?= $s['border'] ?>">
                    <i data-lucide="<?= $s['icon'] ?>" class="h-6 w-6 <?= $s['text'] ?>"></i>
                </div>
                <span class="inline-block px-3 py-1 rounded-full bg-white/5 text-[10px] font-bold uppercase tracking-widest text-white/50">Modules Ready</span>
            </div>
            <h3 class="text-xl font-bold text-white group-hover:<?= $s['text'] ?> transition-colors mb-2"><?= $s['title'] ?></h3>
            <p class="text-sm text-white/40 mb-6">Access complete course materials, recordings and notes.</p>
            
            <div class="flex items-center justify-between pt-4 border-t border-white/5">
                <div class="flex -space-x-2">
                    <img src="<?= $profilePicPath ?>" class="h-7 w-7 rounded-full border-2 border-slate-900 object-cover">
                    <div class="h-7 w-7 rounded-full border-2 border-slate-900 bg-slate-800 text-[10px] flex items-center justify-center font-bold text-yellow-300"><?= $displayCount ?></div>
                </div>
                <div class="h-9 w-9 flex items-center justify-center rounded-full bg-white/5 group-hover:bg-gradient-to-r <?= $s['color'] ?> group-hover:text-slate-900 transition-all">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
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
            <img src="<?= $profilePicPath; ?>" class="w-24 h-24 rounded-3xl mx-auto mb-6 object-cover ring-4 ring-yellow-300/20 shadow-2xl">
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="profilePic" id="file-upload" class="hidden" accept="image/*" onchange="this.form.submit()">
                <input type="hidden" name="updatePic" value="1">
                <label for="file-upload" class="cursor-pointer inline-block bg-white/10 px-8 py-3 rounded-xl text-sm font-bold mb-4 hover:bg-white/20 transition-colors border border-white/10">Choose New Photo</label>
            </form>
        </div>
    </div>
</div>

<div class="fixed bottom-6 right-6 z-[1002] flex flex-col items-end">
    <div id="chatWindow" class="hidden mb-4 w-[calc(100vw-3rem)] sm:w-[380px] overflow-hidden rounded-3xl border border-white/10 bg-slate-900 shadow-2xl">
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center border border-white/20"><i data-lucide="bot" class="text-white h-6 w-6"></i></div>
                    <h3 class="font-bold text-white">Study Assistant</h3>
                </div>
                <button onclick="document.getElementById('chatWindow').classList.add('hidden')" class="text-white/50 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex gap-2">
                <button onclick="setLanguage('en', this)" class="lang-btn flex-1 bg-white/20 py-2 rounded-lg text-[10px] font-bold border border-white/10">EN</button>
                <button onclick="setLanguage('si', this)" class="lang-btn flex-1 bg-white/10 hover:bg-white/20 py-2 rounded-lg text-[10px] font-bold border border-white/10">සිංහල</button>
                <button onclick="setLanguage('ta', this)" class="lang-btn flex-1 bg-white/10 hover:bg-white/20 py-2 rounded-lg text-[10px] font-bold border border-white/10">தமிழ்</button>
            </div>
        </div>
        <div class="h-80 p-4 overflow-y-auto space-y-4" id="msgLog">
            <div class="bg-white/5 p-3 rounded-2xl text-xs text-white/70">Hello <?= htmlspecialchars($user['firstName']) ?>! Ask me anything about your 2.1 subjects.</div>
        </div>
        <div class="p-4 border-t border-white/5 flex gap-2">
            <input type="text" id="chatInput" placeholder="Type message..." class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm outline-none focus:border-blue-500">
            <button id="chatSendBtn" onclick="sendChat()" class="bg-blue-600 p-2 rounded-xl text-white hover:bg-blue-500"><i data-lucide="send" class="w-5 h-5"></i></button>
        </div>
    </div>
    <button onclick="document.getElementById('chatWindow').classList.toggle('hidden')" class="h-16 w-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl text-white shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all">
        <i data-lucide="message-square" class="h-8 w-8"></i>
    </button>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    let selectedLanguage = 'en';

    function setLanguage(lang, btn) {
        selectedLanguage = lang;
        document.querySelectorAll('.lang-btn').forEach(b => {
            b.classList.remove('bg-white/20');
            b.classList.add('bg-white/10');
        });
        btn.classList.add('bg-white/20');
        btn.classList.remove('bg-white/10');
    }

    function appendMessage(sender, text) {
        const log = document.getElementById('msgLog');
        const msgDiv = document.createElement('div');
        msgDiv.className = sender === 'user' ? 'bg-blue-600/20 ml-auto p-3 rounded-2xl text-xs max-w-[80%] border border-blue-500/30 text-white' : 'bg-white/5 p-3 rounded-2xl text-xs text-white/70 max-w-[80%] border border-white/10';
        msgDiv.innerHTML = text;
        log.appendChild(msgDiv);
        log.scrollTop = log.scrollHeight;
        return msgDiv;
    }

    async function sendChat() {
        const input = document.getElementById('chatInput');
        const btn = document.getElementById('chatSendBtn');
        const query = input.value.trim();
        if (!query) return;

        appendMessage('user', query);
        input.value = '';
        input.disabled = true;
        btn.disabled = true;

        const loadingMsg = appendMessage('ai', 'Thinking <span class="loading-dots">. . .</span>');

        try {
            const response = await fetch('?action=chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query, target_lang: selectedLanguage })
            });
            
            const data = await response.json();
            loadingMsg.innerHTML = data.response || "Sorry, I couldn't get a response.";
            
        } catch (error) {
            loadingMsg.innerHTML = "An error occurred. Please try again.";
        } finally {
            input.disabled = false;
            btn.disabled = false;
            input.focus();
        }
    }

    document.getElementById('chatInput').addEventListener('keypress', (e) => { if(e.key === 'Enter') sendChat(); });
</script>
</body>
</html>