<?php
/**
 * HTML email for a new website inquiry.
 *
 * Expects (already HTML-escaped): $safeName, $safeEmail, $safeMessage, $replyUrl, $sentAt
 */
if (!isset($safeName, $safeEmail, $safeMessage, $replyUrl, $sentAt)) {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="dark" />
    <meta name="supported-color-schemes" content="dark" />
    <title>New inquiry from <?= $safeName ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Jost:wght@300;400&display=swap');

        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .px { padding-left: 24px !important; padding-right: 24px !important; }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#0c1e15;">
    <!-- Preheader (inbox preview text) -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">
        <?= $safeName ?> (<?= $safeEmail ?>) sent an inquiry via allzerve.com
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0c1e15;">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#0f2419; border:1px solid #3a3a26;">

                    <!-- Header -->
                    <tr>
                        <td class="px" align="center" style="padding:40px 48px 28px; border-bottom:1px solid #3a3a26;">
                            <div style="font-family:'Cormorant Garamond', Georgia, 'Times New Roman', serif; font-size:30px; font-weight:500; letter-spacing:0.28em; color:#c9a961;">
                                ALLZERVE
                            </div>
                            <div style="padding-top:10px; font-family:'Jost', Helvetica, Arial, sans-serif; font-size:11px; letter-spacing:0.2em; color:#8e9b93;">
                                NEW WEBSITE INQUIRY
                            </div>
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td class="px" style="padding:36px 48px 8px;">
                            <h1 style="margin:0; font-family:'Cormorant Garamond', Georgia, 'Times New Roman', serif; font-size:26px; font-weight:500; line-height:1.3; color:#e7e4da;">
                                You have a new message from <?= $safeName ?>
                            </h1>
                            <p style="margin:12px 0 0; font-family:'Jost', Helvetica, Arial, sans-serif; font-size:13px; font-weight:300; color:#8e9b93;">
                                Received <?= $sentAt ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Details -->
                    <tr>
                        <td class="px" style="padding:28px 48px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding:0 0 18px; border-bottom:1px solid #3a3a26;">
                                        <div style="font-family:'Jost', Helvetica, Arial, sans-serif; font-size:11px; letter-spacing:0.14em; color:#8e9b93; padding-bottom:6px;">NAME</div>
                                        <div style="font-family:'Jost', Helvetica, Arial, sans-serif; font-size:16px; font-weight:400; color:#e7e4da;"><?= $safeName ?></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 0; border-bottom:1px solid #3a3a26;">
                                        <div style="font-family:'Jost', Helvetica, Arial, sans-serif; font-size:11px; letter-spacing:0.14em; color:#8e9b93; padding-bottom:6px;">EMAIL</div>
                                        <a href="mailto:<?= $safeEmail ?>" style="font-family:'Jost', Helvetica, Arial, sans-serif; font-size:16px; color:#c9a961; text-decoration:none;"><?= $safeEmail ?></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 0 0;">
                                        <div style="font-family:'Jost', Helvetica, Arial, sans-serif; font-size:11px; letter-spacing:0.14em; color:#8e9b93; padding-bottom:10px;">MESSAGE</div>
                                        <div style="font-family:'Jost', Helvetica, Arial, sans-serif; font-size:15px; font-weight:300; line-height:1.7; color:#e7e4da; padding:18px 20px; background-color:#0c1e15; border-left:2px solid #c9a961;">
                                            <?= $safeMessage ?>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="px" align="center" style="padding:24px 48px 32px; border-top:1px solid #3a3a26;">
                            <p style="margin:0; font-family:'Jost', Helvetica, Arial, sans-serif; font-size:11px; letter-spacing:0.14em; color:#8e9b93;">
                                ALLZERVE TECHNOLOGY CO., LTD
                            </p>
                            <p style="margin:8px 0 0; font-family:'Jost', Helvetica, Arial, sans-serif; font-size:12px; font-weight:300; color:#5c6b62;">
                                This message was sent from the contact form on the ALLZERVE website.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>

</html>
