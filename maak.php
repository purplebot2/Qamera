<?php
require "db.php";

$fout = "";
$toegelaten = ["teal", "zon", "roos", "nacht"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $naam  = trim($_POST["naam"] ?? "");
    $thema = $_POST["thema"] ?? "";

    if ($naam === "" || mb_strlen($naam) > 80) {
        $fout = "Geef je event een naam van maximaal 80 tekens.";
    } elseif (!in_array($thema, $toegelaten, true)) {
        $fout = "Kies een thema.";
    } else {
        $id  = bin2hex(random_bytes(6));
        $key = bin2hex(random_bytes(8));

        $stmt = $pdo->prepare(
            "INSERT INTO events (id, naam, thema, admin_key) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$id, $naam, $thema, $key]);

        header("Location: event.php?e=$id&key=$key");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event maken - Qamera</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <a href="index.php" class="logo">Qamera</a>
    <a href="maak.php">Event maken</a>
</header>

<main>
    <section class="hero2">
        <h1>Maak je event.</h1>
        <p>Geef je feest een naam en kies een thema.</p>
    </section>

    <div class="maak-layout">

        <section class="form">
            <?php if ($fout !== ""): ?>
                <p class="fout"><?= htmlspecialchars($fout) ?></p>
            <?php endif; ?>

            <form method="post" id="formp" name="formp" action="">
                <label for="naam" class="form-label">Naam van event:</label>
                <input type="text" id="naam" name="naam" placeholder="Verjaardag van ..." maxlength="80" required>

                <p class="form-label">Thema</p>

                <div class="thema-container">

                    <label class="thema-kaart">
                        <input type="radio" name="thema" value="teal" checked>
                        <div class="kleurenbalk teal-balk"></div>
                        <span class="label-tekst">Teal</span>
                    </label>

                    <label class="thema-kaart">
                        <input type="radio" name="thema" value="zon">
                        <div class="kleurenbalk zon-balk"></div>
                        <span class="label-tekst">Zon</span>
                    </label>

                    <label class="thema-kaart">
                        <input type="radio" name="thema" value="roos">
                        <div class="kleurenbalk roos-balk"></div>
                        <span class="label-tekst">Roos</span>
                    </label>

                    <label class="thema-kaart">
                        <input type="radio" name="thema" value="nacht">
                        <div class="kleurenbalk nacht-balk"></div>
                        <span class="label-tekst">Nacht</span>
                    </label>

                </div>

                <button type="submit" class="submit-knop">Event Opslaan</button>
            </form>
        </section>

        <aside class="zijkant">
            <div class="uitleg">
                <h2>Wat gebeurt er daarna?</h2>
                <p><span>01</span> Je krijgt een link en een QR-code.</p>
                <p><span>02</span> Gasten scannen hem en uploaden hun foto's en video's.</p>
                <p><span>03</span> Jij downloadt alles na het feest.</p>
            </div>

            <div class="tip">
                <strong>Tip</strong>
                <p>Bewaar de organisator-link goed. Alleen daarmee kan je foto's verwijderen en alles downloaden.</p>
            </div>
        </aside>

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

</body>
</html>
