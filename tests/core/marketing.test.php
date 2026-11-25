<?php
/**
 * Marketing: the tracking IDs of Settings > Marketing (config section 'marketing_config') - nothing without an ID;
 * the pixel, one gtag.js configured for Ads and Analytics, the conversion of any page; the conversions by key
 */
include_once("beans/ConfigBean.php");
include_once("utils/script/Marketing.php");
include_once("pages/HTMLHead.php");

new ConfigBean();

/**
 * The head of '$values' in the marketing settings, rendered (the keys of marketingKeys() not given are cleared)
 */
function marketingHead(array $values) : string
{
    $config = ConfigBean::Factory();
    $config->setSection(Marketing::SECTION);
    foreach (marketingKeys() as $key) $config->clear($key);
    foreach ($values as $key => $value) $config->set($key, $value);

    //the head of the request, its scripts emptied
    $head = HTMLHead::Instance();
    $scripts = new ReflectionProperty(HTMLHead::class, "scripts");
    $scripts->setValue($head, array());
    Marketing::AddTo($head);
    ob_start();
    foreach ($scripts->getValue($head) as $script) $script->render();
    return (string)ob_get_clean();
}

function marketingKeys() : array
{
    $keys = array(Marketing::KEY_ANALYTICS, Marketing::KEY_ADS, Marketing::KEY_PIXEL);
    foreach (GTMConvParam::cases() as $param) $keys[] = $param->value;
    return $keys;
}

test("marketing: no ID - no tracking, no panel needed", function () {
    try {
        $html = marketingHead(array());
        assertTrue(!Marketing::Enabled(), "enabled without an ID");
        assertTrue(!str_contains($html, "googletagmanager") && !str_contains($html, "fbq"), "a tracking script without an ID");
    }
    finally {
        marketingHead(array());
    }
});

test("marketing: Analytics and Ads - one gtag.js, a config for each, the any page conversion, the pixel", function () {
    try {
        $html = marketingHead(array(
            Marketing::KEY_ANALYTICS => "G-TEST123",
            Marketing::KEY_ADS => "AW-111",
            Marketing::KEY_PIXEL => "222",
            GTMConvParam::VIEW_ANY_PAGE->value => "AW-111/any",
            GTMConvParam::CONTACT_REQUEST->value => "AW-111/contact",
        ));
        assertTrue(Marketing::Enabled());
        assertSame(1, substr_count($html, "googletagmanager.com/gtag/js"), "one gtag.js");
        assertTrue(str_contains($html, "gtag/js?id=AW-111"), "loaded with the Ads ID");
        foreach (array("G-TEST123", json_encode("AW-111/any"), "fbq") as $part) assertTrue(str_contains($html, $part), "$part missing");
        assertTrue(Marketing::Conversion(GTMConvParam::CONTACT_REQUEST) instanceof GTMConversionCommand, "the contact conversion");
        assertTrue(is_null(Marketing::Conversion(GTMConvParam::CART_ADD)), "no conversion when not set");

        //Analytics only: gtag.js with its ID
        $html = marketingHead(array(Marketing::KEY_ANALYTICS => "G-TEST123"));
        assertTrue(str_contains($html, "gtag/js?id=G-TEST123") && !str_contains($html, "fbq"), "analytics only");
    }
    finally {
        marketingHead(array());
    }
});
