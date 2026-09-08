<?php
@mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli('127.0.0.1', 'root', '', 'truong_caodang');
if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}
$conn->query("DELETE FROM mmo_coupons WHERE code IN ('VKC20', 'SINHVIEN10')");
echo "Deleted coupons.\n";
$res = $conn->query("SELECT * FROM mmo_coupons");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "Done.\n";
