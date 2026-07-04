<?php
session_start();
include 'connect.php'; 

if(!isset($_SESSION['user'])){
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];

// Redirect user back to their respective department dashboard after processing
$redirectUrl = (strtolower($user['department']) === 'bms') ? 'bms/index.php' : 'lcs/index.php';

// 1. FACE VERIFICATION UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_photo'])) {
    $error = '';
    if(isset($_FILES['newProfilePic']) && $_FILES['newProfilePic']['error'] === UPLOAD_ERR_OK){
        $ext = strtolower(pathinfo($_FILES['newProfilePic']['name'], PATHINFO_EXTENSION));
        if(in_array($ext,['jpg','jpeg','png']) && $_FILES['newProfilePic']['size'] <= 3*1024*1024){
            if(!is_dir('uploads')) mkdir('uploads',0777,true);
            $profilePic = 'uploads/'.uniqid('profile_',true).'.'.$ext;
            move_uploaded_file($_FILES['newProfilePic']['tmp_name'], $profilePic);

            $stmt = $conn->prepare("UPDATE users SET profilePic=?, is_face_verified=1 WHERE id=?");
            $stmt->bind_param("si", $profilePic, $user['id']);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user']['profilePic'] = $profilePic;
            $_SESSION['user']['is_face_verified'] = 1;
            header("Location: " . $redirectUrl . "?success=verified");
            exit;
        } else { $error="Invalid image (max 3MB JPG/PNG)"; }
    } else { $error="Please select an image."; }
    
    if(!empty($error)) {
        header("Location: " . $redirectUrl . "?error=" . urlencode($error));
        exit;
    }
}

// 2. COMPLETING MISSING BATCH OR REGISTRATION NUMBER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completeProfileData'])) {
    $batch = $_POST['batchSelect'] ?? $user['batch'];
    $regNumber = isset($_POST['regNumber']) ? strtolower(trim($_POST['regNumber'])) : $user['regNumber'];
    $err = "";

    if (empty($regNumber)) {
        $err = "Registration Number is required.";
    } elseif (!preg_match('/^\d{2}\/(ms|cs)\/\d+$/', $regNumber)) {
        $err = "Invalid Registration Number format. Use format like 22/ms/00";
    } else {
        if (!empty($batch)) {
            $batchParts = explode('/', $batch);
            $regParts = explode('/', $regNumber);
            $batchYear = (int)$batchParts[0];
            $regYear = (int)$regParts[0];
            if ($regYear !== $batchYear && $regYear !== ($batchYear - 1)) {
                $err = "Invalid Registration Number format.";
            }
        }
        if (empty($err)) {
            $dept = strtolower($user['department']);
            if ($dept === 'bms' && strpos($regNumber, '/ms/') === false) {
                $err = "Registration number must contain '/ms/' for BMS department.";
            } elseif ($dept === 'lcs' && strpos($regNumber, '/cs/') === false) {
                $err = "Registration number must contain '/cs/' for LCS department.";
            } else {
                $stmt = $conn->prepare("SELECT id FROM users WHERE regNumber=? AND id!=?");
                $stmt->bind_param("si", $regNumber, $user['id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $err = "This Registration Number is already registered by another user.";
                }
                $stmt->close();
            }
        }
    }

    if (empty($err)) {
        $stmt = $conn->prepare("UPDATE users SET batch=?, regNumber=? WHERE id=?");
        $stmt->bind_param("ssi", $batch, $regNumber, $user['id']);
        $stmt->execute();
        $stmt->close();
        $_SESSION['user']['batch'] = $batch;
        $_SESSION['user']['regNumber'] = $regNumber;
        header("Location: " . $redirectUrl . "?success=details_saved");
        exit;
    } else {
        header("Location: " . $redirectUrl . "?error=" . urlencode($err));
        exit;
    }
}

// 3. PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfile'])) {
    $fName = trim($_POST['fName']);
    $lName = trim($_POST['lName']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $regNumber = strtolower(trim($_POST['regNumber']));
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $profilePic = $user['profilePic'];
    $error = "";

    if (!empty($regNumber)) {
        if (!preg_match('/^\d{2}\/(ms|cs)\/\d+$/', $regNumber)) {
            $error = "Invalid Registration Number format.";
        } else {
            if (!empty($user['batch'])) {
                $batchParts = explode('/', $user['batch']);
                $regParts = explode('/', $regNumber);
                $batchYear = (int)$batchParts[0];
                $regYear = (int)$regParts[0];
                if ($regYear !== $batchYear && $regYear !== ($batchYear - 1)) {
                    $error = "Invalid Registration Number format.";
                }
            }
            if (empty($error)) {
                $dept = strtolower($user['department']);
                if ($dept === 'bms' && strpos($regNumber, '/ms/') === false) {
                    $error = "Registration number must contain '/ms/' for BMS department.";
                } elseif ($dept === 'lcs' && strpos($regNumber, '/cs/') === false) {
                    $error = "Registration number must contain '/cs/' for LCS department.";
                }
            }
        }
    } else { $error = "Registration Number cannot be empty."; }

    if (empty($error)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE (email=? OR mobile=? OR regNumber=?) AND id!=?");
        $stmt->bind_param("sssi", $email, $mobile, $regNumber, $user['id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "This Email, Mobile Number, or Registration Number is already in use by another account.";
        }
        $stmt->close();
    }

    if(empty($error)){
        if(isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && $_FILES['profilePic']['size'] <= 3*1024*1024){
                if(!is_dir('uploads')) mkdir('uploads',0777,true);
                if($user['profilePic'] && file_exists($user['profilePic'])) unlink($user['profilePic']);
                $profilePic = 'uploads/'.uniqid('profile_',true).'.'.$ext;
                move_uploaded_file($_FILES['profilePic']['tmp_name'],$profilePic);
            } else { $error="Invalid image (max 3MB JPG/PNG)"; }
        }

        if(empty($error)){
            $sql = "UPDATE users SET firstName=?, lastName=?, email=?, mobile=?, profilePic=?, regNumber=?";
            $params = [$fName,$lName,$email,$mobile,$profilePic,$regNumber];
            $types = "ssssss";

            if(!empty($currentPassword) && !empty($newPassword)){
                if(password_verify($currentPassword,$user['password'])){
                    $sql .= ", password=?";
                    $params[] = password_hash($newPassword,PASSWORD_DEFAULT);
                    $types .= "s";
                } else { $error="Current password incorrect"; }
            }
            $sql .= " WHERE id=?";
            $params[] = $user['id'];
            $types .= "i";

            if(empty($error)){
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types,...$params);
                if($stmt->execute()){
                    $res = $conn->query("SELECT * FROM users WHERE id=".$user['id']);
                    $_SESSION['user'] = $res->fetch_assoc();
                    header("Location: " . $redirectUrl . "?success=profile_updated");
                    exit;
                } else { $error=$stmt->error; }
                $stmt->close();
            }
        }
    }
    if(!empty($error)){
        header("Location: " . $redirectUrl . "?error=" . urlencode($error));
        exit;
    }
}

// 4. POST COMMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, comment) VALUES (?, ?)");
        $stmt->bind_param("is", $user['id'], $comment);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $redirectUrl . "?success=comment_posted");
    exit;
}

// Direct access fallback
header("Location: " . $redirectUrl);
exit;