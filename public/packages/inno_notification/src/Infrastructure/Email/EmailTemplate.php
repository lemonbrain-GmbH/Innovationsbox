<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Infrastructure\Email;

class EmailTemplate
{
    public static function linkButton(string $url, string $label): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<p style="margin:24px 0 0;"><a href="' . $safeUrl . '" style="display:inline-block;padding:10px 24px;background:#B7CD57;color:#2d2d2d;text-decoration:none;border-radius:6px;font-weight:600;font-size:15px;">' . $safeLabel . '</a></p>';
    }

    public static function wrap(string $heading, string $bodyHtml): string
    {
        $safeHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$safeHeading}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;">
<tr><td align="center" style="padding:24px 16px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#B7CD57;padding:20px 32px;">
      <span style="font-size:20px;font-weight:700;color:#2d2d2d;">Innovationsbox</span>
    </td></tr>
    <tr><td style="padding:32px;">
      <h1 style="margin:0 0 16px;font-size:22px;color:#2d2d2d;">{$safeHeading}</h1>
      <div style="font-size:15px;line-height:1.6;color:#4a4a4a;">
        {$bodyHtml}
      </div>
    </td></tr>
    <tr><td style="padding:20px 32px;background:#fafafa;border-top:1px solid #e8e8e8;">
      <p style="margin:0;font-size:12px;color:#999;">
        Diese E-Mail wurde automatisch von der Innovationsbox gesendet.<br>
        Du erhältst diese Nachricht, weil du auf der Plattform registriert bist.
      </p>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
