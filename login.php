<?php
require_once "db.php";

$error = "";

if (isset($_SESSION["user_id"])) {
    header("Location: profile.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    } else {
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($email === "" || $password === "") {
            $error = "กรุณากรอกอีเมลและรหัสผ่าน";
        } else {
            $stmt = $conn->prepare(
                "SELECT user_id, user_name, user_email, user_password, user_role FROM user WHERE user_email = ? LIMIT 1"
            );

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user["user_password"])) {
                        session_regenerate_id(true);

                        $_SESSION["user_id"] = $user["user_id"];
                        $_SESSION["username"] = $user["user_name"];
                        $_SESSION["email"] = $user["user_email"];
                        $_SESSION["role"] = $user["user_role"];

                        header("Location: profile.php");
                        exit;
                    }
                }

                $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
                $stmt->close();
            } else {
                $error = "ไม่สามารถดำเนินการเข้าสู่ระบบได้";
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
    <title>เข้าสู่ระบบ | BMI & TDEE</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">BMI & TDEE</a>
            <div class="nav-links">
                <a href="index.php">หน้าหลัก</a>
                <a href="register.php">สมัครสมาชิก</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="form-container">
            <h1>เข้าสู่ระบบ</h1>

            <?php if ($error !== ""): ?>
                <p class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="email">อีเมล</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn">เข้าสู่ระบบ</button>
            </form>

            <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>