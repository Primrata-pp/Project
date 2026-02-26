<!DOCTYPE html>
<html>
<head>
    <title>Login RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card p-4 shadow">
        <div class="text-center mb-3">
            <img src="assets/Logoo.png" alt="Logo" class="rounded-circle" width="120" height="120">
        </div>
        <h3 class="text-center">HAPPY กะเพรา&คาเฟ่</h3>

        <form action="controllers/AuthController.php" method="POST" id="loginForm" autocomplete="off">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3 text-end">
                <a href="reset_password.php" style="font-size:0.95em;">ลืมรหัสผ่าน?</a>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">
                Login
            </button>
        </form>
    </div>
</div>


<script>
// ป้องกันการ submit แบบ GET
document.getElementById('loginForm').addEventListener('submit', function(e) {
    this.method = 'POST';
});
</script>
</body>
</html>
