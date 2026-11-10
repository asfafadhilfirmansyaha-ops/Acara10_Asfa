<?php
$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) exit("ID tidak valid.");

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("localhost", "root", "", "pgweb_acara9");
if ($conn->connect_error) die("Koneksi gagal.");
$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT * FROM sekolah WHERE id = $id");
if (!$result) exit("Periksa struktur tabel sekolah.");

$row = $result->fetch_assoc();
if (!$row) exit("Data tidak ditemukan.");

function h($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Edit Sekolah</title></head>
<body>
<h2>Form Edit Sekolah</h2>

<form action="edit.php" method="post">
    <input type="hidden" name="id" value="<?= $id ?>">

    Nama Sekolah:<br>
    <input name="nama_sekolah" value="<?= h($row['nama_sekolah']) ?>" maxlength="150" required><br>
    NPSN:<br>
    <input name="npsn" value="<?= h($row['npsn']) ?>" maxlength="8" pattern="[0-9]{8}" required><br>
    Jenjang:<br>
    <input name="jenjang" value="<?= h($row['jenjang']) ?>" maxlength="10" required><br>
    Status:<br>
    <input name="status" value="<?= h($row['status']) ?>" maxlength="10" required><br>
    Alamat:<br>
    <input name="alamat" value="<?= h($row['alamat']) ?>" maxlength="255" required><br>
    Kelurahan:<br>
    <input name="kelurahan" value="<?= h($row['kelurahan']) ?>" maxlength="100" required><br>
    Kecamatan:<br>
    <input name="kecamatan" value="<?= h($row['kecamatan']) ?>" maxlength="100" required><br>
    Longitude:<br>
    <input type="number" name="longitude" value="<?= h($row['longitude']) ?>" min="-180" max="180" step="any" required><br>
    Latitude:<br>
    <input type="number" name="latitude" value="<?= h($row['latitude']) ?>" min="-90" max="90" step="any" required><br><br>

    <input type="submit" value="Submit">
</form>

<a href="../index.php">Kembali</a>
</body>
</html>