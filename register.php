<?php
require_once "db.php";

$error = "";
$success = "";

if (isset($_SESSION["user_id"])) {
    header("Location: profile.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง (CSRF Token mismatch)";
    } else {
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";
        $role = "user"; // บังคับสิทธิ์เริ่มต้นเป็น user เสมอเพื่อความปลอดภัย

        if ($username === "" || $email === "" || $password === "" || $confirm_password === "") {
            $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";
        } elseif (strlen($password) < 8) {
            $error = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
        } elseif ($password !== $confirm_password) {
            $error = "รหัสผ่านไม่ตรงกัน";
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = ? LIMIT 1");

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error = "อีเมลนี้มีผู้ใช้งานแล้ว";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $insert = $conn->prepare(
                        "INSERT INTO user (user_name, user_email, user_password, user_role) VALUES (?, ?, ?, ?)"
                    );

                    if ($insert) {
                        $insert->bind_param("ssss", $username, $email, $hashed_password, $role);

                        if ($insert->execute()) {
                            $success = "สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ";
                        } else {
                            $error = "ไม่สามารถสมัครสมาชิกได้";
                        }
                        $insert->close();
                    } else {
                        $error = "ไม่สามารถดำเนินการสมัครสมาชิกได้";
                    }
                }
                $stmt->close();
            } else {
                $error = "ไม่สามารถตรวจสอบข้อมูลได้";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | BMI & TDEE</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">BMI & TDEE</a>
            <div class="nav-links">
                <a href="index.php">หน้าหลัก</a>
                <a href="login.php">เข้าสู่ระบบ</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="form-container">
            <h1>สมัครสมาชิก</h1>

            <?php if ($error !== ""): ?>
                <p class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if ($success !== ""): ?>
                <p class="success-message">
                    <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
                    <a href="login.php">เข้าสู่ระบบ</a>
                </p>
            <?php else: ?>
                <form action="register.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            maxlength="100"
                            required
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">อีเมล</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="255"
                            required
                            autocomplete="email"
                            value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <button type="submit" class="btn">สมัครสมาชิก</button>
                </form>
            <?php endif; ?>

            <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>