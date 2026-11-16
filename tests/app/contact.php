<?php
//enquiry dialogs - checked by core/contact_request.test.php
include_once("pages/SparkPage.php");
include_once("responders/json/ContactRequestFormResponder.php");
include_once("dialogs/json/JSONFormDialog.php");

//an enquiry about one item (?about=<id>)
class ItemQueryFormResponder extends ContactRequestFormResponder
{
}

//a mail transport that fails
class FailingContactMailer extends ContactRequestMailer
{
    public function send() : bool
    {
        return false;
    }
}
class FailingContactFormResponder extends ContactRequestFormResponder
{
    protected function createMailer() : ContactRequestMailer
    {
        return new FailingContactMailer($this->recipient());
    }
}

$page = new SparkPage();
new ContactRequestFormResponder();
new FailingContactFormResponder();
if (isset($_GET["about"])) {
    $about = new ItemQueryFormResponder();
    $about->setAbout("Item " . (int)$_GET["about"], new URL("/item.php?id=" . (int)$_GET["about"]), array("Code" => (string)(int)$_GET["about"]));
}
new JSONFormDialog();
$page->startRender();
$page->finishRender();
