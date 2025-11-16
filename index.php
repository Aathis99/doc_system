<?php
// --- 1. ส่วนเชื่อมต่อฐานข้อมูล ---
$servername = "localhost";
$username = "root"; // <-- User เริ่มต้นของ XAMPP
$password = "";     // <-- รหัสผ่านเริ่มต้นของ XAMPP (คือว่างเปล่า)
$dbname = "doc_system"; // <-- ชื่อฐานข้อมูลที่คุณสร้าง
$table_name = "combined_data"; // <-- ชื่อตารางที่ phpMyAdmin สร้าง

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 2. ส่วนประมวลผลการค้นหา ---
$search_results = [];
$search_query = "";

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $search_query = $_GET['query'];
    
    // ป้องกัน SQL Injection
    $query_safe = "%" . $conn->real_escape_string($search_query) . "%"; 
    
    // ค้นหาจาก 3 คอลัมน์ (pid, fname, lname) - *แก้ชื่อคอลัมน์ให้ตรงกับของคุณ*
    $sql = "SELECT * FROM $table_name 
            WHERE pid LIKE ? OR fname LIKE ? OR lname LIKE ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $query_safe, $query_safe, $query_safe);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบค้นหาเอกสาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <div class="container mt-5">
        <h1 class="text-center">🔍 ระบบค้นหาเอกสาร</h1>

        <form action="index.php" method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" 
                       placeholder="ค้นหาจาก PID, ชื่อ (fname), หรือ นามสกุล (lname)..." 
                       name="query" value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-primary" type="submit">ค้นหา</button>
            </div>
        </form>

        <?php if (!empty($search_results)): ?>
            <h3 class="mt-4">ผลการค้นหา (<?php echo count($search_results); ?> รายการ)</h3>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>PID</th>
                        <th>ชื่อ (fname)</th>
                        <th>นามสกุล (lname)</th>
                        <th>ชื่อไฟล์เก่า (oldname)</th>
                        <th>ดาวน์โหลด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($search_results as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['pid']); ?></td>
                            <td><?php echo htmlspecialchars($row['fname']); ?></td>
                            <td><?php echo htmlspecialchars($row['lname']); ?></td>
                            <td><?php echo htmlspecialchars($row['oldname']); ?></td>
                            <td>
                                <a href="download.php?pid=<?php echo htmlspecialchars($row['pid']); ?>" 
                                   class="btn btn-success btn-sm" target="_blank">
                                   ดาวน์โหลด PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($_GET['query'])): ?>
            <p class="alert alert-warning">ไม่พบข้อมูลที่ตรงกับ "<?php echo htmlspecialchars($search_query); ?>"</p>
        <?php endif; ?>

    </div>

</body>
</html>