<?php
/**
 * The faq module (tests/app/faq -> modules/faq). Adds sections and questions and removes them.
 */
include_once(__DIR__ . "/../harness/http.php");

function faqRequest(string $query, string $uri) : array
{
    return httpRequest("faq/index.php", $query, array(), $uri);
}

/**
 * Two sections with questions, one empty - returns [tag, section ids by name]
 */
function faqSeed(PDO $pdo) : array
{
    //the beans create the tables on a fresh database
    faqRequest("", "/faq/");
    $tag = "fq" . bin2hex(random_bytes(3));
    $ids = array();
    foreach (array("Delivery $tag", "Payment $tag", "Empty $tag") as $name) {
        $pdo->prepare("INSERT INTO faq_sections (section_name) VALUES (?)")->execute(array($name));
        $ids[$name] = (int)$pdo->lastInsertId();
    }
    $insert = $pdo->prepare("INSERT INTO faq_items (fqsID, question, answer) VALUES (?, ?, ?)");
    $insert->execute(array($ids["Delivery $tag"], "How long is the delivery <b>$tag</b>?", "<p>Two days.</p>"));
    $insert->execute(array($ids["Delivery $tag"], "Where do you deliver $tag?", "<p>Everywhere.</p>"));
    $insert->execute(array($ids["Payment $tag"], "How to pay $tag?", "<p>Cash on delivery.</p>"));
    return array($tag, $ids);
}

function faqClean(PDO $pdo, string $tag) : void
{
    $pdo->exec("DELETE FROM faq_sections WHERE section_name LIKE '%$tag'");
}

test("faq: every section with its questions, the menu of sections with questions, one FAQPage", function () {
    $pdo = TestPDO();
    [$tag, $ids] = faqSeed($pdo);
    try {
        [$status, , $body] = faqRequest("", "/faq/");
        assertSame(200, $status);
        $delivery = $ids["Delivery $tag"];
        assertTrue(str_contains($body, "href='/faq/$delivery/delivery-$tag/'"), "section link");
        assertTrue(!str_contains($body, "Empty $tag"), "a section without questions in the menu");
        assertTrue(str_contains($body, "<summary class='question'>How long is the delivery &lt;b&gt;$tag&lt;/b&gt;?</summary>"), "question as text");
        assertTrue(str_contains($body, "<p>Two days.</p>") && str_contains($body, "<p>Cash on delivery.</p>"), "answers of both sections");
        assertSame(1, preg_match_all('#"@type"\\s*:\\s*"FAQPage"#', $body), "one FAQPage");
        assertTrue(str_contains($body, "/css/FAQ.css"), "base styles");
    }
    finally {
        faqClean($pdo, $tag);
    }
});

test("faq: a section at its address - its questions only, canonical; old ?secID= 301; unknown to the module", function () {
    $pdo = TestPDO();
    [$tag, $ids] = faqSeed($pdo);
    try {
        $id = $ids["Payment $tag"];
        $slug = "payment-$tag";
        [$status, , $body] = faqRequest("path=%2Fsection&fqsID=$id", "/faq/$id/$slug/");
        assertSame(200, $status);
        assertTrue(str_contains($body, "<p>Cash on delivery.</p>") && !str_contains($body, "<p>Two days.</p>"), "only its questions");
        assertTrue((bool)preg_match("#<link rel='canonical' href='[^']*//localhost/faq/$id/$slug/'>#", $body), "canonical");
        assertTrue(str_contains($body, "class='item selected'"), "current section marked");

        [$status, $headers] = faqRequest("secID=$id", "/faq/index.php?secID=$id");
        assertSame(301, $status, "old address");
        assertTrue(str_ends_with($headers["location"] ?? "", "/faq/$id/$slug/"), "old address: " . ($headers["location"] ?? ""));
        [$status, $headers] = faqRequest("path=%2Fsection&fqsID=" . $ids["Empty $tag"], "/faq/" . $ids["Empty $tag"] . "/x/");
        assertSame(302, $status, "a section without questions");
        assertTrue(str_ends_with($headers["location"] ?? "", "/faq/"), "to the module");
    }
    finally {
        faqClean($pdo, $tag);
    }
});
