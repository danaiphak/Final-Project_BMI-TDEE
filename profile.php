<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลและการจัดการ Session
require_once "db.php";

// ----------------------------------------------------------------------
// 1. ตรวจสอบการเข้าสู่ระบบ
// ----------------------------------------------------------------------
// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ หากยังไม่ได้ล็อกอิน ให้ส่งผู้ใช้ไปหน้า login.php ทันที
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// ดึง user_id ของผู้ใช้ปัจจุบันจาก Session
$user_id = $_SESSION["user_id"];
$user_data = null; // ตัวแปรสำหรับเก็บข้อมูลผู้ใช้งานจากฐานข้อมูล

// ----------------------------------------------------------------------
// 2. ดึงข้อมูลส่วนตัวของผู้ใช้จากฐานข้อมูล
// ----------------------------------------------------------------------
// เตรียมคำสั่ง SQL เพื่อค้นหาข้อมูลผู้ใช้เฉพาะแถวที่มี user_id ตรงกับผู้ใช้งานปัจจุบัน
$stmt = $conn->prepare("SELECT user_id, user_name, user_email, user_role, user_gender, user_age, user_weight, user_height, user_bmi, user_tdee, created_at FROM user WHERE user_id = ? LIMIT 1");

if ($stmt) {
    // ผูกตัวแปร $user_id เข้ากับเครื่องหมาย ? ("i" = integer)
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    // ดึงข้อมูลแถวที่พบมาเก็บบรรจุในอาร์เรย์ $user_data
    $user_data = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ส่วนตัว | BMI & TDEE</title>
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
                <a href="calculate.php">คำนวณ</a>
                
                <!-- เงื่อนไขพิเศษ: หากผู้ใช้มีสิทธิ์เป็น admin ให้แสดงเมนู "จัดการสมาชิก" เพิ่มขึ้นมา -->
                <?php if (($user_data["user_role"] ?? "") === "admin"): ?>
                    <a href="admin.php">จัดการสมาชิก</a>
                <?php endif; ?>
                
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </nav>
    </header>

    <!-- ------------------------------------------------------------------
         4. ส่วนเนื้อหาหลัก (Profile Card)
         ------------------------------------------------------------------ -->
    <main>
        <section class="form-container profile-card">
            <h1>ข้อมูลส่วนตัว</h1>

            <!-- ตรวจสอบว่าดึงข้อมูลผู้ใช้จากฐานข้อมูลสำเร็จหรือไม่ -->
            <?php if ($user_data): ?>
                <div class="profile-info">
                    <!-- แสดงชื่อผู้ใช้ -->
                    <div class="info-item">
                        <span class="info-label">ชื่อผู้ใช้:</span>
                        <span class="info-value"><?= htmlspecialchars($user_data["user_name"] ?? "-", ENT_QUOTES, "UTF-8") ?></span>
                    </div>

                    <!-- แสดงอีเมล -->
                    <div class="info-item">
                        <span class="info-label">อีเมล:</span>
                        <span class="info-value"><?= htmlspecialchars($user_data["user_email"] ?? "-", ENT_QUOTES, "UTF-8") ?></span>
                    </div>

                    <!-- แสดงสถานะบัญชี (แสดงสีแดงสำหรับ Admin และสีฟ้าสำหรับ User) -->
                    <div class="info-item">
                        <span class="info-label">สถานะบัญชี:</span>
                        <span class="info-value" style="color: <?= ($user_data["user_role"] ?? "") === "admin" ? "#dc2626" : "#2563eb" ?>;">
                            <?= ($user_data["user_role"] ?? "") === "admin" ? "ผู้ดูแลระบบ (Admin)" : "สมาชิกทั่วไป" ?>
                        </span>
                    </div>

                    <!-- แสดงเพศ (แปลงค่าตัวแปรจากภาษาอังกฤษเป็นภาษาไทย) -->
                    <div class="info-item">
                        <span class="info-label">เพศ:</span>
                        <span class="info-value">
                            <?php 
                                $gender = $user_data["user_gender"] ?? "";
                                echo $gender === "male" ? "ชาย" : ($gender === "female" ? "หญิง" : "-");
                            ?>
                        </span>
                    </div>

                    <!-- แสดงอายุ -->
                    <div class="info-item">
                        <span class="info-label">อายุ:</span>
                        <span class="info-value"><?= !empty($user_data["user_age"]) ? htmlspecialchars((string)$user_data["user_age"]) . " ปี" : "-" ?></span>
                    </div>

                    <!-- แสดงน้ำหนัก -->
                    <div class="info-item">
                        <span class="info-label">น้ำหนัก:</span>
                        <span class="info-value"><?= !empty($user_data["user_weight"]) ? htmlspecialchars((string)$user_data["user_weight"]) . " กก." : "-" ?></span>
                    </div>

                    <!-- แสดงส่วนสูง -->
                    <div class="info-item">
                        <span class="info-label">ส่วนสูง:</span>
                        <span class="info-value"><?= !empty($user_data["user_height"]) ? htmlspecialchars((string)$user_data["user_height"]) . " ซม." : "-" ?></span>
                    </div>
                </div>

                <!-- แสดงกล่องสรุปบันทึกผลการคำนวณล่าสุด (ถ้ามีข้อมูล BMI หรือ TDEE) -->
                <?php if (!empty($user_data["user_bmi"]) || !empty($user_data["user_tdee"])): ?>
                    <div class="result-container">
                        <h2>บันทึกผลล่าสุด</h2>
                        <p>BMI: <strong><?= !empty($user_data["user_bmi"]) ? number_format($user_data["user_bmi"], 2) : "-" ?></strong></p>
                        <p>TDEE: <strong><?= !empty($user_data["user_tdee"]) ? number_format($user_data["user_tdee"], 2) : "-" ?></strong> kcal/วัน</p>
                    </div>
                <?php endif; ?>

                <!-- ปุ่มการทำงานส่วนล่างของโปรไฟล์ -->
                <div class="profile-actions" style="margin-top: 1.5rem;">
                    <!-- แสดงปุ่มไปยังระบบจัดการสมาชิกสำหรับ Admin เท่านั้น -->
                    <?php if (($user_data["user_role"] ?? "") === "admin"): ?>
                        <a href="admin.php" class="btn" style="background-color: #059669;">ระบบจัดการสมาชิก (Admin)</a>
                    <?php endif; ?>
                    
                    <a href="calculate.php" class="btn">ไปที่หน้าคำนวณ</a>
                    <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
                </div>
            <?php else: ?>
                <!-- แสดงข้อความเตือนกรณีหาข้อมูลผู้ใช้ไม่พบ -->
                <p class="error-message">ไม่พบข้อมูลผู้ใช้งาน</p>
            <?php endif; ?>
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