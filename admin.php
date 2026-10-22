<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once "db.php";

// ----------------------------------------------------------------------
// 1. ตรวจสอบการเข้าสู่ระบบและสิทธิ์แอดมิน (Authentication & Authorization)
// ----------------------------------------------------------------------

// ตรวจสอบว่ามีผู้ใช้ล็อกอินอยู่หรือไม่ หากไม่มีส่งกลับไปหน้าล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// ค้นหาสิทธิ์ (user_role) ของผู้ใช้ที่ล็อกอินอยู่จากฐานข้อมูลโดยตรง
$role_stmt = $conn->prepare("SELECT user_role FROM user WHERE user_id = ? LIMIT 1");
$role_stmt->bind_param("i", $_SESSION["user_id"]);
$role_stmt->execute();
$role_row = $role_stmt->get_result()->fetch_assoc();
$role_stmt->close();

// หากไม่พบข้อมูลผู้ใช้ หรือสิทธิ์ไม่ใช่ 'admin' ให้ส่งรหัส 401/403 และหยุดการทำงาน
if (!$role_row || $role_row["user_role"] !== "admin") {
    http_response_code(403);
    die("403 - คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน (สำเร็จ / ข้อผิดพลาด)
$msg = "";
$error = "";

// ----------------------------------------------------------------------
// 2. ประมวลผลคำขอจัดการข้อมูลผู้ใช้ (CRUD Operations via POST)
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // ตรวจสอบความถูกต้องของ CSRF Token เพื่อป้องกันการโจมตีแบบ Cross-Site Request Forgery
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง Token ไม่ตรงกัน";
    } else {
        // รับค่า Action (delete หรือ toggle_role) และ ID ของผู้ใช้เป้าหมาย
        $action = $_POST["action"] ?? "";
        $target_user_id = filter_input(INPUT_POST, "target_user_id", FILTER_VALIDATE_INT);

        if ($target_user_id) {
            // ป้องกันไม่ให้แอดมินแก้ไขสิทธิ์หรือลบบัญชีตัวเองที่กำลังใช้งานอยู่
            if ($target_user_id === (int)$_SESSION["user_id"]) {
                $error = "คุณไม่สามารถเปลี่ยนแปลงหรือลบบัญชีของตัวเองได้ในหน้านี้";
            } else {
                
                // กรณีเลือก action = ลบผู้ใช้งาน (delete)
                if ($action === "delete") {
                    $del_stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
                    $del_stmt->bind_param("i", $target_user_id);
                    if ($del_stmt->execute()) {
                        $msg = "ลบผู้ใช้ ID #{$target_user_id} เรียบร้อยแล้ว";
                    }
                    $del_stmt->close();
                
                // กรณีเลือก action = สลับสิทธิ์ผู้ใช้งาน (toggle_role)
                } elseif ($action === "toggle_role") {
                    // หากเป็น admin ให้เปลี่ยนเป็น user / หากเป็น user ให้เปลี่ยนเป็น admin
                    $new_role = ($_POST["current_role"] ?? "") === "admin" ? "user" : "admin";
                    
                    $up_stmt = $conn->prepare("UPDATE user SET user_role = ? WHERE user_id = ?");
                    $up_stmt->bind_param("si", $new_role, $target_user_id);
                    if ($up_stmt->execute()) {
                        $msg = "อัปเดตสิทธิ์ผู้ใช้ ID #{$target_user_id} เป็น {$new_role} เรียบร้อยแล้ว";
                    }
                    $up_stmt->close();
                }
            }
        }
    }
}

// ----------------------------------------------------------------------
// 3. ดึงข้อมูลรายชื่อสมาชิกทั้งหมดเพื่อนำมาแสดงผลในตาราง
// ----------------------------------------------------------------------
$sql = "SELECT user_id, user_name, user_email, user_gender, user_age, user_weight, user_height, user_bmi, user_tdee, user_role, created_at FROM user ORDER BY user_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการสมาชิก (Admin) | BMI & TDEE</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- ส่วนหัวของเว็บไซต์ (Navigation Bar) -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">BMI & TDEE (Admin)</a>
            <div class="nav-links">
                <a href="profile.php">โปรไฟล์</a>
                <a href="calculate.php">คำนวณ</a>
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </nav>
    </header>

    <!-- ส่วนเนื้อหาหลัก -->
    <main style="max-width: 1150px;">
        <section class="admin-container">
            <h1>รายชื่อสมาชิกและจัดการระบบ</h1>

            <!-- แสดงข้อความแจ้งเตือนเมื่อการทำงานสำเร็จ -->
            <?php if ($msg !== ""): ?>
                <p class="success-message"><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <!-- แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด -->
            <?php if ($error !== ""): ?>
                <p class="error-message"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
            
            <!-- ตารางแสดงรายชื่อสมาชิก -->
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>เพศ/อายุ</th>
                            <th>นน./สส.</th>
                            <th>BMI</th>
                            <th>TDEE</th>
                            <th>สิทธิ์</th>
                            <th>วันที่สมัคร</th>
                            <th style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- วนลูปดึงข้อมูลสมาชิกลงทีละแถว -->
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$row["user_id"]) ?></td>
                                    <td><strong><?= htmlspecialchars($row["user_name"]) ?></strong></td>
                                    <td><?= htmlspecialchars($row["user_email"]) ?></td>
                                    <td>
                                        <!-- แปลงค่าเพศและอายุ -->
                                        <?= ($row["user_gender"] === "male" ? "ชาย" : ($row["user_gender"] === "female" ? "หญิง" : "-")) ?> / 
                                        <?= $row["user_age"] ? htmlspecialchars((string)$row["user_age"]) . " ปี" : "-" ?>
                                    </td>
                                    <td>
                                        <!-- แสดงน้ำหนักและส่วนสูง -->
                                        <?= $row["user_weight"] ? htmlspecialchars((string)$row["user_weight"]) . " กก." : "-" ?> / 
                                        <?= $row["user_height"] ? htmlspecialchars((string)$row["user_height"]) . " ซม." : "-" ?>
                                    </td>
                                    <td><?= $row["user_bmi"] ? number_format($row["user_bmi"], 2) : "-" ?></td>
                                    <td><?= $row["user_tdee"] ? number_format($row["user_tdee"], 2) : "-" ?></td>
                                    <td>
                                        <!-- แสดงสิทธิ์การใช้งาน (แอดมินใช้สีแดง / ผู้ใช้ทั่วไปใช้สีฟ้า) -->
                                        <span style="font-weight: 600; color: <?= $row["user_role"] === 'admin' ? '#dc2626' : '#2563eb' ?>;">
                                            <?= htmlspecialchars($row["user_role"]) ?>
                                        </span>
                                    </td>
                                    <td><?= date("d/m/Y H:i", strtotime($row["created_at"])) ?></td>
                                    <td>
                                        <!-- ซ่อนปุ่มจัดการหากเป็นแถบข้อมูลของแอดมินที่กำลังล็อกอินอยู่ -->
                                        <?php if ((int)$row["user_id"] !== (int)$_SESSION["user_id"]): ?>
                                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                                
                                                <!-- ฟอร์มสำหรับปุ่มเปลี่ยนสิทธิ์ผู้ใช้ -->
                                                <form action="admin.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="toggle_role">
                                                    <input type="hidden" name="target_user_id" value="<?= $row['user_id'] ?>">
                                                    <input type="hidden" name="current_role" value="<?= $row['user_role'] ?>">
                                                    <button type="submit" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.8rem; margin: 0; background-color: #64748b;">
                                                        <?= $row["user_role"] === "admin" ? "ลดสิทธิ์" : "ตั้งเป็น Admin" ?>
                                                    </button>
                                                </form>

                                                <!-- ฟอร์มสำหรับปุ่มลบผู้ใช้ -->
                                                <form action="admin.php" method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้หรือไม่? ข้อมูลจะไม่สามารถกู้คืนได้');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="target_user_id" value="<?= $row['user_id'] ?>">
                                                    <button type="submit" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.8rem; margin: 0; background-color: #dc2626;">
                                                        ลบ
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem; text-align: center; display: block;">(บัญชีคุณ)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- กรณีไม่มีผู้ใช้ในระบบ -->
                            <tr>
                                <td colspan="10" style="text-align: center;">ยังไม่มีสมาชิกในระบบ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ส่วนท้ายกระดาษ (Footer) -->
    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>