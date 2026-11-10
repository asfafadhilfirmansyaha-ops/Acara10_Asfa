<?php
$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) exit("ID tidak valid.");

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("localhost", "root", "", "pgweb_acara9");
if ($conn->connect_error) die("Koneksi gagal.");
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("DELETE FROM sekolah WHERE id = ?");
if (!$stmt) exit("Periksa struktur tabel sekolah.");

$stmt->bind_param("i", $id);
if (!$stmt->execute()) exit("Data gagal dihapus.");

$conn->close();
header("Location: index.php");
exit;