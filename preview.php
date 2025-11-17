<?php
// --- 1. ส่วนเชื่อมต่อฐานข้อมูล ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "doc_system";
$table_name = "Combined_Data";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 2. รับค่าและตรวจสอบ PID ---
$pid = $_GET['pid'] ?? '';
$person_data = null;
$error_message = '';

if (empty($pid)) {
    $error_message = "ไม่ได้ระบุ PID";
} else {
    // --- 3. ค้นหาข้อมูลบุคคลจาก PID ---
    $sql = "SELECT pid, perfix, fname, lname FROM $table_name WHERE pid = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $pid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $person_data = $result->fetch_assoc();
        } else {
            $error_message = "ไม่พบข้อมูลสำหรับ PID: " . htmlspecialchars($pid);
        }
        $stmt->close();
    } else {
        $error_message = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL";
    }
}
$conn->close();

// --- 4. ตรวจสอบไฟล์ PDF ---
$file_path = '';
if ($person_data) {
    $file_path_relative = "pdf_storage/" . $person_data['pid'] . ".pdf";
    if (!file_exists(__DIR__ . '/' . $file_path_relative)) {
        $error_message = "ไม่พบไฟล์ PDF สำหรับบุคคลนี้";
        $person_data = null; // ไม่ต้องแสดงข้อมูลถ้าไม่มีไฟล์
    } else {
        $file_path = $file_path_relative;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดูตัวอย่างเอกสาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* ย้ายคุณสมบัติ background-image ไปที่ ::before */
            /* สีสำรองจะแสดงผลหาก ::before ไม่ทำงาน */
            background-color: #D7C097;
        }

        body::before {
            content: '';
            position: fixed; /* ตรึงให้อยู่กับ viewport */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* ส่งไปไว้ด้านหลังสุด */

            /* --- ส่วนสำหรับภาพพื้นหลัง --- */
            background-image: url('images/bg1.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            
            /* --- ส่วนที่คุณสามารถปรับความโปร่งใสได้ --- */
            /* ปรับค่า opacity: 1.0 (ทึบ) ถึง 0.0 (โปร่งใส) */
            opacity: 0.3; 
        }
        body, html { height: 100%; margin: 0; }
        .container-fluid { display: flex; flex-direction: column; height: 100%; }
        iframe { flex-grow: 1; border: none; }
    </style>
</head>
<body>
    <div class="container-fluid p-3">
        <?php if ($person_data && !$error_message): ?>
            <!-- <header class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                <h4 class="mb-0">
                    เอกสารของ: <?php echo htmlspecialchars($person_data['perfix'] . $person_data['fname'] . ' ' . $person_data['lname']); ?>
                </h4>
                <a href="download.php?pid=<?php echo htmlspecialchars($person_data['pid']); ?>" class="btn btn-primary">
                    💾 ดาวน์โหลดไฟล์นี้
                </a>
            </header> -->
            <iframe src="<?php echo $file_path; ?>"></iframe>
        <?php else: ?>
            <div class="alert alert-danger text-center"><strong>เกิดข้อผิดพลาด:</strong> <?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>