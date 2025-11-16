<?php
if (isset($_GET['pid']) && !empty($_GET['pid'])) {
    
    // 1. รับค่า PID
    $pid = $_GET['pid'];
    
    // 2. (สำคัญ) ป้องกันการโจมตี Path Traversal
    //    เช็กให้แน่ใจว่า pid ไม่มีอักขระแปลกปลอม (ให้มีแค่ตัวเลข)
    if (!ctype_digit($pid)) {
        die("Invalid PID.");
    }

    // 3. กำหนดชื่อไฟล์และที่อยู่ของไฟล์ PDF
    $filename = $pid . ".pdf"; // เช่น "1529900192498.pdf"
    $filepath = __DIR__ . "/pdf_storage/" . $filename; // __DIR__ คือที่อยู่ของโฟลเดอร์นี้
                                                      // เช่น C:\xampp\htdocs\doc_app/pdf_storage/1529900192498.pdf

    // 4. เช็กว่าไฟล์มีอยู่จริงหรือไม่
    if (file_exists($filepath)) {
        
        // 5. (ส่วนสำคัญ) บังคับให้เบราว์เซอร์ดาวน์โหลด
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // อ่านไฟล์และส่งออก
        ob_clean();
        flush();
        readfile($filepath);
        exit;
        
    } else {
        // ถ้าไม่พบไฟล์
        die("File not found for PID: " . htmlspecialchars($pid));
    }
} else {
    die("No PID specified.");
}
?>