<?php
/**
 * OptionFinder: the markup its script reads - the query field, the hidden field of the key, the chosen option, the
 * list, the source and the hints; the chosen option escaped
 */
include_once("components/Component.php");
include_once("components/OptionFinder.php");
include_once("utils/url/URL.php");

test("the finder: query, hidden key, list, source, hints; nothing chosen", function () {
    $finder = new OptionFinder("office_key");
    $finder->setSource(new URL("/checkout/delivery/courier?responder=X&function=list"));
    $finder->setPlaceholder("Town");
    $finder->setHints("Type", "None");
    ob_start();
    $finder->render();
    $html = ob_get_clean();
    assertTrue(str_contains($html, "name='office_key'") && str_contains($html, "type='hidden'"), "the hidden key");
    assertTrue(str_contains($html, "class='query'") && str_contains($html, "placeholder='Town'"), "the query");
    assertTrue(str_contains($html, "source='/checkout/delivery/courier?responder=X&amp;function=list'"), "the source");
    assertTrue(str_contains($html, "hint-empty='Type'") && str_contains($html, "hint-none='None'"), "the hints");
    assertTrue((bool)preg_match("#class='chosen'[^>]*hidden#", $html), "nothing chosen: hidden");
    assertTrue(str_contains($html, "OptionFinder.Start(\"office_key\")"), "the script");
});

test("a chosen option: its key and its text, escaped", function () {
    $finder = new OptionFinder("office_key");
    $finder->setChosen("348", "Хасково <b>", "бул. \"Левски\"");
    ob_start();
    $finder->render();
    $html = ob_get_clean();
    assertTrue(str_contains($html, "value='348'"), "the key");
    assertTrue(str_contains($html, "Хасково &lt;b&gt;") && !str_contains($html, "<b>"), "the title escaped");
    assertTrue(!preg_match("#class='chosen'[^>]*hidden#", $html), "shown");
});
