<?php
require "db.php";

// Slaat een foto op en verkleint hem tot maximaal 1280 px breed.
// Een gsm-foto van 5 MB wordt zo ongeveer 300-500 KB.
function bewaarFoto(string $tmp, string $doel, string $type): bool {
    $max = 1280;

    // Zonder GD, of bij een gif (kan animatie bevatten): gewoon verplaatsen
    if ($type === "image/gif" || !function_exists("imagecreatefromjpeg")) {
        return move_uploaded_file($tmp, $doel);
    }

    switch ($type) {
        case "image/jpeg":
            $img = @imagecreatefromjpeg($tmp);
            break;
        case "image/png":
            $img = @imagecreatefrompng($tmp);
            break;
        case "image/webp":
            $img = function_exists("imagecreatefromwebp") ? @imagecreatefromwebp($tmp) : false;
            break;
        default:
            $img = false;
    }
    if (!$img) {
        return move_uploaded_file($tmp, $doel);
    }

    // Gsm-foto's hebben vaak een draaiing in de EXIF-gegevens: herstellen
    if ($type === "image/jpeg" && function_exists("exif_read_data")) {
        $exif  = @exif_read_data($tmp);
        $stand = $exif["Orientation"] ?? 1;
        $hoeken = [3 => 180, 6 => -90, 8 => 90];
        if (isset($hoeken[$stand])) {
            $gedraaid = imagerotate($img, $hoeken[$stand], 0);
            if ($gedraaid) {
                $img = $gedraaid;
            }
        }
    }

    // Verkleinen als de foto breder is dan 1280 px
    if (imagesx($img) > $max) {
        $kleiner = imagescale($img, $max);
        if ($kleiner) {
            $img = $kleiner;
        }
    }

    switch ($type) {
        case "image/jpeg":
            imagejpeg($img, $doel, 85);
            break;
        case "image/png":
            imagesavealpha($img, true);
            imagepng($img, $doel, 6);
            break;
        case "image/webp":
            imagewebp($img, $doel, 85);
            break;
    }
    imagedestroy($img);

    return is_file($doel);
}

// 1. Het event opzoeken
$id = $_GET["e"] ?? "";

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit("Event niet gevonden.");
}

$naam = $event["naam"];

// Thema controleren, zodat alleen bekende waarden als class gebruikt worden
$themas = ["teal", "zon", "roos", "nacht"];
$thema  = in_array($event["thema"], $themas, true) ? $event["thema"] : "teal";

// 2. Is dit de organisator? (de sleutel in de link moet kloppen)
$key     = $_GET["key"] ?? "";
$isAdmin = hash_equals($event["admin_key"], $key);
$terug   = "event.php?e=" . $event["id"] . ($isAdmin ? "&key=" . $key : "");

// De link die gasten gebruiken (NOOIT met de sleutel)
$schema   = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$map      = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
$gastLink = $schema . "://" . $_SERVER["HTTP_HOST"] . $map . "/event.php?e=" . $event["id"];

// 3. Een foto verwijderen (alleen organisator)
if ($isAdmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["verwijder"])) {
    $stmt = $pdo->prepare("SELECT * FROM media WHERE id = ? AND event_id = ?");
    $stmt->execute([(int)$_POST["verwijder"], $event["id"]]);
    $foto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($foto) {
        $pad = __DIR__ . "/uploads/" . $event["id"] . "/" . $foto["bestandsnaam"];
        if (is_file($pad)) {
            unlink($pad);
        }
        $stmt = $pdo->prepare("DELETE FROM media WHERE id = ?");
        $stmt->execute([$foto["id"]]);
    }

    header("Location: $terug");
    exit;
}

// 4. Foto's uploaden (iedereen)
$toegelaten = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp",
    "image/gif"  => "gif",
];
$maxGrootte = 10 * 1024 * 1024; // 10 MB per foto
$fouten = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["fotos"])) {
    $uploadMap = __DIR__ . "/uploads/" . $event["id"];
    if (!is_dir($uploadMap)) {
        mkdir($uploadMap, 0755, true);
    }

    $gelukt = 0;

    foreach ($_FILES["fotos"]["name"] as $i => $origineel) {
        $uploadFout = $_FILES["fotos"]["error"][$i];
        $tmp        = $_FILES["fotos"]["tmp_name"][$i];
        $grootte    = $_FILES["fotos"]["size"][$i];

        if ($uploadFout === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($uploadFout !== UPLOAD_ERR_OK) {
            $fouten[] = "$origineel: upload mislukt (te groot voor de server?).";
            continue;
        }
        if ($grootte > $maxGrootte) {
            $fouten[] = "$origineel: groter dan 10 MB.";
            continue;
        }

        // Type controleren op de inhoud, niet op de naam
        $info = getimagesize($tmp);
        $type = $info ? $info["mime"] : "";
        if (!isset($toegelaten[$type])) {
            $fouten[] = "$origineel: geen toegelaten afbeelding.";
            continue;
        }

        $bestandsnaam = bin2hex(random_bytes(10)) . "." . $toegelaten[$type];

        if (bewaarFoto($tmp, "$uploadMap/$bestandsnaam", $type)) {
            $oorspronkelijk = preg_replace('/[^\w.\- ]+/u', "_", basename($origineel));

            $stmt = $pdo->prepare(
                "INSERT INTO media (event_id, bestandsnaam, origineel, soort) VALUES (?, ?, ?, 'image')"
            );
            $stmt->execute([$event["id"], $bestandsnaam, mb_substr($oorspronkelijk, 0, 120)]);
            $gelukt++;
        } else {
            $fouten[] = "$origineel: opslaan mislukt.";
        }
    }

    // Alles gelukt? Pagina opnieuw laden, zodat F5 niets dubbel uploadt
    if (!$fouten && $gelukt > 0) {
        header("Location: $terug");
        exit;
    }
}

// 5. Foto's ophalen: standaard de nieuwste 40, met een knop om er meer te tonen
$limiet = (int)($_GET["max"] ?? 40);
$limiet = max(40, min(500, $limiet));

$stmt = $pdo->prepare("SELECT COUNT(*) FROM media WHERE event_id = ?");
$stmt->execute([$event["id"]]);
$totaal = (int)$stmt->fetchColumn();

// $limiet is een geheel getal (hierboven gecontroleerd), dus veilig in de query
$stmt = $pdo->prepare("SELECT * FROM media WHERE event_id = ? ORDER BY id DESC LIMIT $limiet");
$stmt->execute([$event["id"]]);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($naam) ?> - Qamera</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="thema-<?= $thema ?>">

<header class="header">
    <a href="index.php" class="logo">Qamera</a>
    <a href="maak.php">Event maken</a>
</header>

<main>

    <?php if ($isAdmin): ?>
    <section class="organisator">
        <div class="poster">
            <p class="poster-titel"><?= htmlspecialchars($naam) ?></p>
            <div id="qr"></div>
            <p class="poster-tekst">Scan en zet je foto's erbij</p>
            <p class="poster-link"><?= htmlspecialchars($gastLink) ?></p>
        </div>

        <div class="organisator-info geen-print">
            <h2>Voor de organisator</h2>
            <p>Deel of print de QR-code. Gasten gebruiken een link zonder sleutel.</p>
            <div class="knoppen">
                <button type="button" class="knop" id="kopieer">Link kopiëren</button>
                <button type="button" class="knop" onclick="window.print()">Afdrukken</button>
                <a class="knop" href="zip.php?e=<?= $event["id"] ?>&key=<?= htmlspecialchars($key) ?>">Alles downloaden (zip)</a>
            </div>
            <p class="klein">Bewaar de link van deze pagina. Alleen daarmee kan je foto's verwijderen en alles downloaden.</p>
        </div>
    </section>
    <?php endif; ?>

    <div class="wand">

        <div class="wand-titel">
            <h1><?= htmlspecialchars($naam) ?></h1>

            <?php foreach ($fouten as $fout): ?>
                <p class="fout"><?= htmlspecialchars($fout) ?></p>
            <?php endforeach; ?>

            <?php if ($totaal === 0): ?>
                <p>Nog geen foto's. Wees de eerste!</p>
            <?php elseif ($totaal > count($fotos)): ?>
                <p><?= count($fotos) ?> nieuwste van <?= $totaal ?> foto's</p>
            <?php else: ?>
                <p><?= $totaal ?> foto's</p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <label for="fotos" class="knop">Foto's toevoegen</label>
                <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple hidden
                       onchange="this.form.submit()">
            </form>

            <?php if ($totaal > count($fotos)): ?>
                <p><a href="<?= htmlspecialchars($terug . "&max=" . ($limiet + 40)) ?>">Toon meer foto's</a></p>
            <?php endif; ?>
        </div>

        <?php foreach ($fotos as $foto): ?>
            <figure class="polaroid">
                <img src="uploads/<?= $event["id"] ?>/<?= htmlspecialchars($foto["bestandsnaam"]) ?>"
                     alt="" loading="lazy">

                <?php if ($isAdmin): ?>
                    <form method="post" onsubmit="return confirm('Deze foto verwijderen?')">
                        <input type="hidden" name="verwijder" value="<?= (int)$foto["id"] ?>">
                        <button type="submit" class="del">Verwijderen</button>
                    </form>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>

    </div>
</main>

<footer class="footer">
    <div class="footer-grid">
        <div>
            <span class="footer-logo">Qamera</span>
            <p>Alle foto's van je feest op één plek.</p>
        </div>
        <nav>
            <h3>Pagina's</h3>
            <a href="index.php">Home</a>
            <a href="index.php#stappen">Hoe het werkt</a>
            <a href="maak.php">Event maken</a>
        </nav>
    </div>
    <div class="footer-onder">
        &copy; <?= date('Y') ?> Qamera &middot; created by Quinten Van Camp
    </div>
</footer>

<?php if ($isAdmin): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const gastLink = <?= json_encode($gastLink) ?>;

new QRCode(document.getElementById("qr"), {
    text: gastLink,
    width: 220,
    height: 220,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
});

document.getElementById("kopieer").onclick = function () {
    navigator.clipboard.writeText(gastLink);
    this.textContent = "Gekopieerd";
};
</script>
<?php endif; ?>

</body>
</html>