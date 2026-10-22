<?php
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_data = null;

$stmt = $conn->prepare("SELECT user_id, user_name, user_email, user_role, user_gender, user_age, user_weight, user_height, user_bmi, user_tdee, created_at FROM user WHERE user_id = ? LIMIT 1");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
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
    <link rel="stylesheet" href="style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">BMI & TDEE</a>
            <div class="nav-links">
                <a href="index.php">หน้าหลัก</a>
                <a href="calculate.php">คำนวณ</a>
                <?php if (($user_data["user_role"] ?? "") === "admin"): ?>
                    <a href="admin.php">จัดการสมาชิก</a>
                <?php endif; ?>
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="form-container profile-card">
            <h1>ข้อมูลส่วนตัว</h1>

            <?php if ($user_data): ?>
                <div class="profile-info">
                    <div class="info-item">
                        <span class="info-label">ชื่อผู้ใช้:</span>
                        <span class="info-value"><?= htmlspecialchars($user_data["user_name"] ?? "-", ENT_QUOTES, "UTF-8") ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">อีเมล:</span>
                        <span class="info-value"><?= htmlspecialchars($user_data["user_email"] ?? "-", ENT_QUOTES, "UTF-8") ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">สถานะบัญชี:</span>
                        <span class="info-value" style="color: <?= ($user_data["user_role"] ?? "") === "admin" ? "#dc2626" : "#2563eb" ?>;">
                            <?= ($user_data["user_role"] ?? "") === "admin" ? "ผู้ดูแลระบบ (Admin)" : "สมาชิกทั่วไป" ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">เพศ:</span>
                        <span class="info-value">
                            <?php 
                                $gender = $user_data["user_gender"] ?? "";
                                echo $gender === "male" ? "ชาย" : ($gender === "female" ? "หญิง" : "-");
                            ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">อายุ:</span>
                        <span class="info-value"><?= !empty($user_data["user_age"]) ? htmlspecialchars((string)$user_data["user_age"]) . " ปี" : "-" ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">น้ำหนัก:</span>
                        <span class="info-value"><?= !empty($user_data["user_weight"]) ? htmlspecialchars((string)$user_data["user_weight"]) . " กก." : "-" ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">ส่วนสูง:</span>
                        <span class="info-value"><?= !empty($user_data["user_height"]) ? htmlspecialchars((string)$user_data["user_height"]) . " ซม." : "-" ?></span>
                    </div>
                </div>

                <?php if (!empty($user_data["user_bmi"]) || !empty($user_data["user_tdee"])): ?>
                    <div class="result-container">
                        <h2>บันทึกผลล่าสุด</h2>
                        <p>BMI: <strong><?= !empty($user_data["user_bmi"]) ? number_format($user_data["user_bmi"], 2) : "-" ?></strong></p>
                        <p>TDEE: <strong><?= !empty($user_data["user_tdee"]) ? number_format($user_data["user_tdee"], 2) : "-" ?></strong> kcal/วัน</p>
                    </div>
                <?php endif; ?>

                <div class="profile-actions" style="margin-top: 1.5rem;">
                    <?php if (($user_data["user_role"] ?? "") === "admin"): ?>
                        <a href="admin.php" class="btn" style="background-color: #059669;">ระบบจัดการสมาชิก (Admin)</a>
                    <?php endif; ?>
                    <a href="calculate.php" class="btn">ไปที่หน้าคำนวณ</a>
                    <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
                </div>
            <?php else: ?>
                <p class="error-message">ไม่พบข้อมูลผู้ใช้งาน</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>