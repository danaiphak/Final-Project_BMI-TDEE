<?php
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลและตั้งค่า Session
require_once "db.php";

// ----------------------------------------------------------------------
// 1. ตรวจสอบการเข้าสู่ระบบและสิทธิ์ Admin
// ----------------------------------------------------------------------
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// ตรวจสิทธิ์ Admin จากฐานข้อมูล
$role_stmt = $conn->prepare("SELECT user_role FROM user WHERE user_id = ? LIMIT 1");
$role_stmt->bind_param("i", $_SESSION["user_id"]);
$role_stmt->execute();
$role_row = $role_stmt->get_result()->fetch_assoc();
$role_stmt->close();

if (!$role_row || $role_row["user_role"] !== "admin") {
    http_response_code(403);
    die("403 - คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

$msg = "";
$error = "";

// ----------------------------------------------------------------------
// 2. ประมวลผลการลบและเปลี่ยนสิทธิ์ (CRUD Operations)
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verify_csrf_token($token)) {
        $error = "คำขอไม่ถูกต้อง CSRF Token ไม่ตรงกัน";
    } else {
        $action = $_POST["action"] ?? "";
        $target_user_id = filter_input(INPUT_POST, "target_user_id", FILTER_VALIDATE_INT);

        if ($target_user_id) {
            // ป้องกันการแก้ไข/ลบบัญชีตัวเอง
            if ($target_user_id === (int)$_SESSION["user_id"]) {
                $error = "คุณไม่สามารถเปลี่ยนแปลงหรือลบบัญชีของตัวเองได้";
            } else {
                if ($action === "delete") {
                    $del_stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
                    $del_stmt->bind_param("i", $target_user_id);
                    if ($del_stmt->execute()) {
                        $msg = "ลบผู้ใช้ ID #{$target_user_id} เรียบร้อยแล้ว";
                    }
                    $del_stmt->close();
                } elseif ($action === "toggle_role") {
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
// 3. ดึงข้อมูลสมาชิกทั้งหมดรวมถึงเบอร์โทรศัพท์ (phone_number)
// ----------------------------------------------------------------------
$sql = "SELECT user_id, user_name, user_email, phone_number, user_role, created_at FROM user ORDER BY user_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการสมาชิก (Admin) | BMI & TDEE</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <!-- Header/Navbar -->
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

    <!-- Main Container ครอบด้วยคลาสสไตล์การ์ดมินิมอล -->
    <main style="max-width: 1050px; width: 100%;">
        <section class="admin-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1 style="margin-bottom: 0;">ระบบจัดการสมาชิก</h1>
                <a href="profile.php" class="btn btn-secondary" style="margin-top: 0; width: auto; padding: 0.5rem 1rem; font-size: 0.9rem;">← กลับหน้าโปรไฟล์</a>
            </div>

            <!-- ข้อความแจ้งเตือนสถานะ -->
            <?php if ($msg !== ""): ?>
                <p class="success-message"><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
            <?php if ($error !== ""): ?>
                <p class="error-message"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <!-- ตารางสไตล์มินิมอลตาม style.css -->
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>สถานะ</th>
                            <th>วันที่สมัคร</th>
                            <th style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$row["user_id"]) ?></td>
                                    <td><strong><?= htmlspecialchars($row["user_name"]) ?></strong></td>
                                    <td><?= htmlspecialchars($row["user_email"]) ?></td>
                                    <td><?= !empty($row["phone_number"]) ? htmlspecialchars($row["phone_number"]) : "-" ?></td>
                                    <td>
                                        <span class="info-value" style="color: <?= $row["user_role"] === 'admin' ? '#dc2626' : '#2563eb' ?>; font-size: 0.85rem;">
                                            <?= $row["user_role"] === 'admin' ? 'ผู้ดูแลระบบ' : 'สมาชิก' ?>
                                        </span>
                                    </td>
                                    <td><?= date("Y-m-d H:i:s", strtotime($row["created_at"])) ?></td>
                                    <td>
                                        <?php if ((int)$row["user_id"] !== (int)$_SESSION["user_id"]): ?>
                                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                                <!-- ปุ่มเปลี่ยนสิทธิ์ -->
                                                <form action="admin.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="toggle_role">
                                                    <input type="hidden" name="target_user_id" value="<?= $row['user_id'] ?>">
                                                    <input type="hidden" name="current_role" value="<?= $row['user_role'] ?>">
                                                    <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; margin: 0; width: auto;">
                                                        <?= $row["user_role"] === "admin" ? "ลดสิทธิ์" : "ตั้งเป็น Admin" ?>
                                                    </button>
                                                </form>

                                                <!-- ปุ่มลบผู้ใช้ -->
                                                <form action="admin.php" method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้หรือไม่?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="target_user_id" value="<?= $row['user_id'] ?>">
                                                    <button type="submit" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; margin: 0; width: auto; background-color: #dc2626;">
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
                            <tr>
                                <td colspan="7" style="text-align: center;">ยังไม่มีสมาชิกในระบบ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date("Y") ?> BMI & TDEE Calculator</p>
    </footer>
</body>
</html>
