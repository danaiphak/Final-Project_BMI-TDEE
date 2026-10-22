<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลและการจัดการ Session
require_once "db.php";

// ----------------------------------------------------------------------
// 1. ตรวจสอบสถานะการเข้าสู่ระบบ
// ----------------------------------------------------------------------
// หากผู้ใช้ล็อกอินอยู่แล้ว ($_SESSION["user_id"] มีค่า)
// ให้ส่งผู้ใช้ไปยังหน้าโปรไฟล์ส่วนตัวทันที ไม่ต้องแสดงหน้าแรกอีก
if (isset($_SESSION["user_id"])) {
    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMI & TDEE Calculator</title>
    <!-- นำเข้าไฟล์สไตล์ CSS หลักสำหรับตกแต่งหน้าเว็บ -->
    <link rel="stylesheet" href="style.css">
    <!-- นำเข้าไฟล์ JavaScript สำหรับฟังก์ชันการทำงานเพิ่มเติม -->
    <script src="js/script.js" defer></script>
</head>
<body>
    <!-- ------------------------------------------------------------------
         2. ส่วนหัวของเว็บไซต์ (Navigation Bar)
         ------------------------------------------------------------------ -->
    <header>
        <nav class="navbar">
            <!-- โลโก้เว็บไซต์ที่กดแล้วจะกลับมาหน้าหลัก -->
            <a href="index.php" class="logo">BMI & TDEE</a>
            <!-- เมนูนำทางสำหรับผู้เยี่ยมชมทั่วไป -->
            <div class="nav-links">
                <a href="index.php">หน้าหลัก</a>
                <a href="login.php">เข้าสู่ระบบ</a>
                <a href="register.php">สมัครสมาชิก</a>
            </div>
        </nav>
    </header>

    <!-- ------------------------------------------------------------------
         3. ส่วนเนื้อหาหลัก (Main Content)
         ------------------------------------------------------------------ -->
    <main>
        <!-- ส่วนต้อนรับและปุ่มทางลัดหลัก (Hero Banner) -->
        <section class="hero">
            <h1>คำนวณ BMI & TDEE</h1>
            <p>คำนวณดัชนีมวลกายและพลังงานที่ร่างกายใช้ต่อวัน</p>
            <div class="hero-buttons">
                <a href="calculate.php" class="btn">เริ่มคำนวณ</a>
                <a href="register.php" class="btn btn-secondary">สมัครสมาชิก</a>
            </div>
        </section>

        <!-- ส่วนแนะนำฟีเจอร์หลักของระบบ (Features Section) -->
        <section class="features">
            <!-- การ์ดฟีเจอร์ 1: BMI -->
            <div class="feature-card">
                <h2>BMI</h2>
                <p>คำนวณดัชนีมวลกายเพื่อประเมินระดับน้ำหนักเบื้องต้น</p>
            </div>

            <!-- การ์ดฟีเจอร์ 2: TDEE -->
            <div class="feature-card">
                <h2>TDEE</h2>
                <p>คำนวณพลังงานที่ร่างกายใช้ในแต่ละวันตามกิจกรรมของคุณ</p>
            </div>

            <!-- การ์ดฟีเจอร์ 3: บันทึกผล -->
            <div class="feature-card">
                <h2>บันทึกผล</h2>
                <p>สมัครสมาชิกเพื่อเข้าสู่ระบบและจัดการข้อมูลส่วนตัว</p>
            </div>
        </section>
    </main>

    <!-- ------------------------------------------------------------------
         4. ส่วนท้ายกระดาษ (Footer)
         ------------------------------------------------------------------ -->
    <footer>
        <!-- แสดงปี ค.ศ. ปัจจุบันแบบอัตโนมัติด้วยฟังก์ชัน date("Y") -->
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>