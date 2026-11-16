<?php
/**
 * ContactRequestFormResponder: enquiries passed through to the owner by e-mail (nothing stored)
 */
include_once(__DIR__ . "/../harness/http.php");

function enquiry(string $responder, array $post = array(), string $extra = "") : array
{
    $function = count($post) > 0 ? "submit" : "render";
    $options = count($post) > 0 ? array("post" => $post + array("SubmitForm" => "ContactRequestForm")) : array();
    [$status, , $body] = httpRequest("contact.php", "$extra&JSONRequest=1&responder=$responder&function=$function", array(), "", $options);
    assertSame(200, $status, "status");
    $json = json_decode($body, true);
    assertTrue(is_array($json), "not JSON: " . substr($body, 0, 300));
    return $json;
}

function lastMail() : string
{
    $log = @file_get_contents(TestMailLog());
    if ($log === false || $log === "") return "";
    $parts = preg_split("/^(?=To: )/m", $log);
    return (string)end($parts);
}

function mailCount() : int
{
    return preg_match_all("/^Subject: /m", (string)@file_get_contents(TestMailLog()));
}

test("the dialog renders the form, no extra fields", function () {
    $json = enquiry("ContactRequestFormResponder");
    assertSame("OK", $json["status"], $json["message"] ?? "");
    assertTrue(str_contains($json["contents"], "name='ContactRequestForm'"), "form missing");
    assertTrue(!str_contains($json["contents"], "field='website'"), "spam trap field shown");
});

test("missing values: the form comes back with errors, no mail", function () {
    $before = mailCount();
    $json = enquiry("ContactRequestFormResponder", array("fullname" => "", "email" => "", "query" => ""));
    assertTrue(str_contains($json["contents"] ?? "", "ContactRequestForm"), "form not returned");
    assertSame($before, mailCount(), "mail sent");
});

test("an optional field with a typo is an error, not dropped", function () {
    $json = enquiry("ContactRequestFormResponder", array("fullname" => "A", "email" => "a@example.com", "phone" => "", "query" => "q"));
    assertSame("", $json["contents"] ?? "", "empty optional phone rejected");
    include_once("input/renderers/Input.php");
    include_once("forms/InputForm.php");
    include_once("input/DataInputFactory.php");
    $form = new InputForm();
    $form->addInput(DataInputFactory::Create(InputType::EMAIL, "email", "Email", 0));
    $form->getInput("email")->setValue("not-an-address");
    $form->getInput("email")->validate();
    assertTrue($form->getInput("email")->haveError(), "typo in an optional e-mail dropped without an error");
});

test("accepted enquiry: mailed to the owner, Reply-To the visitor, text escaped, nothing stored", function () {
    TestPDO()->exec("DROP TABLE IF EXISTS contact_requests");
    $before = mailCount();
    $json = enquiry("ContactRequestFormResponder", array("fullname" => "Иван Петров", "email" => "ivan@example.com",
        "phone" => "0888 1", "query" => "Where is <b>it</b>?\nThanks"));
    assertSame("OK", $json["status"], $json["message"] ?? "");
    assertSame("", $json["contents"] ?? "", "form returned");
    assertSame($before + 1, mailCount(), "no mail");
    $mail = lastMail();
    assertTrue(str_contains($mail, "Reply-To:") && str_contains($mail, "ivan@example.com"), "Reply-To missing");
    assertTrue(str_starts_with($mail, "To: info@localhost"), "recipient: the service address");
    assertTrue(!str_contains($mail, "<b>it</b>"), "visitor text not escaped");
    assertSame(1, substr_count(strtolower($mail), "<html"), "template applied twice");
    assertTrue(!(bool)TestPDO()->query("SHOW TABLES LIKE 'contact_requests'")->fetch(), "a contact_requests table was created");
});

test("enquiry about an item: the item is linked with its details", function () {
    $json = enquiry("ItemQueryFormResponder", array("fullname" => "A", "email" => "a@example.com", "query" => "q"), "about=42");
    assertSame("OK", $json["status"], $json["message"] ?? "");
    $mail = lastMail();
    assertTrue(str_contains($mail, "item.php?id=42") && str_contains($mail, "Item 42"), "item link missing");
});

test("a mail that cannot be sent is an error for the visitor, the form stays", function () {
    $json = enquiry("FailingContactFormResponder", array("fullname" => "A", "email" => "a@example.com", "query" => "q"));
    assertSame("error", $json["status"] ?? "", "status: " . json_encode($json));
    assertTrue(str_contains($json["message"] ?? "", "опитайте отново"), "message: " . ($json["message"] ?? ""));
});
