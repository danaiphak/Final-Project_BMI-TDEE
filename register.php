<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลและการจัดการ Session
require_once "db.php";

// ประกาศตัวแปรสำหรับเก็บข้อความแจ้งเตือน (ข้อผิดพลาด / ข้อความสำเร็จ)
$error = "";
$success = "";

// ----------------------------------------------------------------------
// 1. ตรวจสอบสถานะการเข้าสู่ระบบ
// ----------------------------------------------------------------------
// หากผู้ใช้ล็อกอินอยู่แล้ว ($_SESSION["user_id"] มีค่า)
// ให้ส่งผู้ใช้ไปยังหน้าโปรไฟล์ส่วนตัวทันที ไม่ต้องแสดงฟอร์มสมัครสมาชิก
if (isset($_SESSION["user_id"])) {
    header("Location: profile.php");
    exit;
}

// ----------------------------------------------------------------------
// 2. ประมวลผลเมื่อมีการส่งฟอร์มสมัครสมาชิก (POST Request)
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // ตรวจสอบความถูกต้องของ CSRF Token เพื่อป้องกันการส่งฟอร์มข้ามไซต์ที่ไม่ปลอดภัย
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง (CSRF Token mismatch)";
    } else {
        // รับค่าและตัดช่องว่างหน้า-หลังสำหรับชื่อผู้ใช้และอีเมล
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";
        $role = "user"; // บังคับสิทธิ์เริ่มต้นเป็น user เสมอเพื่อความปลอดภัย

        // Validation 1: ตรวจสอบว่ากรอกข้อมูลครบทุกช่องหรือไม่
        if ($username === "" || $email === "" || $password === "" || $confirm_password === "") {
            $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
            
        // Validation 2: ตรวจสอบรูปแบบอีเมลว่าถูกต้องตามมาตรฐานหรือไม่
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";
            
        // Validation 3: ตรวจสอบความยาวรหัสผ่าน (ต้องไม่ต่ำกว่า 8 ตัวอักษร)
        } elseif (strlen($password) < 8) {
            $error = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
            
        // Validation 4: ตรวจสอบการยืนยันรหัสผ่านว่าตรงกันหรือไม่
        } elseif ($password !== $confirm_password) {
            $error = "รหัสผ่านไม่ตรงกัน";
        } else {
            // ค้นหาอีเมลซ้ำในตาราง user ด้วย Prepared Statement
            $stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = ? LIMIT 1");

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                // หากพบว่ามีอีเมลนี้อยู่ในฐานข้อมูลแล้ว
                if ($result->num_rows > 0) {
                    $error = "อีเมลนี้มีผู้ใช้งานแล้ว";
                } else {
                    // แฮชรหัสผ่านด้วยอัลกอริทึมมาตรฐานเพื่อความปลอดภัย
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // เตรียมคำสั่ง INSERT เพื่อเพิ่มสมาชิกใหม่ลงในตาราง user
                    $insert = $conn->prepare(
                        "INSERT INTO user (user_name, user_email, user_password, user_role) VALUES (?, ?, ?, ?)"
                    );

                    if ($insert) {
                        // ผูกตัวแปร 4 ตัวเข้ากับคำสั่ง SQL ("ssss" = string ทั้งหมด)
                        $insert->bind_param("ssss", $username, $email, $hashed_password, $role);

                        // สั่งประมวลผลการบันทึกข้อมูล
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
                <a href="login.php">เข้าสู่ระบบ</a>
            </div>
        </nav>
    </header>

    <!-- ------------------------------------------------------------------
         4. ส่วนเนื้อหาหลัก (Form Container)
         ------------------------------------------------------------------ -->
    <main>
        <section class="form-container">
            <h1>สมัครสมาชิก</h1>

            <!-- แสดงกล่องแจ้งเตือนเมื่อเกิดข้อผิดพลาดในการสมัครสมาชิก -->
            <?php if ($error !== ""): ?>
                <p class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <!-- แสดงข้อความเมื่อสมัครสมาชิกสำเร็จ พร้อมลิงก์ไปหน้าเข้าสู่ระบบ -->
            <?php if ($success !== ""): ?>
                <p class="success-message">
                    <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
                    <a href="login.php">เข้าสู่ระบบ</a>
                </p>
            <?php else: ?>
                <!-- ฟอร์มสมัครสมาชิก -->
                <form action="register.php" method="POST">
                    <!-- CSRF Token เพื่อความปลอดภัย -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- ช่องกรอกชื่อผู้ใช้ -->
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

    <!-- ------------------------------------------------------------------
         5. ส่วนท้ายกระดาษ (Footer)
         ------------------------------------------------------------------ -->
    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>