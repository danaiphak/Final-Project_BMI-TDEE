<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลและตั้งค่า Session
require_once "db.php";

// ----------------------------------------------------------------------
// 1. ประกาศตัวแปรเริ่มต้น
// ----------------------------------------------------------------------
$bmi = null;
$bmi_category = "";
$bmr = null;
$tdee = null;
$error = "";
$save_success = false;

// ตัวแปรสำหรับเก็บค่าเริ่มต้นเพื่อแสดงในช่องอินพุตของฟอร์ม
$default_weight = "";
$default_height = "";
$default_age = "";
$default_gender = "";
$default_activity = "";

// ----------------------------------------------------------------------
// 2. ดึงข้อมูลส่วนตัวของผู้ใช้ล่าสุดมากรอกลงฟอร์มอัตโนมัติ (กรณีล็อกอินอยู่)
// ----------------------------------------------------------------------
if (isset($_SESSION["user_id"])) {
    // เตรียมคำสั่ง SQL เพื่อดึงค่าน้ำหนัก ส่วนสูง อายุ เพศ และระดับกิจกรรมของผู้ใช้
    $stmt = $conn->prepare("SELECT user_weight, user_height, user_age, user_gender, user_activity_level FROM user WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $user_row = $stmt->get_result()->fetch_assoc();
        
        // นำค่าจากฐานข้อมูลมาตั้งเป็นค่าเริ่มต้นในฟอร์ม
        if ($user_row) {
            $default_weight = $user_row["user_weight"] ?? "";
            $default_height = $user_row["user_height"] ?? "";
            $default_age = $user_row["user_age"] ?? "";
            $default_gender = $user_row["user_gender"] ?? "";
            $default_activity = $user_row["user_activity_level"] ?? "";
        }
        $stmt->close();
    }
}

// ----------------------------------------------------------------------
// 3. ประมวลผลคำนวณและบันทึกข้อมูลเมื่อส่งฟอร์ม (POST Request)
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // ตรวจสอบความถูกต้องของ CSRF Token เพื่อป้องกันการส่งฟอร์มข้ามไซต์ที่ไม่ปลอดภัย
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง กรุณารองใหม่อีกครั้ง";
    } else {
        // กรองและตรวจสอบชนิดข้อมูลที่ส่งมาจากฟอร์ม
        $weight = filter_input(INPUT_POST, "weight", FILTER_VALIDATE_FLOAT);
        $height = filter_input(INPUT_POST, "height", FILTER_VALIDATE_FLOAT);
        $age = filter_input(INPUT_POST, "age", FILTER_VALIDATE_INT);
        $gender = $_POST["gender"] ?? "";
        $activity = filter_input(INPUT_POST, "activity", FILTER_VALIDATE_FLOAT);

        // นำค่าที่ผู้ใช้เพิ่งกรอกส่งมา ค้างไว้ในฟอร์มเพื่อไม่ให้ข้อมูลหายเมื่อหน้ารีเฟรช
        $default_weight = $_POST["weight"] ?? "";
        $default_height = $_POST["height"] ?? "";
        $default_age = $_POST["age"] ?? "";
        $default_gender = $gender;
        $default_activity = (string)$activity;

        // Validation 1: ตรวจสอบว่ากรอกข้อมูลครบถ้วนและรูปแบบถูกต้องหรือไม่
        if (
            $weight === false || $weight === null ||
            $height === false || $height === null ||
            $age === false || $age === null ||
            !in_array($gender, ["male", "female"], true) ||
            $activity === false || $activity === null
        ) {
            $error = "กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง";
            
        // Validation 2: ตรวจสอบช่วงตัวเลขว่าสมเหตุสมผลหรือไม่
        } elseif (
            $weight <= 0 || $weight > 500 ||
            $height <= 0 || $height > 300 ||
            $age < 1 || $age > 120 ||
            !in_array($activity, [1.2, 1.375, 1.55, 1.725, 1.9], true)
        ) {
            $error = "ข้อมูลอยู่นอกช่วงที่ระบบกำหนด";
        } else {
            
            // --- ขั้นตอนการคำนวณทางคณิตศาสตร์ ---
            
            // 1. คำนวณ BMI: น้ำหนัก (กก.) / [ส่วนสูง (ม.)]^2
            $height_m = $height / 100;
            $bmi = $weight / ($height_m * $height_m);

            // แปลผลระดับเกณฑ์ BMI ตามมาตรฐาน
            if ($bmi < 18.5) {
                $bmi_category = "น้ำหนักน้อยกว่าเกณฑ์";
            } elseif ($bmi < 23) {
                $bmi_category = "น้ำหนักปกติ";
            } elseif ($bmi < 25) {
                $bmi_category = "น้ำหนักเกิน";
            } elseif ($bmi < 30) {
                $bmi_category = "โรคอ้วนระดับ 1";
            } else {
                $bmi_category = "โรคอ้วนระดับ 2";
            }

            // 2. คำนวณ BMR (Mifflin-St Jeor Equation) แยกตามเพศ
            if ($gender === "male") {
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
            } else {
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
            }

            // 3. คำนวณ TDEE: BMR x ตัวคูณระดับกิจกรรม
            $tdee = $bmr * $activity;

            // --- อัปเดตผลการคำนวณลงฐานข้อมูล (กรณีผู้ใช้ล็อกอินอยู่) ---
            if (isset($_SESSION["user_id"])) {
                $user_id = $_SESSION["user_id"];
                
                $update_stmt = $conn->prepare("UPDATE user SET user_gender = ?, user_age = ?, user_weight = ?, user_height = ?, user_activity_level = ?, user_bmi = ?, user_bmr = ?, user_tdee = ?, user_bmi_category = ? WHERE user_id = ?");
                
                if ($update_stmt) {
                    $update_stmt->bind_param(
                        "siddddddsi",
                        $gender,
                        $age,
                        $weight,
                        $height,
                        $activity,
                        $bmi,
                        $bmr,
                        $tdee,
                        $bmi_category,
                        $user_id
                    );
                    
                    // สั่งบันทึกข้อมูล
                    if ($update_stmt->execute()) {
                        $save_success = true;
                    }
                    $update_stmt->close();
                }
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
    <title>คำนวณ BMI & TDEE</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <!-- แถบเมนูด้านบน (Navigation Bar) -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">BMI & TDEE</a>
            <div class="nav-links">
                <a href="index.php">หน้าหลัก</a>
                <!-- แสดงเมนูต่างกันตามสถานะการเข้าสู่ระบบ -->
                <?php if (isset($_SESSION["user_id"])): ?>
                    <a href="profile.php">โปรไฟล์</a>
                    <a href="logout.php">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php">เข้าสู่ระบบ</a>
                    <a href="register.php">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- ส่วนเนื้อหาหลัก -->
    <main>
        <section class="form-container">
            <h1>คำนวณ BMI & TDEE</h1>

            <!-- แสดงกล่องข้อผิดพลาดเมื่อกรอกข้อมูลไม่ถูกต้อง/ไม่ครบ -->
            <?php if ($error !== ""): ?>
                <p class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <!-- แสดงกล่องแจ้งเตือนการบันทึกข้อมูลสำเร็จ -->
            <?php if ($save_success): ?>
                <p class="success-message">
                    บันทึกข้อมูลเข้าโปรไฟล์เรียบร้อยแล้ว <a href="profile.php">ดูข้อมูลในโปรไฟล์</a>
                </p>
            <?php endif; ?>

            <!-- ฟอร์มกรอกข้อมูลการคำนวณ -->
            <form action="calculate.php" method="POST">
                <!-- CSRF Token เพื่อความปลอดภัย -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <!-- ช่องกรอกน้ำหนัก -->
                <div class="form-group">
                    <label for="weight">น้ำหนัก (กิโลกรัม)</label>
                    <input
                        type="number"
                        id="weight"
                        name="weight"
                        min="0.1"
                        max="500"
                        step="0.1"
                        value="<?= htmlspecialchars((string)$default_weight, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <!-- ช่องกรอกส่วนสูง -->
                <div class="form-group">
                    <label for="height">ส่วนสูง (เซนติเมตร)</label>
                    <input
                        type="number"
                        id="height"
                        name="height"
                        min="1"
                        max="300"
                        step="0.1"
                        value="<?= htmlspecialchars((string)$default_height, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <!-- ช่องกรอกอายุ -->
                <div class="form-group">
                    <label for="age">อายุ (ปี)</label>
                    <input
                        type="number"
                        id="age"
                        name="age"
                        min="1"
                        max="120"
                        value="<?= htmlspecialchars((string)$default_age, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <!-- ช่องเลือกเพศ -->
                <div class="form-group">
                    <label for="gender">เพศ</label>
                    <select id="gender" name="gender" required>
                        <option value="">เลือกเพศ</option>
                        <option value="male" <?= $default_gender === "male" ? "selected" : "" ?>>ชาย</option>
                        <option value="female" <?= $default_gender === "female" ? "selected" : "" ?>>หญิง</option>
                    </select>
                </div>

                <!-- ช่องเลือกระดับกิจกรรม -->
                <div class="form-group">
                    <label for="activity">ระดับกิจกรรม</label>
                    <select id="activity" name="activity" required>
                        <option value="">เลือกระดับกิจกรรม</option>
                        <option value="1.2" <?= (string)$default_activity === "1.2" ? "selected" : "" ?>>นั่งทำงาน ไม่ค่อยออกกำลังกาย</option>
                        <option value="1.375" <?= (string)$default_activity === "1.375" ? "selected" : "" ?>>ออกกำลังกายเบา ๆ 1–3 วัน/สัปดาห์</option>
                        <option value="1.55" <?= (string)$default_activity === "1.55" ? "selected" : "" ?>>ออกกำลังกายปานกลาง 3–5 วัน/สัปดาห์</option>
                        <option value="1.725" <?= (string)$default_activity === "1.725" ? "selected" : "" ?>>ออกกำลังกายหนัก 6–7 วัน/สัปดาห์</option>
                        <option value="1.9" <?= (string)$default_activity === "1.9" ? "selected" : "" ?>>ออกกำลังกายหนักมากหรือทำงานใช้แรงมาก</option>
                    </select>
                </div>

                <button type="submit" class="btn">คำนวณ</button>
            </form>

            <!-- แสดงส่วนสรุปผลการคำนวณ (เมื่อคำนวณสำเร็จ) -->
            <?php if ($bmi !== null): ?>
                <section class="result-container">
                    <h2>ผลการคำนวณ</h2>
                    <p>BMI: <strong><?= number_format($bmi, 2) ?></strong></p>
                    <p>ระดับ BMI: <strong><?= htmlspecialchars($bmi_category, ENT_QUOTES, "UTF-8") ?></strong></p>
                    <p>BMR: <strong><?= number_format($bmr, 2) ?></strong> kcal/วัน</p>
                    <p>TDEE: <strong><?= number_format($tdee, 2) ?></strong> kcal/วัน</p>
                </section>
            <?php endif; ?>

            <p><a href="index.php">กลับหน้าหลัก</a></p>
        </section>
    </main>

    <!-- ส่วนท้ายกระดาษ (Footer) -->
    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>