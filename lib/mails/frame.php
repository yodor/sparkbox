<?php
/**
 * The frame of every mail (MailView::Frame): the logo or the site title, the content, the footer.
 * A table 600px wide with inline styles - what mail clients show alike. A site may have its own copy (class/mails).
 * @var MailView $this
 */
$accent = $this->accent();
$header = $this->headerBackground();
$logo = $this->logoURL();
$logoWidth = $this->logoWidth();
$site = $this->siteTitle();
$url = $this->siteURL();
$lang = $this->e(Translator::Instance()->activeCodeISO2());
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title><?php echo $this->e($this->title); ?></title>
</head>
<body style="margin:0;padding:0;background:#f2f4f6;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;"><?php echo $this->e($this->title); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f2f4f6;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:6px;border-top:4px solid <?php echo $accent; ?>;">
<tr><td style="padding:20px 32px;background:<?php echo $header; ?>;">
<a href="<?php echo $this->e($url); ?>" style="text-decoration:none;color:<?php echo $accent; ?>;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:bold;">
<?php if ($logo !== "") { ?><img src="<?php echo $this->e($logo); ?>" alt="<?php echo $this->e($site); ?>" width="<?php echo $logoWidth; ?>" style="display:block;width:<?php echo $logoWidth; ?>px;max-width:100%;height:auto;border:0;"><?php } else { echo $this->e($site); } ?>
</a>
</td></tr>
<tr><td style="padding:24px 32px 8px 32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#222222;">
<?php echo $this->content; ?>
</td></tr>
<tr><td style="padding:16px 32px 24px 32px;border-top:1px solid #e5e8eb;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#6b7280;">
<?php echo $this->e($site); ?> &middot; <a href="<?php echo $this->e($url); ?>" style="color:#6b7280;"><?php echo $this->e(preg_replace("#^https?://#", "", rtrim($url, "/"))); ?></a>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
