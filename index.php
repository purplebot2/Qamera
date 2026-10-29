<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Qamera</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <a href="index.php" class="logo">Qamera</a>
    <a href="maak.php">Event maken</a>
</header>

<main>
    <section class="hero hero-grid">
        <div>
            <h1>Alle foto's van je feest op één plek.</h1>
            <p>Maak een event, laat gasten de QR-code scannen en verzamel alle foto's en video's in één gedeelde galerij.</p>
            <a href="maak.php" class="knop">Maak je event</a>
        </div>

        <div class="fotostapel" aria-hidden="true">
            <div class="foto foto1"></div>
            <div class="foto foto2"></div>
            <div class="foto foto3"></div>
        </div>
    </section>

    <section class="stappen" id="stappen">
        <div class="stap">
            <div class="stap-inhoud">
                <span class="nummer">01</span>
                <h2>Maak je event</h2>
                <p>Kies een naam en een thema.</p>
            </div>
        </div>
        <div class="stap">
            <div class="stap-inhoud">
                <span class="nummer">02</span>
                <h2>Deel de QR-code</h2>
                <p>Hang hem op of stuur de link.</p>
            </div>
        </div>
        <div class="stap">
            <div class="stap-inhoud">
                <span class="nummer">03</span>
                <h2>Verzamel alles</h2>
                <p>Download de foto's als zip.</p>
            </div>
        </div>
    </section>
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
