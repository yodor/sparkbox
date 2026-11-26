<?php
/**
 * A visitor's enquiry - to the owner (ContactRequestMailer): values (label => value), about_title, about_url,
 * about_details (label => value), reply (the visitor gave an e-mail - Reply-To)
 * @var MailView $this
 */
echo $this->text(tr("An enquiry was sent from %s.", $this->siteTitle()));
$rows = array();
foreach ((array)$this->get("values") as $label => $value) {
    $rows[(string)$label] = nl2br($this->e($value), false);
}
echo $this->details($rows);
if ($this->get("about_url") !== "") {
    echo $this->heading(tr("About"));
    $about = array(tr("Product") => $this->link((string)$this->get("about_url"), (string)$this->get("about_title")));
    foreach ((array)$this->get("about_details") as $label => $value) $about[(string)$label] = $this->e($value);
    echo $this->details($about);
}
if ($this->get("reply")) {
    echo $this->paragraph("<span style='color:#6b7280;'>" . $this->e(tr("Reply to this mail to write to the client.")) . "</span>");
}
