<?php
// 1. Force error reporting at the VERY top to debug the 500 error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'connect.php';

// Set timezone here since .htaccess was blocking it
date_default_timezone_set('Asia/Colombo');

$adminEmail = 'fcbsofficial2223@gmail.com';
$adminPassword = 'thanush21@12@2003#';

function showAlertPage($message, $type='success', $redirect='index.php', $delay=2500){
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Message</title>';
    echo '<style>
    .custom-alert {position: fixed; top:50%; left:50%; transform: translate(-50%,-50%) scale(0.9); 
      background: #007bff; color:#fff; padding:20px 35px; font-size:16px; border-radius:10px; 
      box-shadow:0 6px 20px rgba(0,0,0,0.3); z-index:9999; text-align:center; min-width:250px; 
      max-width:90%; opacity:0; transition: all 0.5s ease;}
    .custom-alert.show {opacity:1; transform: translate(-50%,-50%) scale(1);}
    .custom-alert.success {background:#28a745;} .custom-alert.error{background:#dc3545;}
    </style></head><body>';
    echo '<div id="customAlert" class="custom-alert '.$type.'">'.$message.'</div>';
    echo '<script>
    const alertBox = document.getElementById("customAlert");
    alertBox.classList.add("show");
    setTimeout(()=>{ window.location="'.$redirect.'"; }, '.$delay.');
    </script></body></html>';
    exit;
}

// ===== SIGN UP =====
if(isset($_POST['signUp'])){
    $fName = trim($_POST['fName']);
    $lName = trim($_POST['lName']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $department = trim($_POST['department']);
    $batch = trim($_POST['batch']); 
    $regNumber = strtolower(trim($_POST['regNumber'])); 
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Batch alignment validation
    if (!empty($batch) && !empty($regNumber)) {
        if (!preg_match('/^\d{2}\/(ms|cs)\/\d+$/', $regNumber)) {
            showAlertPage("❌ Invalid Registration Number format.", "error");
        }
        $batchParts = explode('/', $batch);
        $regParts = explode('/', $regNumber);
        $batchYear = (int)$batchParts[0];
        $regYear = (int)$regParts[0];
        if ($regYear !== $batchYear && $regYear !== ($batchYear - 1)) {
            showAlertPage("❌ Invalid Registration Number format.", "error");
        }
    }

    // Check email, mobile, and regNumber uniqueness
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email=? OR mobile=? OR regNumber=?");
    if(!$checkStmt){ showAlertPage("❌ DB Error: ".$conn->error,"error"); }
    $checkStmt->bind_param("sss", $email, $mobile, $regNumber);
    $checkStmt->execute();
    $checkStmt->store_result();
    if($checkStmt->num_rows > 0){ 
        showAlertPage("❌ Error: Email, Mobile Number, or Registration Number is already in use by another account.","error"); 
    }
    $checkStmt->close();

    // Profile Pic
    $profilePic = '';
    if(isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK){
        $allowedExt = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION));
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['profilePic']['tmp_name']);
        finfo_close($finfo);

        if(!in_array($ext,$allowedExt) || !in_array($mime,['image/jpeg','image/png'])){
            showAlertPage("❌ Only JPG/PNG allowed","error");
        }
        if($_FILES['profilePic']['size'] > 3*1024*1024){
            showAlertPage("❌ File too large (max 3MB)","error");
        }
        
        if(!is_dir('uploads')){ mkdir('uploads',0755,true); } 
        $profilePic = 'uploads/'.uniqid('profile_',true).'.'.$ext;
        move_uploaded_file($_FILES['profilePic']['tmp_name'], $profilePic);
    } else {
        showAlertPage("❌ Please upload a profile picture","error");
    }

    // UPDATED: Added is_face_verified and set it to 1
    $stmt = $conn->prepare("INSERT INTO users (firstName,lastName,email,mobile,department,batch,password,profilePic,regNumber,is_face_verified) VALUES (?,?,?,?,?,?,?,?,?,1)");
    if(!$stmt){ showAlertPage("❌ DB Error: ".$conn->error,"error"); }
    $stmt->bind_param("sssssssss", $fName, $lName, $email, $mobile, $department, $batch, $password, $profilePic, $regNumber);
    
    if($stmt->execute()){
        showAlertPage("✅ Registration successful! Please log in.","success","index.php",2500);
    } else {
        showAlertPage("❌ Registration failed: ".$stmt->error,"error");
    }
    $stmt->close();
}

// ===== SIGN IN =====
if(isset($_POST['signIn'])){
    $loginInput = trim($_POST['email']);
    $password = $_POST['password'];

    // Admin login
    if($loginInput === $adminEmail && $password === $adminPassword){
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        showAlertPage("✅ Admin login successful!","success","admin.php",2000);
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE (email=? OR regNumber=?) LIMIT 1");
    if(!$stmt){ showAlertPage("❌ DB Error: ".$conn->error,"error"); }
    $stmt->bind_param("ss", $loginInput, $loginInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result && $result->num_rows === 1){
        $user = $result->fetch_assoc();
        if(password_verify($password,$user['password'])){
            session_regenerate_id(true);
            $_SESSION['user'] = $user;

            if($conn->query("SHOW COLUMNS FROM users LIKE 'last_login'")->num_rows){
                $updateStmt = $conn->prepare("UPDATE users SET last_login=NOW() WHERE id=?");
                if($updateStmt){
                    $updateStmt->bind_param("i",$user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }

            showAlertPage("✅ Login successful! Welcome ".htmlspecialchars($user['firstName']),"success","account.php",2000);
        } else {
            showAlertPage("❌ Invalid email or password","error");
        }
    } else {
        showAlertPage("❌ Invalid email or password","error");
    }
    $stmt->close();
}

$conn->close();
?>