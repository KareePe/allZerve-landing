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
    . '&family=Noto+Sans+Thai:wght@300;400'
    . '&display=swap';

$siteUrl = 'https://allzerve.com/';
$pageTitle = 'ALLZERVE Technology | M&A Advisory Thailand · ที่ปรึกษา M&A ซื้อขายกิจการ';
$pageDescription = 'ALLZERVE Technology Thailand: confidential M&A advisory, business sales, factories for sale, '
    . 'valuation, sale and leaseback, and debt restructuring. ที่ปรึกษา M&A ซื้อขายกิจการ ขายโรงงาน ขายฝาก จัดหาเงินทุน';
$ogImage = $siteUrl . 'assets/og-image.png';
$ogImageAlt = 'ALLZERVE: Capital, Real Estate, M&A & Investment';

// Rendered in the Services section and in the JSON-LD, so both always match.
$services = [
    ['M&A Advisory Thailand', 'ที่ปรึกษา M&A · ควบรวมกิจการ',
        'Buy-side and sell-side advice on mergers and acquisitions, run in strict confidence.'],
    ['Sell a Business in Thailand', 'ขายบริษัท · ซื้อขายกิจการ',
        'We find qualified buyers, negotiate terms and manage the sale through to closing.'],
    ['Factory for Sale Thailand', 'ขายโรงงาน · ขายเครื่องจักรมือสอง',
        'We sell factories, industrial land and used machinery to investors and operators.'],
    ['Business Valuation Thailand', 'ประเมินมูลค่าบริษัท',
        'Independent valuations to support pricing, negotiations and investment decisions.'],
    ['Sale and Leaseback Thailand', 'ขายฝากโรงงาน · ขายฝากที่ดิน',
        'Release cash tied up in a factory or land and keep operating from the same site.'],
    ['Capital Raising and Financing', 'จัดหาเงินทุน · จำนอง · ขายฝาก',
        'Equity, mortgage and asset-backed funding arranged to fit your timeline.'],
    ['Debt Restructuring Thailand', 'ปรับโครงสร้างหนี้',
        'We negotiate with lenders to reshape obligations and protect the business.'],
    ['Business Closure and Liquidation', 'ปิดกิจการ · ชำระบัญชี',
        'We handle an orderly wind-down, asset disposal and liquidation from start to finish.'],
];

// Rendered as the visible FAQ and as FAQPage JSON-LD. Answer engines quote
// short, self-contained answers, so each one opens with the direct answer.
$faqs = [
    [
        'en',
        'What does ALLZERVE do?',
        'ALLZERVE Technology Co., Ltd. is a Thailand-based advisory firm for capital, real estate, and M&A. '
            . 'It helps owners sell a business or factory, value a company, arrange sale and leaseback or financing, '
            . 'restructure debt, and close or liquidate a company, in strict confidence.',
    ],
    [
        'en',
        'How can I sell my business confidentially in Thailand?',
        'Work with a confidential M&A advisor instead of listing the business publicly. ALLZERVE presents the '
            . 'opportunity only to qualified buyers and shares identifying details only with the owner\'s approval.',
    ],
    [
        'th',
        'บริษัท ออลล์เซิร์ฟ เทคโนโลยี จำกัด ให้บริการอะไรบ้าง',
        'ออลล์เซิร์ฟ เป็นที่ปรึกษา M&A และที่ปรึกษาซื้อขายกิจการในประเทศไทย ให้บริการขายบริษัท ขายโรงงาน '
            . 'ประเมินมูลค่าบริษัท ขายฝากโรงงานและที่ดิน จำนอง จัดหาเงินทุน ปรับโครงสร้างหนี้ ปิดกิจการและชำระบัญชี '
            . 'รวมถึงขายเครื่องจักรมือสอง โดยดำเนินการเป็นความลับ',
    ],
    [
        'th',
        'อยากขายบริษัทหรือขายโรงงานโดยไม่ให้คนอื่นรู้ ต้องทำอย่างไร',
        'ควรขายผ่านที่ปรึกษาซื้อขายกิจการแทนการประกาศขายทั่วไป ออลล์เซิร์ฟจะนำเสนอเฉพาะผู้ซื้อที่ผ่านการคัดกรอง '
            . 'และเปิดเผยข้อมูลที่ระบุตัวตนของกิจการเมื่อเจ้าของอนุญาตเท่านั้น',
    ],
    [
        'th',
        'ขายฝากกับจำนองต่างกันอย่างไร',
        'ขายฝากคือการโอนกรรมสิทธิ์ทรัพย์สินให้ผู้ซื้อฝาก โดยผู้ขายมีสิทธิไถ่คืนภายในเวลาที่ตกลง '
            . '(อสังหาริมทรัพย์ไม่เกิน 10 ปี) ส่วนจำนองคือการใช้ทรัพย์สินเป็นหลักประกันหนี้โดยกรรมสิทธิ์ยังเป็นของเจ้าของ '
            . 'ขายฝากมักได้วงเงินเร็วกว่า แต่หากไม่ไถ่คืนตามกำหนดจะเสียกรรมสิทธิ์ทันที',
    ],
    [
        'th',
        'ประเมินมูลค่าบริษัทก่อนขายกิจการทำอย่างไร',
        'การประเมินมูลค่าบริษัทพิจารณาจากผลประกอบการและกระแสเงินสด มูลค่าทรัพย์สิน เช่น ที่ดิน โรงงาน และเครื่องจักร '
            . 'รวมถึงราคาซื้อขายของกิจการที่ใกล้เคียงกัน เพื่อกำหนดราคาขายที่สมเหตุสมผลและใช้ต่อรองกับผู้ซื้อ',
    ],
    [
        'th',
        'บริษัทมีหนี้มาก ควรปรับโครงสร้างหนี้หรือปิดกิจการ',
        'ขึ้นอยู่กับว่าธุรกิจยังสร้างกระแสเงินสดได้หรือไม่ หากยังไปต่อได้ การปรับโครงสร้างหนี้กับเจ้าหนี้ช่วยลดภาระและรักษากิจการไว้ '
            . 'หากไปต่อไม่ได้ การปิดกิจการและชำระบัญชีอย่างเป็นระบบ หรือขายกิจการ/ทรัพย์สิน จะช่วยรักษามูลค่าที่เหลือได้มากที่สุด',
    ],
    [
        'th',
        'ติดต่อออลล์เซิร์ฟได้อย่างไร',
        'กรอกแบบฟอร์มบนเว็บไซต์ allzerve.com หรืออีเมลถึง sarayut.k@allzerve.com ทีมงานจะตอบกลับภายใน 1 วันทำการ',
    ],
];

$structuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'ProfessionalService',
            '@id' => $siteUrl . '#organization',
            'name' => 'ALLZERVE Technology Co., Ltd.',
            'alternateName' => ['ALLZERVE', 'ALLZERVE Technology Thailand', 'บริษัท ออลล์เซิร์ฟ เทคโนโลยี จำกัด'],
            'url' => $siteUrl,
            'image' => $ogImage,
            'description' => $pageDescription,
            'email' => 'sarayut.k@allzerve.com',
            'areaServed' => ['@type' => 'Country', 'name' => 'Thailand'],
            'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'TH'],
            'knowsLanguage' => ['en', 'th'],
            'founder' => [
                '@type' => 'Person',
                'name' => 'Sarayut Kornrittidet',
                'jobTitle' => 'Chief Executive Officer',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'email' => 'sarayut.k@allzerve.com',
                'areaServed' => 'TH',
                'availableLanguage' => ['English', 'Thai'],
            ],
            'knowsAbout' => [
                'Mergers and acquisitions', 'Confidential M&A advisor', 'Business valuation',
                'Sale and leaseback', 'Debt restructuring', 'Liquidation', 'Real estate', 'Capital raising',
            ],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Services',
                'itemListElement' => array_map(static fn(array $s) => [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $s[0],
                        'alternateName' => $s[1],
                        'description' => $s[2],
                        'areaServed' => 'TH',
                    ],
                ], $services),
            ],
        ],
        [
            '@type' => 'FAQPage',
            '@id' => $siteUrl . '#faq',
            'about' => ['@id' => $siteUrl . '#organization'],
            'mainEntity' => array_map(static fn(array $f) => [
                '@type' => 'Question',
                'inLanguage' => $f[0],
                'name' => $f[1],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[2]],
            ], $faqs),
        ],
        [
            '@type' => 'WebSite',
            '@id' => $siteUrl . '#website',
            'url' => $siteUrl,
            'name' => 'ALLZERVE',
            'inLanguage' => ['en', 'th'],
            'publisher' => ['@id' => $siteUrl . '#organization'],
        ],
    ],
];

$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#0c1e15" />
    <title><?= $e($pageTitle) ?></title>
    <meta name="description" content="<?= $e($pageDescription) ?>" />
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large" />
    <link rel="canonical" href="<?= $e($siteUrl) ?>" />
    <meta name="google-site-verification" content="T2ENO0oH-Pyy02bSBBTMz6WPbtpwWfwy_eOBcUnzxVk" />
    <link rel="icon" href="/assets/favicon.ico" sizes="64x64" />

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="ALLZERVE" />
    <meta property="og:url" content="<?= $e($siteUrl) ?>" />
    <meta property="og:title" content="<?= $e($pageTitle) ?>" />
    <meta property="og:description" content="<?= $e($pageDescription) ?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:locale:alternate" content="th_TH" />
    <meta property="og:image" content="<?= $e($ogImage) ?>" />
    <meta property="og:image:type" content="image/png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="720" />
    <meta property="og:image:alt" content="<?= $e($ogImageAlt) ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= $e($pageTitle) ?>" />
    <meta name="twitter:description" content="<?= $e($pageDescription) ?>" />
    <meta name="twitter:image" content="<?= $e($ogImage) ?>" />
    <meta name="twitter:image:alt" content="<?= $e($ogImageAlt) ?>" />

    <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR) ?></script>

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
            <nav>
                <a class="nav-link" href="#services">Services</a>
                <a class="nav-link" href="#faq">FAQ</a>
                <a class="nav-link" href="#contact">Contact</a>
            </nav>
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

        <section class="services-section" id="services" aria-labelledby="services-title">
            <h2 id="services-title">Services</h2>
            <p class="intro">
                ALLZERVE Technology Co., Ltd. is a confidential M&amp;A advisor in Thailand for owners who want to sell a business,
                sell a factory, raise capital or restructure debt, without the market knowing.
            </p>
            <p class="intro" lang="th">
                บริษัท ออลล์เซิร์ฟ เทคโนโลยี จำกัด ที่ปรึกษาซื้อขายกิจการและควบรวมกิจการ
                ดูแลการขายบริษัท ขายโรงงาน ขายฝาก จำนอง และจัดหาเงินทุนอย่างเป็นความลับ
            </p>

            <ul class="service-list">
                <?php foreach ($services as [$name, $nameTh, $summary]): ?>
                    <li>
                        <h3><?= $e($name) ?></h3>
                        <p class="th" lang="th"><?= $e($nameTh) ?></p>
                        <p><?= $e($summary) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="faq-section" id="faq" aria-labelledby="faq-title">
            <h2 id="faq-title">Questions</h2>
            <dl class="faq-list">
                <?php foreach ($faqs as [$lang, $question, $answer]): ?>
                    <div lang="<?= $e($lang) ?>">
                        <dt><?= $e($question) ?></dt>
                        <dd><?= $e($answer) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
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
            <p lang="th">บริษัท ออลล์เซิร์ฟ เทคโนโลยี จำกัด</p>
            <p><a href="mailto:sarayut.k@allzerve.com">sarayut.k@allzerve.com </a></p>
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
