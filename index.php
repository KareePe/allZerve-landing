<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ALLZERVE — Capital, Real Estate, M&A &amp; Investment</title>
    <link rel="stylesheet" href="assets/style.css" />
</head>

<body>

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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>" />
            <div class="hp" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" />
            </div>
            <div class="field">
                <label for="name">NAME</label>
                <input type="text" id="name" name="name" required maxlength="100" placeholder="Your full name" />
            </div>
            <div class="field">
                <label for="email">EMAIL</label>
                <input type="email" id="email" name="email" required placeholder="you@company.com" />
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
            <p><a href="mailto:sarayut.k@allzerve.com">sarayut.k@allzerve.com</a></p>
        </footer>
    </div>

    <script>
        const form = document.getElementById('contact-form');
        const statusEl = form.querySelector('.form-status');
        const button = form.querySelector('button.submit');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            button.disabled = true;
            button.textContent = 'SENDING...';
            statusEl.className = 'form-status';
            statusEl.textContent = '';

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                statusEl.textContent = data.message;
                statusEl.classList.add(data.ok ? 'is-success' : 'is-error');
                if (data.ok) form.reset();
            } catch (err) {
                statusEl.textContent = 'Network error. Please try again.';
                statusEl.classList.add('is-error');
            } finally {
                button.disabled = false;
                button.textContent = 'SEND INQUIRY';
            }
        });
    </script>

</body>

</html>