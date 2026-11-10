<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php"); exit;
}

$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) exit("ID tidak valid.");

$data = [];
foreach (["nama_sekolah", "npsn", "jenjang", "status",
          "alamat", "kelurahan", "kecamatan"] as $k) {
    $v = $_POST[$k] ?? "";
    $data[] = is_string($v) ? trim($v) : "";
}

$long = filter_var($_POST["longitude"] ?? "", FILTER_VALIDATE_FLOAT);
$lat = filter_var($_POST["latitude"] ?? "", FILTER_VALIDATE_FLOAT);

if (in_array("", $data, true) ||
    !preg_match('/^[0-9]{8}$/', $data[1]) ||
    $long === false || $lat === false ||
    abs($long) > 180 || abs($lat) > 90) {
    exit("Periksa isian. NPSN harus 8 digit dan koordinat harus valid.");
}

$data[] = $long;
$data[] = $lat;
$data[] = $id;

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("localhost", "root", "", "pgweb_acara9");
if ($conn->connect_error) die("Koneksi gagal.");
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("UPDATE sekolah SET
    nama_sekolah = ?, npsn = ?, jenjang = ?, status = ?,
    alamat = ?, kelurahan = ?, kecamatan = ?,
    longitude = ?, latitude = ? WHERE id = ?");

if (!$stmt) exit("Periksa struktur tabel sekolah.");

$stmt->bind_param("sssssssddi", ...$data);

if (!$stmt->execute()) {
    exit("Gagal menyimpan. Periksa data dan NPSN yang sudah digunakan.");
}

$conn->close();
header("Location: ../index.php");
exit;