<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("localhost", "root", "", "pgweb_acara9");
if ($conn->connect_error) die("Koneksi gagal.");
$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT * FROM sekolah ORDER BY id");
if (!$result) exit("Tabel sekolah belum tersedia.");

$kolom = [
    "nama_sekolah"=>"Nama Sekolah", "npsn"=>"NPSN", "jenjang"=>"Jenjang",
    "status"=>"Status", "alamat"=>"Alamat", "kelurahan"=>"Kelurahan",
    "kecamatan"=>"Kecamatan", "longitude"=>"Longitude", "latitude"=>"Latitude"
];

$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Web GIS Persebaran Sekolah</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
h1 { text-align:center; font:bold 25px Arial; color:#174b3b; }
#map { height:500px; margin:12px 0; border-radius:8px; }
button { padding:8px 12px; cursor:pointer; }
#ukur.aktif { background:#ea580c; color:white; }
#hasil { padding:8px; font:bold 13px Arial; color:#9a3412; }
#map.mengukur, #map.mengukur .leaflet-interactive { cursor:crosshair !important; }

.pin {
    position:relative; display:grid; place-items:center; box-sizing:border-box;
    width:34px; height:34px; background:var(--warna);
    border:2px solid white; border-radius:10px; box-shadow:0 2px 7px #0005;
}
.pin::after {
    content:""; position:absolute; bottom:-7px; left:10px;
    border-left:5px solid transparent; border-right:5px solid transparent;
    border-top:7px solid var(--warna);
}
.item-layer { display:inline-flex; align-items:center; gap:7px; vertical-align:middle; }
.item-layer .pin { width:24px; height:24px; border-radius:7px; box-shadow:none; }
.item-layer .pin::after { display:none; }
.item-layer svg { width:16px; height:16px; }
.leaflet-control-layers-overlays label { margin:4px 0; }
.leaflet-control-layers-overlays input { top:0; vertical-align:middle; }
</style>
</head>

<body>
<h1>WEB GIS PERSEBARAN SEKOLAH</h1>

<button type="button" id="ukur">Ukur jarak</button>
<button type="button" id="bersihkan">Hapus ukuran</button>
<output id="hasil" aria-live="polite">Jarak: 0 m</output>

<div id="map"></div>
<p id="status" role="status"></p>

<?php
echo "<a href='input/index.html'>Input</a><table border='1'><tr>";
foreach ($kolom as $judul) echo "<th>$judul</th>";
echo "<th colspan='2'>Aksi</th></tr>";

foreach ($data as $row) {
    $id = (int) $row["id"];
    echo "<tr>";

    foreach ($kolom as $nama => $judul)
        echo "<td>".htmlspecialchars((string) $row[$nama], ENT_QUOTES, "UTF-8")."</td>";

    echo "<td><a href='delete.php?id=$id'>hapus</a></td>
          <td><a href='edit/index.php?id=$id'>edit</a></td></tr>";
}

if (!$data) echo "<tr><td colspan='11'>Belum ada data.</td></tr>";
echo "</table>";
?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
var data = <?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
var kolom = <?= json_encode($kolom) ?>;

if (typeof L === "undefined") {
    document.getElementById("status").textContent = "Leaflet belum termuat. Periksa internet.";
} else {

    /* Initial Map */
    var map = L.map("map").setView([-6.93, 107.63], 12);

    /* Tile Basemap */
    var basemap1 = L.tileLayer(
        "https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom:19,
            attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }
    );

    var basemap2 = L.tileLayer(
        "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
            maxZoom:19,
            attribution:"Tiles &copy; Esri, Maxar, Earthstar Geographics, GIS User Community"
        }
    );

    basemap1.addTo(map);

    /* Simbol dan Layer Sekolah */
    var warna = {
        PAUD:"#db2777", TK:"#7c3aed", SD:"#059669", SMP:"#2563eb",
        SMA:"#dc2626", SMK:"#d97706", LAINNYA:"#64748b"
    };

    var layers = {}, overlayMaps = {}, batas = [];
    var gedung = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-6 9 6M5 9v12h14V9M10 21v-6h4v6M8 12h.01M16 12h.01M12 8h.01"/></svg>';

    function simbol(j) {
        return '<span class="pin" style="--warna:'+warna[j]+'">'+gedung+'</span>';
    }

    function aman(teks) {
        var el = document.createElement("span");
        el.textContent = teks ?? "";
        return el.innerHTML;
    }

    Object.keys(warna).forEach(function(j) {
        layers[j] = L.layerGroup().addTo(map);
        overlayMaps[
            '<span class="item-layer">'+simbol(j)+'<span>'+j+'</span></span>'
        ] = layers[j];
    });

    /* Layer Marker dari Basis Data */
    data.forEach(function(sekolah) {
        var lat = Number(sekolah.latitude), lon = Number(sekolah.longitude);

        if (!String(sekolah.latitude ?? "").trim()
            || !String(sekolah.longitude ?? "").trim()
            || !Number.isFinite(lat) || !Number.isFinite(lon)
            || Math.abs(lat)>90 || Math.abs(lon)>180) return;

        var j = String(sekolah.jenjang ?? "").trim().toUpperCase();
        if (j === "PUD") j = "PAUD";
        if (!Object.hasOwn(warna, j)) j = "LAINNYA";

        var icon = L.divIcon({
            className:"ikon-sekolah", html:simbol(j),
            iconSize:[34,42], iconAnchor:[17,41]
        });

        var marker = L.marker([lat,lon], {
            icon:icon,
            title:String(sekolah.nama_sekolah ?? j),
            bubblingMouseEvents:false
        }).addTo(layers[j]);

        var popup = Object.keys(kolom).map(function(k) {
            return "<b>"+aman(kolom[k])+":</b> "+aman(sekolah[k]);
        }).join("<br>");

        marker.on("click", function() {
            if (mengukur) tambahTitik(marker.getLatLng());
            else L.popup({offset:[0,-34]})
                .setLatLng(marker.getLatLng()).setContent(popup).openOn(map);
        });

        batas.push([lat,lon]);
    });

    if (batas.length) map.fitBounds(batas, {padding:[35,45], maxZoom:16});

    document.getElementById("status").textContent =
        batas.length+" dari "+data.length+" lokasi sekolah ditampilkan.";

    /* Control Layer */
    var baseMaps = {
        "OpenStreetMap":basemap1,
        "Esri Imagery":basemap2
    };

    L.control.layers(baseMaps, overlayMaps, {collapsed:false}).addTo(map);

    /* Scale Bar */
    L.control.scale({
        maxWidth:150, position:"bottomright", imperial:false
    }).addTo(map);

    /* Tool Pengukuran Jarak */
    var mengukur = false, titikUkur = [], jarak = 0, garis = null;
    var layerUkur = L.layerGroup().addTo(map);
    var tombol = document.getElementById("ukur");
    var hasil = document.getElementById("hasil");

    function mode(aktif) {
        mengukur = aktif;
        tombol.textContent = aktif ? "Selesai ukur" : "Ukur jarak";
        tombol.classList.toggle("aktif", aktif);
        tombol.setAttribute("aria-pressed", String(aktif));
        map.getContainer().classList.toggle("mengukur", aktif);
        map.doubleClickZoom[aktif ? "disable" : "enable"]();
    }

    function hapusUkuran() {
        mode(false);
        layerUkur.clearLayers();
        titikUkur = []; jarak = 0; garis = null;
        hasil.textContent = "Jarak: 0 m";
    }

    function tambahTitik(p) {
        if (!mengukur) return;
        map.closePopup();

        if (titikUkur.length)
            jarak += map.distance(titikUkur[titikUkur.length-1], p);

        titikUkur.push(p);

        if (!garis) garis = L.polyline([], {
            color:"#ea580c", weight:3, dashArray:"6,6", interactive:false
        }).addTo(layerUkur);

        garis.addLatLng(p);

        L.circleMarker(p, {
            radius:5, color:"white", weight:2,
            fillColor:"#ea580c", fillOpacity:1, interactive:false
        }).addTo(layerUkur);

        var nilai = jarak >= 1000 ? jarak/1000 : jarak;

        hasil.textContent = titikUkur.length === 1
            ? "Titik awal dipilih. Klik titik berikutnya."
            : "Jarak: "+nilai.toLocaleString("id-ID", {
                maximumFractionDigits:2
            })+(jarak>=1000 ? " km" : " m");
    }

    tombol.onclick = function() {
        if (mengukur) mode(false);
        else {
            hapusUkuran(); mode(true); map.closePopup();
            hasil.textContent = "Klik titik awal di peta atau simbol sekolah.";
        }
    };

    document.getElementById("bersihkan").onclick = hapusUkuran;
    map.on("click", function(e) { tambahTitik(e.latlng); });
    mode(false);
}
</script>
</body>
</html>