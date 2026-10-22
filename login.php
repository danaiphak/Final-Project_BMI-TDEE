<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลและการจัดการ Session
require_once "db.php";

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือนข้อผิดพลาด
$error = "";

// ----------------------------------------------------------------------
// 1. ตรวจสอบสถานะการเข้าสู่ระบบ
// ----------------------------------------------------------------------
// หากผู้ใช้เข้าสู่ระบบอยู่แล้ว ($_SESSION["user_id"] มีค่า)
// ให้ส่งผู้ใช้ไปยังหน้าโปรไฟล์ส่วนตัวทันที ไม่ต้องกรอกฟอร์มเข้าสู่ระบบซ้ำ
if (isset($_SESSION["user_id"])) {
    header("Location: profile.php");
    exit;
}

// ----------------------------------------------------------------------
// 2. ประมวลผลเมื่อมีการส่งฟอร์มเข้าสู่ระบบ (POST Request)
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // ตรวจสอบความถูกต้องของ CSRF Token เพื่อป้องกันการส่งฟอร์มข้ามไซต์ที่ไม่ปลอดภัย
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    } else {
        // ตัดช่องว่างหน้า-หลังอีเมล และรับค่ารหัสผ่านจากฟอร์ม
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        // Validation: ตรวจสอบว่าได้กรอกอีเมลและรหัสผ่านหรือไม่
        if ($email === "" || $password === "") {
            $error = "กรุณากรอกอีเมลและรหัสผ่าน";
        } else {
            // ค้นหาข้อมูลผู้ใช้จากตาราง user โดยค้นหาตามอีเมลด้วย Prepared Statement
            $stmt = $conn->prepare(
                "SELECT user_id, user_name, user_email, user_password, user_role FROM user WHERE user_email = ? LIMIT 1"
            );

            if ($stmt) {
                // ผูกตัวแปรอีเมลเข้ากับคำสั่ง SQL ("s" = string)
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                // หากพบข้อมูลผู้ใช้ตรงตามอีเมลที่ระบุ
                if ($user = $result->fetch_assoc()) {
                    // ตรวจสอบรหัสผ่านที่ผู้ใช้กรอก เทียบกับรหัสผ่านแฮชในฐานข้อมูล
                    if (password_verify($password, $user["user_password"])) {
                        // สุ่มรหัส Session ID ใหม่เพื่อป้องกันการตั้งค่า Session Fixation Attack
                        session_regenerate_id(true);

                        // บันทึกข้อมูลประจำตัวของผู้ใช้ลงในตัวแปร Session
                        $_SESSION["user_id"] = $user["user_id"];
                        $_SESSION["username"] = $user["user_name"];
                        $_SESSION["email"] = $user["user_email"];
                        $_SESSION["role"] = $user["user_role"];

                        // ส่งผู้ใช้ไปยังหน้าโปรไฟล์ทันทีเมื่อเข้าสู่ระบบสำเร็จ
                        header("Location: profile.php");
                        exit;
                    }
                }

                // หากแมตช์อีเมลไม่เจอ หรือตรวจสอบรหัสผ่านไม่ผ่าน ให้แสดงข้อความเตือนเดียวกัน
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
    <!-- นำเข้าไฟล์สไตล์ CSS หลักสำหรับตกแต่งหน้าเว็บ -->
    <link rel="stylesheet" href="style.css">
    <!-- นำเข้าไฟล์ JavaScript สำหรับฟังก์ชันการทำงานเพิ่มเติม -->
    <script src="js/script.js" defer></script>
</head>
<body>
    <!-- ------------------------------------------------------------------
         3. ส่วนหัวของเว็บไซต์ (Navigation Bar)
         ------------------------------------------------------------------ -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">BMI & TDEE</a>
            <div class="nav-links">
                <a href="index.php">หน้าหลัก</a>
                <a href="register.php">สมัครสมาชิก</a>
            </div>
        </nav>
    </header>

    <!-- ------------------------------------------------------------------
         4. ส่วนเนื้อหาหลัก (Form Container)
         ------------------------------------------------------------------ -->
    <main>
        <section class="form-container">
            <h1>เข้าสู่ระบบ</h1>

            <!-- แสดงข้อความเตือนเมื่อเกิดข้อผิดพลาดในการเข้าสู่ระบบ -->
            <?php if ($error !== ""): ?>
                <p class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <!-- ฟอร์มส่งข้อมูลการเข้าสู่ระบบ -->
            <form action="login.php" method="POST">
                <!-- CSRF Token เพื่อความปลอดภัย -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <!-- ช่องกรอกอีเมล -->
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

                <!-- ช่องกรอกรหัสผ่าน -->
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

    <!-- ------------------------------------------------------------------
         5. ส่วนท้ายกระดาษ (Footer)
         ------------------------------------------------------------------ -->
    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>