<?php
session_start();

$icon_classes = [
    'fa-graduation-cap', 'fa-book', 'fa-code', 'fa-flask', 'fa-pencil', 'fa-chalkboard-user',
    'fa-lightbulb', 'fa-atom', 'fa-chart-line', 'fa-laptop-code', 'fa-user-graduate', 'fa-infinity',
    'fa-calculator', 'fa-microscope', 'fa-globe', 'fa-brain', 'fa-scroll', 'fa-puzzle-piece',
    'fa-ruler', 'fa-magnifying-glass', 'fa-terminal', 'fa-vial', 'fa-compass', 'fa-comment-dots',
    'fa-cloud', 'fa-star', 'fa-paperclip', 'fa-sitemap', 'fa-rocket', 'fa-cogs',
    'fa-shield-halved', 'fa-trophy', 'fa-feather', 'fa-shapes', 'fa-dna', 'fa-table', 
    'fa-seedling', 'fa-mobile-screen', 'fa-wifi', 'fa-bolt'
];

$total_icons = 40;
$html_icons = '';
for ($i = 0; $i < $total_icons; $i++) {
    $icon_class = $icon_classes[$i % count($icon_classes)];
    $left_pos = rand(-10, 110); 
    $top_pos = rand(-10, 110); 
    $size = rand(25, 70) / 10; 
    $duration = rand(40, 80); 
    $delay = rand(0, 40); 
    $opacity = rand(8, 25) / 100; 
    $hue = rand(240, 270); 
    
    $html_icons .= '<i class="fa-solid ' . $icon_class . ' floating-icon" style="
        top: ' . $top_pos . '%;
        left: ' . $left_pos . '%; 
        font-size: ' . $size . 'rem;
        color: hsl(' . $hue . ', 60%, 70%, ' . $opacity . ');
        animation-duration: ' . $duration . 's;
        animation-delay: ' . $delay . 's;
    "></i>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login & Signup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <meta name="google-site-verification" content="jIUnHEAwY2XvCsmCuCjvlsK3nAPsZUOTmnN55aNTVFo" />
  <meta name="google-site-verification" content="zi_x0qIdawIbBaybQbEC3huUkmlGtubL4GygoJHJ-FA" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

  <style>
    :root {
      --primary: #6a11cb;
      --primary-hover: #2575fc;
      --danger: #dc3545;
      --bg: #0d0f1a;
      --card-bg: #141726;
      --white: #fff;
      --text-dark: #e5e7ef;
      --text-light: #a0a3b1;
      --radius: 16px;
      --shadow: 0 8px 30px rgba(0,0,0,0.6);
      --input-bg: #1b1f32;
      --border-color: #2a2d40;
      --tilt-max: 10deg;
      --max-depth: 10px;
    }

    * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    body {
      background: linear-gradient(135deg, #0d0f1a, #090b13);
      display:flex;
      justify-content:center;
      align-items:center;
      min-height:100vh;
      padding:20px;
      color: var(--text-dark);
      overflow-y: auto; 
      perspective: 1000px; 
    }

    @keyframes moveBackground {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, rgba(106, 17, 203, 0.1), transparent 50%),
                    radial-gradient(circle at bottom right, rgba(37, 117, 252, 0.1), transparent 50%);
        background-size: 400% 400%;
        animation: moveBackground 30s ease infinite;
        z-index: -1;
    }

    @keyframes float {
      0% { transform: translate(0, 0) rotate(0deg); }
      25% { transform: translate(15vw, 15vh) rotate(45deg); }
      50% { transform: translate(-15vw, 30vh) rotate(-45deg); }
      75% { transform: translate(5vw, 45vh) rotate(90deg); }
      100% { transform: translate(-5vw, 0) rotate(0deg); } 
    }

    .floating-icons-wrapper {
      position: fixed; 
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 1; 
      pointer-events: none;
    }

    .floating-icon {
      position: absolute;
      display: block;
      animation-name: float;
      animation-timing-function: ease-in-out; 
      animation-iteration-count: infinite;
    }

    .container {
      display:flex;
      flex-direction:column;
      width:100%;
      max-width:420px;
      background: var(--card-bg);
      padding:35px 25px;
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      transition: all 0.3s ease;
      position:relative;
      border:1px solid rgba(255,255,255,0.05);
      z-index: 10;
    }

    .container:hover {
      box-shadow: 0 15px 45px rgba(0,0,0,0.8);
      transform: translateY(-5px);
    }

    .logo-container {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 20px;
    }

    .logo-container img {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(255,255,255,0.1);
      box-shadow: 0 0 20px rgba(106,17,203,0.4);
    }

    h2 {
      margin-bottom:15px;
      color:var(--white);
      font-size:1.7rem;
      font-weight:700;
      text-align:center;
    }

    .profile img {
      width:70px; height:70px; border-radius:50%; object-fit:cover;
      margin:0 auto 10px; border:3px solid rgba(255,255,255,0.1); display:block;
      box-shadow: 0 0 15px rgba(106,17,203,0.3);
    }
    .profile h2 { margin-bottom:5px; text-align:center; font-size:1.4rem; }
    .profile p { margin-bottom:10px; text-align:center; color:var(--text-light); font-size:13px; }

    form { display:flex; flex-direction:column; gap:14px; }

    .input-group { position:relative; width:100%; display:flex; flex-direction:column; }

    .input-group input,
    .input-group select,
    .input-group .file-input {
      width:100%;
      padding:10px 38px 10px 36px;
      border:1px solid var(--border-color);
      border-radius:8px;
      font-size:14px;
      transition:border 0.3s, box-shadow 0.3s, background 0.3s;
      height:38px;
      background: var(--input-bg);
      color: var(--white);
    }

    .input-group input:focus,
    .input-group select:focus,
    .input-group .file-input:focus {
      border-color:var(--primary-hover);
      outline:none;
      box-shadow:0 0 0 3px rgba(106,17,203,0.25);
    }

    .input-group label {
      position:absolute;
      left:36px;
      top:50%;
      transform:translateY(-50%);
      color:var(--text-light);
      font-size:13px;
      pointer-events:none;
      transition: all 0.2s ease;
      background: var(--input-bg);
      padding:0 3px;
      z-index: 20;
    }

    .input-group input:focus + label,
    .input-group input:not(:placeholder-shown) + label {
      top:-7px;
      left:34px;
      font-size:11px;
      color:var(--primary);
      background: var(--input-bg);
    }

    .input-group i.left-icon {
      position:absolute;
      left:10px;
      top:50%;
      transform:translateY(-50%);
      color:var(--text-light);
      font-size:14px;
      z-index: 20;
    }

    .toggle-password {
      position:absolute;
      right:10px;
      top:50%;
      transform:translateY(-50%);
      cursor:pointer;
      color:var(--text-light);
      font-size:14px;
      transition:color 0.2s;
      z-index: 20;
    }
    .toggle-password:hover { color:var(--primary-hover); }

    .strength-line {
      height:3px;
      width:100%;
      background:#333;
      border-radius:3px;
      margin-top:2px;
      transition:background 0.3s;
    }

    #passwordStrengthText {
      margin-top:1px;
      font-size:10px;
      font-weight:600;
      align-self:flex-start;
    }

    .file-note {
      font-size:11px;
      color:var(--text-light);
      margin-top:2px;
      align-self:flex-start;
    }

    button {
      padding:12px;
      border:none;
      border-radius:8px;
      font-size:15px;
      font-weight:600;
      cursor:pointer;
      color:#fff;
      transition:all 0.3s ease;
      background:linear-gradient(90deg,#6a11cb,#2575fc);
      box-shadow:0 0 15px rgba(37,117,252,0.4);
    }
    button:hover { transform:translateY(-2px); box-shadow:0 0 20px rgba(106,17,203,0.5); }
    button:disabled { opacity: 0.5; cursor: not-allowed; }

    .logout-btn {
      background:linear-gradient(90deg,#dc3545,#ff6b6b);
      box-shadow:0 0 15px rgba(220,53,69,0.4);
    }

    .switch-link {
      font-size:13px;
      color:var(--primary);
      text-align:center;
      cursor:pointer;
      transition:color 0.2s;
    }
    .switch-link:hover { text-decoration:underline; }

    .hidden { display:none; }

    .input-group select {
      height:38px;
      padding-left:36px;
      display:flex;
      align-items:center;
      -webkit-appearance:none;
      -moz-appearance:none;
      appearance:none;
      background: var(--input-bg) url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"><polygon points="0,0 14,0 7,7" fill="%236a11cb"/></svg>') no-repeat right 10px center;
      background-size:12px;
      color: var(--white);
    }

    .footer {
      margin-top:15px;
      font-size:12px;
      color:var(--text-light);
      text-align:center;
    }

    @media (max-width:480px) {
      .container { padding:25px 18px; }
      h2 { font-size:1.5rem; }
      .footer { font-size:11px; }
    }
  </style>
</head>

<body>
    
  <div class="floating-icons-wrapper">
    <?php echo $html_icons; ?>
  </div>
  <div class="container" id="mainContainer">

    <?php if(!isset($_SESSION['user'])): ?>
      <div class="logo-container">
        <img src="kuppiimage.png" alt="Site Logo">
      </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['user'])): ?>
      <div class="profile">
        <img src="<?php echo htmlspecialchars($_SESSION['user']['profilePic']); ?>" alt="Profile Picture">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']['firstName']); ?>!</h2>
        <p>Department: <?php echo strtoupper(htmlspecialchars($_SESSION['user']['department'])); ?></p>
        <p>Reg No: <?php echo htmlspecialchars($_SESSION['user']['regNumber'] ?? 'N/A'); ?></p>

        <form method="get" action="account.php">
          <button type="submit">My Account</button>
        </form>

        <form method="post" action="logout.php">
          <button type="submit" class="logout-btn">Logout</button>
        </form>
      </div>
    <?php else: ?>
      <div id="signInBox">
        <form method="post" action="register.php">
          <h2>Sign In</h2>

          <div class="input-group">
            <i class="fa fa-envelope left-icon"></i>
            <input type="text" name="email" placeholder=" " required>
            <label>Email / Reg No (e.g: YY/ms/00)</label>
          </div>

          <div class="input-group password-input">
            <i class="fa fa-lock left-icon"></i>
            <input type="password" name="password" placeholder=" " required>
            <label>Password</label>
            <i class="fa fa-eye toggle-password"></i>
          </div>

          <button type="submit" name="signIn">Sign In</button>
          <span class="switch-link" onclick="toggleForms()">Don't have an account? Sign Up</span>
        </form>
      </div>

      <div id="signUpBox" class="hidden">
        <form method="post" action="register.php" enctype="multipart/form-data" onsubmit="return validateSignUp(event)">
          <h2>Create Account</h2>

          <div class="input-group"><i class="fa fa-user left-icon"></i>
            <input type="text" name="fName" placeholder=" " required>
            <label>First Name</label>
          </div>

          <div class="input-group"><i class="fa fa-user left-icon"></i>
            <input type="text" name="lName" placeholder=" " required>
            <label>Last Name</label>
          </div>

          <!-- Email Field with Error Text -->
          <div class="input-group"><i class="fa fa-envelope left-icon"></i>
            <input type="email" id="signupEmail" name="email" placeholder=" " required>
            <label>Email</label>
          </div>
          <span id="emailError" style="color: var(--danger); font-size: 11px; font-weight: 600; display: none; margin-top: -8px; padding-left: 5px;">Email is error please re-check </span>

          <div class="input-group"><i class="fa fa-phone left-icon"></i>
            <input type="text" name="mobile" placeholder=" " required pattern="\d{10}" maxlength="10" title="Enter exactly 10 digits">
            <label>Mobile</label>
          </div>

          <div class="input-group"><i class="fa fa-id-card left-icon"></i>
            <input type="text" id="regNum" name="regNumber" placeholder=" " required pattern="^[0-9]{2}/(ms|cs)/[0-9]+$" title="Format: YY/ms/number or YY/cs/number (e.g. 19/ms/00)">
            <label>Registration No (e.g: 19/ms/00)</label>
          </div>
            
          <div class="input-group"><i class="fa fa-calendar left-icon"></i>
             <select name="batch" required>
                <option value="">-- Select Your Batch --</option>
                <option value="20/21">20/21 Batch</option>
                <option value="21/22">21/22 Batch</option>
                <option value="22/23">22/23 Batch</option>
                <option value="23/24">23/24 Batch</option>
                <option value="24/25">24/25 Batch</option>
             </select>
          </div>

          <div class="input-group"><i class="fa fa-building left-icon"></i>
            <select name="department" id="deptSelect" required>
              <option value="">-- Select Department --</option>
              <option value="bms">BMS</option>
              <option value="lcs">LCS</option>
            </select>
          </div>

          <div class="input-group password-input">
            <i class="fa fa-lock left-icon"></i>
            <input type="password" id="signupPassword" name="password" placeholder=" " required oninput="checkStrength(this.value)">
            <label>Password</label>
            <i class="fa fa-eye toggle-password"></i>
          </div>

          <div class="strength-line" id="passwordStrengthLine"></div>
          <p id="passwordStrengthText"></p>

          <div class="input-group">
            <i class="fa fa-image left-icon"></i>
            <input type="file" name="profilePic" id="signupFile" accept=".jpg,.jpeg,.png" class="file-input" required onchange="previewImage(this)">
          </div>
          <p class="file-note" id="aiStatus" style="color: #ff9800; font-weight: bold;">Loading AI Face Scanner... Please wait.</p>
          <img id="profilePreview" src="" alt="Profile Preview" style="display:none; width:80px; height:80px; border-radius:50%; margin-top:5px; border:2px solid #444;">

          <button type="submit" name="signUp" id="signUpBtn" disabled>Sign Up</button>
          <span class="switch-link" onclick="toggleForms()">Already have an account? Sign In</span>
        </form>
      </div>
    <?php endif; ?>

    <div class="footer">Design by 22/23 FCBS</div>
  </div>

  <script>
    // Real-time Email Validation (Blur and Input events)
    document.addEventListener("DOMContentLoaded", function() {
        const signupEmail = document.getElementById('signupEmail');
        const emailError = document.getElementById('emailError');

        // Check when user leaves the field
        signupEmail.addEventListener('blur', function() {
            const emailLocalPart = this.value.split('@')[0];
            if (emailLocalPart && emailLocalPart.includes('.')) {
                emailError.style.display = 'block';
                this.style.borderColor = 'var(--danger)';
                this.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.25)';
            } else {
                emailError.style.display = 'none';
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });
        
        // Remove error when user starts typing to fix it
        signupEmail.addEventListener('input', function() {
            const emailLocalPart = this.value.split('@')[0];
            if (!emailLocalPart.includes('.')) {
                emailError.style.display = 'none';
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });
    });

    function toggleForms() {
      document.getElementById("signInBox").classList.toggle("hidden");
      document.getElementById("signUpBox").classList.toggle("hidden");
    }

    function validateSignUp(event) {
        const dept = document.getElementById('deptSelect').value.toLowerCase();
        const regNum = document.getElementById('regNum').value.toLowerCase();
        const batchSelect = document.querySelector('select[name="batch"]');
        
        // Form Submit Email Validation (Double check)
        const emailInput = document.getElementById('signupEmail').value;
        const emailLocalPart = emailInput.split('@')[0];
        
        if (emailLocalPart.includes('.')) {
            document.getElementById('emailError').style.display = 'block';
            document.getElementById('signupEmail').style.borderColor = 'var(--danger)';
            event.preventDefault();
            return false;
        }
        
        // Batch alignment validation
        if (batchSelect && batchSelect.value && regNum) {
            const batchParts = batchSelect.value.split('/');
            const regParts = regNum.split('/');
            if (batchParts.length > 0 && regParts.length > 0) {
                const batchYear = parseInt(batchParts[0]);
                const regYear = parseInt(regParts[0]);
                if (!isNaN(batchYear) && !isNaN(regYear)) {
                    if (regYear !== batchYear && regYear !== (batchYear - 1)) {
                        alert("Invalid Registration Number format.");
                        event.preventDefault();
                        return false;
                    }
                }
            }
        }
        
        if (dept === 'bms' && !regNum.includes('/ms/')) {
            alert("BMS department students must have '/ms/' in their Registration Number.");
            event.preventDefault();
            return false;
        }
        if (dept === 'lcs' && !regNum.includes('/cs/')) {
            alert("LCS department students must have '/cs/' in their Registration Number.");
            event.preventDefault();
            return false;
        }
        return true;
    }

    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', () => {
        const wrapper = icon.closest('.password-input');
        const input = wrapper.querySelector('input');
        if(input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('fa-eye','fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.replace('fa-eye-slash','fa-eye');
        }
      });
    });

    function checkStrength(password) {
      const text = document.getElementById('passwordStrengthText');
      const line = document.getElementById('passwordStrengthLine');
      const lengthCheck = password.length >= 8;
      const upperCheck = /[A-Z]/.test(password);
      const numberCheck = /[0-9]/.test(password);
      const specialCheck = /[!@#$%^&*(),.?":{}|<>]/.test(password);
      let score = [lengthCheck, upperCheck, numberCheck, specialCheck].filter(Boolean).length;

      if(password.length === 0){
        text.textContent = '';
        line.style.background = '#333';
      } else if(score <= 1){
        text.textContent = 'Weak';
        text.style.color = 'red';
        line.style.background = 'red';
      } else if(score == 2 || score == 3){
        text.textContent = 'Medium';
        text.style.color = 'orange';
        line.style.background = 'orange';
      } else {
        text.textContent = 'Strong';
        text.style.color = 'lime';
        line.style.background = 'lime';
      }
    }

    function previewImage(input) {
      const preview = document.getElementById('profilePreview');
      if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = function(e){
          preview.src = e.target.result;
          preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
      } else {
        preview.src = '';
        preview.style.display = 'none';
      }
    }

    // ==========================================
    // AI FACE DETECTION LOGIC FOR SIGN UP
    // ==========================================
    
    function initFaceDetection() {
        if (typeof faceapi !== 'undefined') {
            const signUpFile = document.getElementById('signupFile');
            const signUpBtn = document.getElementById('signUpBtn');
            const aiStatus = document.getElementById('aiStatus');

            faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/')
            .then(() => {
                aiStatus.innerText = '✅ AI Scanner Ready. Please upload your photo.';
                aiStatus.style.color = '#00ff88';
            }).catch(err => {
                aiStatus.innerText = '❌ Error loading AI model.';
                aiStatus.style.color = 'red';
            });

            signUpFile.addEventListener('change', async () => {
                if (!signUpFile.files[0]) return;
                signUpBtn.disabled = true;
                aiStatus.innerText = 'Scanning photo... ⏳';
                aiStatus.style.color = '#ff9800';

                try {
                    const image = await faceapi.bufferToImage(signUpFile.files[0]);
                    const detection = await faceapi.detectSingleFace(image, new faceapi.TinyFaceDetectorOptions());

                    if (detection) {
                        aiStatus.innerText = '✅ Human Face Detected! You can sign up.';
                        aiStatus.style.color = '#00ff88';
                        signUpBtn.disabled = false;
                    } else {
                        aiStatus.innerText = '❌ No face detected! Please upload a real human photo.';
                        aiStatus.style.color = 'red';
                        signUpFile.value = ''; 
                        document.getElementById('profilePreview').style.display = 'none';
                    }
                } catch (e) {
                    aiStatus.innerText = '❌ Error during scan.';
                    aiStatus.style.color = 'red';
                }
            });
        } else {
            setTimeout(initFaceDetection, 500);
        }
    }

    window.addEventListener('load', initFaceDetection);
  </script>
<a href="https://wa.me/94764781212" class="support-float" target="_blank">
    <i class="fas fa-headset"></i> <span>Contact Support</span>
</a>

<style>
.support-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background-color: #6a1b9a; 
    color: white;
    padding: 15px 25px;
    border-radius: 50px;
    text-decoration: none;
    font-family: 'Segoe UI', sans-serif;
    font-weight: 600;
    box-shadow: 0 8px 20px rgba(106, 27, 154, 0.4); 
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    animation: float-animation 3s ease-in-out infinite;
}

.support-float:hover {
    background-color: #4a148c; 
    transform: scale(1.1);
    box-shadow: 0 10px 25px rgba(106, 27, 154, 0.5);
}

@keyframes float-animation {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-12px); }
    100% { transform: translateY(0px); }
}
</style>
</body>
</html>