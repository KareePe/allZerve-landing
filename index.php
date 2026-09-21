<?php

declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? 'off') !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
]);
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
// Release the session lock before rendering: nothing below touches $_SESSION.
session_write_close();

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: private, no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// One URL for both the preload hint and the stylesheet itself.
$fontsUrl = 'https://fonts.googleapis.com/css2'
    . '?family=Cormorant+Garamond:ital,wght@0,500;1,400'
    . '&family=Jost:wght@300;400'
    . '&display=swap';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#0c1e15" />
    <title>ALLZERVE — Capital, Real Estate, M&amp;A &amp; Investment</title>
    <meta name="description" content="ALLZERVE structures capital, real estate, and strategic M&amp;A transactions for clients who value discretion over noise." />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preload" as="style" href="<?= htmlspecialchars($fontsUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars($fontsUrl, ENT_QUOTES, 'UTF-8') ?>" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="<?= htmlspecialchars($fontsUrl, ENT_QUOTES, 'UTF-8') ?>" />
    </noscript>

    <!-- Inlined: the whole sheet is ~4 KB, so an extra round trip costs more than the bytes. -->
    <style><?php readfile(__DIR__ . '/assets/style.css'); ?></style>
</head>

<body>

    <div class="preloader" id="preloader" role="status" aria-label="Loading">
        <div class="preloader__logo">ALLZERVE</div>
        <div class="preloader__bar"><span></span></div>
    </div>
    <noscript>
        <style>
            .preloader {
                display: none;
            }
        </style>
    </noscript>
    <script>
        // Runs before the page paints, so the overlay is never seen "arriving".
        (function () {
            var root = document.documentElement;
            root.classList.add('is-loading');

            var hidden = false;
            function hide() {
                if (hidden) return;
                hidden = true;
                root.classList.remove('is-loading');
                var el = document.getElementById('preloader');
                if (!el) return;
                el.classList.add('is-hidden');
                setTimeout(function () { el.remove(); }, 600);
            }

            function onLoad() {
                return new Promise(function (resolve) {
                    if (document.readyState === 'complete') resolve();
                    else addEventListener('load', resolve, { once: true });
                });
            }

            var fonts = (document.fonts && document.fonts.ready) || Promise.resolve();
            // A short floor keeps the overlay from flashing on instant loads.
            var floor = new Promise(function (r) { setTimeout(r, 400); });

            Promise.all([onLoad(), fonts, floor]).then(hide);
            // Safety net: never trap the page behind a stalled font or asset.
            setTimeout(hide, 3000);
        })();
    </script>

    <div class="wrap">
        <header>
            <div class="logo">ALLZERVE</div>
            <a class="nav-link" href="#contact">Contact</a>
        </header>

        <section class="hero">
            <h1>ALLZERVE</h1>
            <div class="services">
                Capital<br>
                Real Estate<br>
                M&amp;A &amp; Investment
            </div>
        </section>

        <hr class="divider" />

        <section class="quote">
            <p>&ldquo;We structure capital, real estate, and strategic transactions for clients who value discretion over noise.&rdquo;</p>
            <cite>SARAYUT KORNRITTIDET — CHIEF EXECUTIVE OFFICER</cite>
        </section>

        <section class="contact-section" id="contact">
            <h2>Get in Touch</h2>
            <p>Share a few details about your objective. We reply within one business day.</p>
        </section>

        <form id="contact-form" action="send.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="hp" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" />
            </div>
            <div class="field">
                <label for="name">NAME</label>
                <input type="text" id="name" name="name" required maxlength="100" autocomplete="name" placeholder="Your full name" />
            </div>
            <div class="field">
                <label for="email">EMAIL</label>
                <input type="email" id="email" name="email" required maxlength="254" autocomplete="email" placeholder="you@company.com" />
            </div>
            <div class="field">
                <label for="message">MESSAGE</label>
                <textarea id="message" name="message" rows="2" required maxlength="5000" placeholder="Briefly describe what you're looking to achieve"></textarea>
            </div>
            <div class="submit-row">
                <button type="submit" class="submit">SEND INQUIRY</button>
                <p class="form-status" role="status" aria-live="polite"></p>
            </div>
        </form>

        <footer>
            <p>ALLZERVE TECHNOLOGY CO., LTD</p>
            <p><a href="mailto:sarayut.ceo@gmail.com">sarayut.ceo@gmail.com</a></p>
        </footer>
    </div>

    <script>
        (function () {
            var form = document.getElementById('contact-form');
            var statusEl = form.querySelector('.form-status');
            var button = form.querySelector('button.submit');
            var sending = false;

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (sending) return;
                sending = true;
                button.disabled = true;
                button.textContent = 'SENDING...';
                statusEl.className = 'form-status';
                statusEl.textContent = '';

                // Don't leave the button stuck if the network never answers.
                var abort = new AbortController();
                var timer = setTimeout(function () { abort.abort(); }, 20000);

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: abort.signal
                    });
                    const data = await res.json();
                    statusEl.textContent = data.message;
                    statusEl.classList.add(data.ok ? 'is-success' : 'is-error');
                    if (data.ok) form.reset();
                } catch (err) {
                    statusEl.textContent = err.name === 'AbortError'
                        ? 'The request timed out. Please try again.'
                        : 'Network error. Please try again.';
                    statusEl.classList.add('is-error');
                } finally {
                    clearTimeout(timer);
                    sending = false;
                    button.disabled = false;
                    button.textContent = 'SEND INQUIRY';
                }
            });
        })();
    </script>

</body>

</html>
