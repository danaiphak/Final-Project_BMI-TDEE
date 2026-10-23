<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลและการจัดการ Session
require_once "db.php";

// ประกาศตัวแปรสำหรับเก็บข้อความแจ้งเตือน (ข้อผิดพลาด / ข้อความสำเร็จ)
$error = "";
$success = "";

// ----------------------------------------------------------------------
// 1. ตรวจสอบสถานะการเข้าสู่ระบบ
// ----------------------------------------------------------------------
if (isset($_SESSION["user_id"])) {
    header("Location: profile.php");
    exit;
}

// ----------------------------------------------------------------------
// 2. ประมวลผลเมื่อมีการส่งฟอร์มสมัครสมาชิก (POST Request)
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // ตรวจสอบความถูกต้องของ CSRF Token
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง (CSRF Token mismatch)";
    } else {
        // รับค่าและตัดช่องว่างหน้า-หลังสำหรับข้อมูลทั้งหมด
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone_number = trim($_POST["phone_number"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";
        $role = "user"; // บังคับสิทธิ์เริ่มต้นเป็น user เสมอเพื่อความปลอดภัย

        // Validation 1: ตรวจสอบว่ากรอกข้อมูลครบทุกช่องหรือไม่
        if ($username === "" || $email === "" || $phone_number === "" || $password === "" || $confirm_password === "") {
            $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
            
        // Validation 2: ตรวจสอบรูปแบบอีเมล
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";

        // Validation 3: ตรวจสอบรูปแบบหมายเลขโทรศัพท์ไทย (ขึ้นต้นด้วย 0 และมี 10 หลัก)
        } elseif (!preg_match("/^0[0-9]{9}$/", $phone_number)) {
            $error = "หมายเลขโทรศัพท์ต้องขึ้นต้นด้วยเลข 0 และมี 10 หลัก (เช่น 0812345678)";
            
        // Validation 4: ตรวจสอบความยาวรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)
        } elseif (strlen($password) < 8) {
            $error = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
            
        // Validation 5: ตรวจสอบการยืนยันรหัสผ่านว่าตรงกันหรือไม่
        } elseif ($password !== $confirm_password) {
            $error = "รหัสผ่านไม่ตรงกัน";
        } else {
            // ค้นหาอีเมล หรือ เบอร์โทรศัพท์ ซ้ำในตาราง user
            $stmt = $conn->prepare("SELECT user_id, user_email, phone_number FROM user WHERE user_email = ? OR phone_number = ? LIMIT 1");

            if ($stmt) {
                $stmt->bind_param("ss", $email, $phone_number);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    if ($row['user_email'] === $email) {
                        $error = "อีเมลนี้มีผู้ใช้งานแล้ว";
                    } else {
                        $error = "หมายเลขโทรศัพท์นี้มีผู้ใช้งานแล้ว";
                    }
                } else {
                    // แฮชรหัสผ่าน
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // เตรียมคำสั่ง INSERT บันทึกข้อมูลรวมถึง phone_number
                    $insert = $conn->prepare(
                        "INSERT INTO user (user_name, user_email, phone_number, user_password, user_role) VALUES (?, ?, ?, ?, ?)"
                    );

                    if ($insert) {
                        $insert->bind_param("sssss", $username, $email, $phone_number, $hashed_password, $role);

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
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <!-- ช่องกรอกชื่อผู้ใช้ -->
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้</label>
                        <input
                            type="tel"
                            id="username"
                            name="username"
                            maxlength="100"
                            required
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <!-- ช่องกรอกอีเมล -->
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

                    <!-- ช่องกรอกหมายเลขโทรศัพท์ (รองรับเบอร์ไทย ขึ้นต้นด้วย 0 และมี 10 หลัก) -->
                    <div class="form-group">
                        <label for="phone_number">หมายเลขโทรศัพท์</label>
                        <input
                            type="tel"
                            id="phone_number"
                            name="phone_number"
                            maxlength="10"
                            pattern="^0[0-9]{9}$"
                            title="กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (ขึ้นต้นด้วย 0 และมี 10 หลัก)"
                            required
                            autocomplete="tel"
                            placeholder="0812345678"
                            value="<?= htmlspecialchars($_POST["phone_number"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <!-- ช่องกรอกรหัสผ่าน -->
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

                    <!-- ช่องกรอกยืนยันรหัสผ่าน -->
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
