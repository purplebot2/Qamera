<?php
require "db.php";

$id  = $_GET["e"] ?? "";
$key = $_GET["key"] ?? "";

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Alleen de organisator (juiste sleutel) mag downloaden
if (!$event || !hash_equals($event["admin_key"], $key)) {
    http_response_code(403);
    exit("Geen toegang.");
}

if (!class_exists("ZipArchive")) {
    exit("De zip-extensie van PHP staat niet aan.");
}

$stmt = $pdo->prepare("SELECT * FROM media WHERE event_id = ? ORDER BY id");
$stmt->execute([$event["id"]]);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$fotos) {
    exit("Er zijn nog geen foto's om te downloaden.");
}

$tmp = tempnam(sys_get_temp_dir(), "qamera");
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::OVERWRITE);

foreach ($fotos as $foto) {
    $pad = __DIR__ . "/uploads/" . $event["id"] . "/" . $foto["bestandsnaam"];
    if (is_file($pad)) {
        $naamInZip = $foto["id"] . "_" . ($foto["origineel"] ?: $foto["bestandsnaam"]);
        $zip->addFile($pad, $naamInZip);
    }
}
$zip->close();

$slug = trim(preg_replace("/[^a-z0-9]+/", "-", strtolower($event["naam"])), "-");
if ($slug === "") {
    $slug = "event";
}

header("Content-Type: application/zip");
header('Content-Disposition: attachment; filename="' . $slug . '.zip"');
header("Content-Length: " . filesize($tmp));
readfile($tmp);
unlink($tmp);
exit;
