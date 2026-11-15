<?php
/**
 * Input normalisation (Spark::SanitizeInput, used by InputProcessor::loadPostData) and output escaping
 * (Component::setText)
 */
include_once("components/Component.php");

test("ordinary text is kept as typed - no SQL word filtering", function () {
    foreach (array("Where the Crawdads Sing", "Order by phone after 18:00 -- thanks", "Тайната на Union Square",
                 "Select Poems 1950-1960", "Колекция 0x1F", "x < 5 and y > 3", "Tom & Jerry \"special\"") as $text) {
        assertSame($text, Spark::SanitizeInput($text));
    }
});

test("plain fields lose tags, HTML fields keep allowed tags only", function () {
    //strip_tags keeps the text between tags - harmless as text
    assertSame("Helloalert(1)", Spark::SanitizeInput("<b>Hello</b><script>alert(1)</script>"));
    $html = Spark::SanitizeInput("<p>Hello <b>world</b></p><script>alert(1)</script>", true);
    assertTrue(str_contains($html, "<b>world</b>"), $html);
    assertTrue(!str_contains($html, "<script"), $html);
});

test("Component::setText escapes, setContents keeps HTML", function () {
    $cmp = new Component(false);
    $cmp->setText("<img src=x onerror=alert(1)> & \"q\"");
    assertSame("&lt;img src=x onerror=alert(1)&gt; &amp; &quot;q&quot;", $cmp->getContents());
    $cmp->setContents("<b>bold</b>");
    assertSame("<b>bold</b>", $cmp->getContents());
});
