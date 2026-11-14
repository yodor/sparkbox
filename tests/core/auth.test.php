<?php
/**
 * Authentication helpers
 */
include_once("auth/Authenticator.php");
include_once("auth/AuthToken.php");

test("RandomToken: requested length, hex, not repeating", function () {
    foreach (array(8, 16, 31, 32) as $length) {
        $token = Authenticator::RandomToken($length);
        assertSame($length, strlen($token), "length $length");
        assertTrue(ctype_xdigit($token), "not hex: $token");
    }
    $tokens = array();
    for ($i = 0; $i < 200; $i++) $tokens[] = Authenticator::RandomToken(16);
    assertSame(200, count(array_unique($tokens)), "repeated tokens");
});
