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

// --- ดึงข้อมูล 'role' มาเตรียมไว้สำหรับ Dropdown ---
$roles_list = [];
$sql_roles = "SELECT role_id, role_name FROM role ORDER BY role_id";
$result_roles = $conn->query($sql_roles);
if ($result_roles->num_rows > 0) {
    while ($row = $result_roles->fetch_assoc()) {
        $roles_list[] = $row;
    }
}

// *** แก้ไข ***
// --- 2. ส่วนประมวลผลการค้นหา (รับ 4 ค่าแยกกัน) ---
$search_results = [];
// รับค่าจากฟอร์มทั้ง 4 ช่อง
$pid_query = $_POST['pid_query'] ?? '';
$fname_query = $_POST['fname_query'] ?? '';
$lname_query = $_POST['lname_query'] ?? '';
$selected_role = $_POST['role_filter'] ?? '';

// ตรวจสอบว่ามีการค้นหา (ไม่ว่างเปล่า) อย่างน้อย 1 ช่องหรือไม่
$search_active = !empty($pid_query) || !empty($fname_query) || !empty($lname_query) || !empty($selected_role);

if ($search_active) {

    $sql = "SELECT * FROM $table_name";
    $where_clauses = [];
    $params_types = "";
    $params_values = [];

    // --- สร้างเงื่อนไข SQL แบบไดนามิก ---

    // เงื่อนไขที่ 1: ค้นหา PID
    if (!empty($pid_query)) {
        // !! แก้ 'pid' ให้ตรงกับชื่อคอลัมน์ของคุณ !!
        $where_clauses[] = "pid = ?";
        $params_types .= "s";
        $params_values[] = $pid_query;
    }

    // เงื่อนไขที่ 2: ค้นหา Fname
    if (!empty($fname_query)) {
        // !! แก้ 'fname' ให้ตรงกับชื่อคอลัมน์ของคุณ !!
        $where_clauses[] = "fname LIKE ?";
        $params_types .= "s";
        $params_values[] = "%" . $conn->real_escape_string($fname_query) . "%";
    }

    // เงื่อนไขที่ 3: ค้นหา Lname
    if (!empty($lname_query)) {
        // !! แก้ 'lname' ให้ตรงกับชื่อคอลัมน์ของคุณ !!
        $where_clauses[] = "lname LIKE ?";
        $params_types .= "s";
        $params_values[] = "%" . $conn->real_escape_string($lname_query) . "%";
    }

    // เงื่อนไขที่ 4: ค้นหา Role
    if (!empty($selected_role)) {
        $where_clauses[] = "role_id = ?";
        $params_types .= "i"; // 'i' หมายถึง Integer
        $params_values[] = $selected_role;
    }

    // รวมเงื่อนไขทั้งหมดด้วย "AND"
    $sql .= " WHERE " . implode(" AND ", $where_clauses);

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // นี่คือการดักจับ Error (เช่น ถ้าคุณใส่ชื่อคอลัมน์ผิด)
        die("SQL Error: " . $conn->error . "<br>Full SQL: " . $sql);
    }

    // ...$params_values คือการส่ง Array เข้าไปใน bind_param
    $stmt->bind_param($params_types, ...$params_values);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();

// --- ฟังก์ชันสำหรับไฮไลท์ (ไม่เปลี่ยนแปลง) ---
function highlightText($text, $query)
{
    if (empty($query)) {
        return htmlspecialchars($text);
    }
    $safe_text = htmlspecialchars($text);
    $safe_query = htmlspecialchars($query);
    return str_ireplace($safe_query, "<mark>{$safe_query}</mark>", $safe_text);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบค้นหาเอกสาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            opacity: 0.8; 
        }
        .banner-placeholder {
            width: 100%;
            height: 250px; /* กำหนดความสูงคงที่ */
            background-color: #F5F5DC; /* สีเบจ (Beige) */
            overflow: hidden; /* ซ่อนส่วนที่ล้นออกจากกรอบ */
        }
        .banner-image {
            width: 100%;
            height: 100%;
            object-fit: cover; /* ทำให้รูปภาพเต็มกรอบโดยไม่เสียสัดส่วน */
        }
        .content-wrapper {
            background-color: rgba(245, 245, 220, 0.95); /* สีเบจ (Beige) แบบโปร่งแสงเล็กน้อย */
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
    </style>
</head>

<body>
    <!-- เพิ่มคลาส content-wrapper ที่นี่ -->
    <div class="container mt-4 content-wrapper">
        <!-- ส่วนของ Banner ที่เป็น Responsive -->
        <div class="banner-placeholder mb-4">
            <picture>
                <!-- 1. สำหรับจอใหญ่ (Desktop >= 992px) -->
                <source media="(min-width: 992px)" srcset="images/banner1.jpg">
                <!-- 2. สำหรับจอขนาดกลาง (Tablet >= 768px) -->
                <source media="(min-width: 768px)" srcset="images/banner1.jpg">
                <!-- 3. รูปภาพเริ่มต้นสำหรับจอมือถือและกรณีอื่นๆ (Fallback) -->
                <img src="images/banner1.jpg" alt="Company Banner" class="banner-image">
            </picture>
        </div>
        <h1 class="text-center">🔍 แบบแจ้งรายการเพื่อการหักลดหย่อนข้าราชการ</h1>

        <form action="index.php" method="POST" class="mb-4" id="searchForm">

            <div class="row g-3">

                <div class="col-md-12">
                    <input type="text" class="form-control" placeholder="ค้นหาด้วย เลขประจำตัวประชาชน (13 หลัก)" name="pid_query"
                        id="pid_input" value="<?php echo htmlspecialchars($pid_query); ?>" maxlength="13">
                </div>

                <!-- <div class="col-md-3">
                    <input type="text" class="form-control"
                        placeholder="ค้นหาชื่อ (fname)"
                        name="fname_query" value="<?php echo htmlspecialchars($fname_query); ?>">
                </div> -->

                <!-- <div class="col-md-3">
                    <input type="text" class="form-control"
                        placeholder="ค้นหานามสกุล (lname)"
                        name="lname_query" value="<?php echo htmlspecialchars($lname_query); ?>">
                </div> -->

                <!-- <div class="col-md-3">
                    <select class="form-select" name="role_filter">
                        <option value="">[ เลือกตำแหน่งทั้งหมด ]</option>
                        <?php foreach ($roles_list as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>"
                                <?php if ($selected_role == $role['role_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div> -->
            </div>

            <div class="row mt-3">
                <div class="col-6"> <button class="btn btn-primary w-100" type="submit">
                        🔍 ค้นหา
                    </button>
                </div>
                <div class="col-6"> <a href="index.php" class="btn btn-warning btn-secondary w-100">
                        ❌ ล้างค่า
                    </a>
                </div>
            </div>
        </form>

        <?php if (!empty($search_results)): ?>
            <h3 class="mt-4">ผลการค้นหา (<?php echo count($search_results); ?> รายการ)</h3>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <!-- <th>เลขประจำตัวประชาชน</th> -->
                        <th>คำนำหน้า</th>
                        <th>ชื่อ (fname)</th>
                        <th>นามสกุล (lname)</th>
                        <th>ตำแหน่ง</th>
                        <th>ดาวน์โหลด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($search_results as $row): ?>
                        <tr>
                            <!-- <td><?php echo highlightText($row['pid'], $pid_query); ?></td> -->
                            <td><?php echo htmlspecialchars($row['perfix']); ?></td>
                            <td><?php echo highlightText($row['fname'], $fname_query); ?></td>
                            <td><?php echo highlightText($row['lname'], $lname_query); ?></td>
                            <td>
                                <?php
                                if ($row['role_id'] == 1) {
                                    echo 'ข้าราชการ';
                                } elseif ($row['role_id'] == 2) {
                                    echo 'ลูกจ้างประจำ';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                $file_to_check = __DIR__ . "/pdf_storage/" . $row['pid'] . ".pdf";
                                if (file_exists($file_to_check)):
                                ?>
                                    <a href="preview.php?pid=<?php echo htmlspecialchars($row['pid']); ?>"
                                        class="btn btn-success btn-sm" target="_blank">
                                        ดูไฟล์ PDF
                                    </a>
                                <?php
                                else:
                                ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        ไม่พบไฟล์
                                    </button>
                                <?php
                                endif;
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php
        // *** แก้ไข *** (เงื่อนไข "ไม่พบข้อมูล")
        // ถ้า $search_active เป็น true (มีการค้นหา) แต่ $search_results ว่างเปล่า
        elseif ($search_active):
        ?>
            <p class="alert alert-warning">ไม่พบข้อมูลที่ตรงกับเงื่อนไขที่ค้นหา</p>
        <?php endif; ?>

    </div>

    <script>
        // ดักจับการ submit ของฟอร์มที่มี id="searchForm"
        document.getElementById('searchForm').addEventListener('submit', function (event) {
            // ดึงค่าจากช่อง input ที่มี id="pid_input"
            const pidInput = document.getElementById('pid_input');
            const pidValue = pidInput.value.trim();

            // 1. ตรวจสอบว่าใส่ข้อมูลหรือไม่
            if (pidValue === '') {
                event.preventDefault(); // หยุดการส่งฟอร์ม
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาใส่ข้อมูล',
                    text: 'กรุณากรอกเลขประจำตัวประชาชนเพื่อทำการค้นหา',
                });
                return; // หยุดการทำงานฟังก์ชัน
            }

            // 2. ตรวจสอบว่าเป็นตัวเลขทั้งหมดหรือไม่ (Regular Expression)
            if (!/^\d+$/.test(pidValue)) {
                event.preventDefault(); // หยุดการส่งฟอร์ม
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อมูลไม่ถูกต้อง',
                    text: 'กรุณากรอกเป็นตัวเลขเท่านั้น',
                });
                return;
            }

            // 3. ตรวจสอบว่ามี 13 หลักหรือไม่
            if (pidValue.length !== 13) {
                event.preventDefault(); // หยุดการส่งฟอร์ม
                Swal.fire({
                    icon: 'error',
                    title: 'จำนวนหลักไม่ถูกต้อง',
                    text: 'เลขประจำตัวประชาชนต้องมี 13 หลักเท่านั้น',
                });
                return;
            }

            // ถ้าผ่านทุกเงื่อนไข ฟอร์มจะถูกส่งไปตามปกติ
        });
    </script>
</body>

</html>