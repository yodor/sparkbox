<?php
/**
 * ConfigBean: a section is read with one query per request; set() and clear() keep it current; clear() removes the
 * key of its own section only; a value larger than INLINE_SIZE is loaded when it is asked for
 */
include_once("beans/ConfigBean.php");

$pdo = TestPDO();
new ConfigBean();
$tag = "cfg" . bin2hex(random_bytes(3));

test("config: values, a missing key, set() and clear() in one request", function () use ($pdo, $tag) {
    $pdo->prepare("INSERT INTO config (config_key, config_val, section) VALUES ('phone', '0888', ?), ('phone', '0999', ?)")
        ->execute(array("a$tag", "b$tag"));
    try {
        $a = new ConfigBean();
        $a->setSection("a$tag");
        assertSame("0888", $a->get("phone"));
        assertSame("fallback", $a->get("missing", "fallback"));

        $a->set("email", "x@example.com");
        assertSame("x@example.com", $a->get("email"), "a new key is seen after set()");
        $a->set("phone", "0777");
        assertSame("0777", $a->get("phone"), "an updated key is seen after set()");

        $a->clear("phone");
        assertSame("gone", $a->get("phone", "gone"), "cleared");

        $b = new ConfigBean();
        $b->setSection("b$tag");
        assertSame("0999", $b->get("phone"), "clear() of section a left the key of section b");
    }
    finally {
        $pdo->exec("DELETE FROM config WHERE section IN ('a$tag', 'b$tag')");
    }
});

test("config: a value larger than INLINE_SIZE is loaded when asked for", function () use ($pdo, $tag) {
    $large = str_repeat("x", ConfigBean::INLINE_SIZE + 10);
    $pdo->prepare("INSERT INTO config (config_key, config_val, section) VALUES ('small', 's', ?), ('large', ?, ?)")
        ->execute(array("l$tag", $large, "l$tag"));
    try {
        $config = new ConfigBean();
        $config->setSection("l$tag");
        assertSame("s", $config->get("small"));
        assertSame($large, $config->get("large"));
        assertSame($large, $config->get("large"), "again, from the request");
    }
    finally {
        $pdo->exec("DELETE FROM config WHERE section = 'l$tag'");
    }
});
