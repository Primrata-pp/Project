
<?php
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
	require_once $composerAutoload;
} else {
	die('ไม่พบไฟล์ autoload ของ Composer กรุณาติดตั้ง dependencies ให้ครบ');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mailer_config = require 'config/mailer_config.php';
require_once 'config/database.php';
require_once 'models/User.php';
$db = new Database();
$conn = $db->connect();
$userModel = new User($conn);

$msg = '';
if(isset($_POST['request_reset'])){
	$email = trim($_POST['email']);
	// ตรวจสอบอีเมลในระบบ
	$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		$user = $result->fetch_assoc();
		$user_id = $user['id'];
		// สร้าง token
		$token = bin2hex(random_bytes(32));
		$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
		// บันทึก token ลงฐานข้อมูล (สร้างตาราง password_resets ถ้ายังไม่มี)
		$conn->query("CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, email VARCHAR(255), token VARCHAR(64), expires_at DATETIME, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
		// ลบ token เก่าของ user นี้
		$conn->query("DELETE FROM password_resets WHERE user_id = $user_id");
		// เพิ่ม token ใหม่
		$stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
		$stmt2->bind_param("isss", $user_id, $email, $token, $expires);
		$stmt2->execute();
		// ส่งอีเมล
		$reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
		$subject = "Password Reset Request";
		$message = "<p>คุณได้ขอรีเซ็ตรหัสผ่าน กรุณาคลิกลิงก์นี้เพื่อเปลี่ยนรหัสผ่านใหม่:<br><a href='$reset_link'>$reset_link</a></p>";
		$mail = new PHPMailer(true);
		try {
			$mail->isSMTP();
			$mail->Host = $mailer_config['host'];
			$mail->SMTPAuth = true;
			$mail->Username = $mailer_config['username'];
			$mail->Password = $mailer_config['password'];
			$mail->SMTPSecure = $mailer_config['secure'];
			$mail->Port = $mailer_config['port'];
			$mail->CharSet = 'UTF-8';
			$mail->setFrom($mailer_config['from_email'], $mailer_config['from_name']);
			$mail->addAddress($email);
			$mail->Subject = $subject;
			$mail->Body = $message;
			$mail->isHTML(true);
			$mail->send();
			$msg = '<div class="alert alert-success">ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณแล้ว</div>';
		} catch (Exception $e) {
			$msg = '<div class="alert alert-danger">ไม่สามารถส่งอีเมลได้ กรุณาติดต่อผู้ดูแลระบบ<br>' . $mail->ErrorInfo . '</div>';
		}
	}else{
		$msg = '<div class="alert alert-danger">ไม่พบอีเมลนี้ในระบบ</div>';
	}
}

// กรณีคลิกลิงก์รีเซ็ต (มี token)
if(isset($_GET['token'])){
	$token = $_GET['token'];
	$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token=? AND expires_at > NOW()");
	$stmt->bind_param("s", $token);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		$reset = $result->fetch_assoc();
		// ถ้ามีการ submit รหัสผ่านใหม่
		if(isset($_POST['reset_password'])){
			$new_password = $_POST['new_password'];
			$confirm_password = $_POST['confirm_password'];
			if($new_password === $confirm_password && strlen($new_password) >= 4){
				$hashed = password_hash($new_password, PASSWORD_DEFAULT);
				$stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
				$stmt2->bind_param("si", $hashed, $reset['user_id']);
				$stmt2->execute();
				// ลบ token ทิ้ง
				$conn->query("DELETE FROM password_resets WHERE user_id = " . $reset['user_id']);
				$msg = '<div class="alert alert-success">เปลี่ยนรหัสผ่านสำเร็จแล้ว คุณสามารถเข้าสู่ระบบด้วยรหัสผ่านใหม่</div>';
			}else{
				$msg = '<div class="alert alert-danger">รหัสผ่านไม่ตรงกัน หรือสั้นเกินไป (อย่างน้อย 4 ตัวอักษร)</div>';
			}
		}
		// แสดงฟอร์มเปลี่ยนรหัสผ่าน
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<title>ตั้งรหัสผ่านใหม่</title>
			<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
		</head>
		<body class="bg-light">
		<div class="container mt-5">
			<div class="card p-4 shadow">
				<h3 class="mb-3">ตั้งรหัสผ่านใหม่</h3>
				<?php if(!empty($msg)) echo $msg; ?>
				<form method="POST">
					<div class="mb-3">
						<label>รหัสผ่านใหม่</label>
						<input type="password" name="new_password" class="form-control" required minlength="4">
					</div>
					<div class="mb-3">
						<label>ยืนยันรหัสผ่านใหม่</label>
						<input type="password" name="confirm_password" class="form-control" required minlength="4">
					</div>
					<button type="submit" name="reset_password" class="btn btn-success w-100">เปลี่ยนรหัสผ่าน</button>
				</form>
			</div>
		</div>
		</body>
		</html>
		<?php exit; }
	}else{
		$msg = '<div class="alert alert-danger">ลิงก์นี้หมดอายุหรือไม่ถูกต้อง</div>';
	}

// แสดงฟอร์มขอรีเซ็ตรหัสผ่าน (ถ้าไม่ได้อยู่ในขั้นตอน reset ด้วย token)
if(!isset($_GET['token'])) {
?>
<!DOCTYPE html>
<html>
<head>
	<title>Reset Password</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
	<div class="card p-4 shadow">
		<h3 class="mb-3">ขอรีเซ็ตรหัสผ่าน</h3>
		<?php if(!empty($msg)) echo $msg; ?>
		<form method="POST">
			<div class="mb-3">
				<label>อีเมลที่ลงทะเบียน</label>
				<input type="email" name="email" class="form-control" required>
			</div>
			<button type="submit" name="request_reset" class="btn btn-primary w-100">ส่งลิงก์รีเซ็ตรหัสผ่าน</button>
		</form>
	</div>
</div>
</body>
</html>
<?php }
