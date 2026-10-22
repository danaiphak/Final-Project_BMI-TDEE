<?php
require_once "db.php";

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
                <a href="register.php">สมัครสมาชิก</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>คำนวณ BMI & TDEE</h1>
            <p>คำนวณดัชนีมวลกายและพลังงานที่ร่างกายใช้ต่อวัน</p>
            <div class="hero-buttons">
                <a href="calculate.php" class="btn">เริ่มคำนวณ</a>
                <a href="register.php" class="btn btn-secondary">สมัครสมาชิก</a>
            </div>
        </section>

        <section class="features">
            <div class="feature-card">
                <h2>BMI</h2>
                <p>คำนวณดัชนีมวลกายเพื่อประเมินระดับน้ำหนักเบื้องต้น</p>
            </div>

            <div class="feature-card">
                <h2>TDEE</h2>
                <p>คำนวณพลังงานที่ร่างกายใช้ในแต่ละวันตามกิจกรรมของคุณ</p>
            </div>

            <div class="feature-card">
                <h2>บันทึกผล</h2>
                <p>สมัครสมาชิกเพื่อเข้าสู่ระบบและจัดการข้อมูลส่วนตัว</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>