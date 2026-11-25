<?php
include_once("beans/ConfigBean.php");
include_once("pages/HTMLHead.php");
include_once("utils/script/GTAG.php");
include_once("utils/script/FBPixel.php");
include_once("objects/data/GTMCommand.php");
include_once("objects/data/GTMConversionCommand.php");
include_once("objects/data/GTMConvParam.php");

/**
 * The tracking of a site: Google Analytics (GA4), Google Ads and the Facebook pixel - the IDs of the admin
 * Settings > Marketing (MarketingConfigForm, config section 'marketing_config'). The frames of the public pages
 * (SparkSitePage, StorePage) add the scripts to their head; nothing is sent before the visitor's choice (consent
 * mode: sparkfront js/SparkGTM.js, the panel CookiesPanel / js/SparkCookies.js).
 */
class Marketing
{
    const string SECTION = "marketing_config";

    const string KEY_ANALYTICS = "googleID_analytics";
    const string KEY_ADS = "googleID_ads";
    const string KEY_PIXEL = "facebookID_pixel";

    protected static function Config() : ConfigBean
    {
        $config = ConfigBean::Factory();
        $config->setSection(Marketing::SECTION);
        return $config;
    }

    /**
     * The value of '$key' in the marketing settings - "" when not set
     */
    public static function Get(string $key) : string
    {
        return trim((string)Marketing::Config()->get($key, ""));
    }

    /**
     * The site tracks its visitors (an ID is set) - it needs their consent (the cookie panel)
     */
    public static function Enabled() : bool
    {
        return Marketing::Get(Marketing::KEY_ANALYTICS) !== "" || Marketing::Get(Marketing::KEY_ADS) !== ""
            || Marketing::Get(Marketing::KEY_PIXEL) !== "";
    }

    /**
     * The Google Ads conversion of '$param', or null when it is not set
     */
    public static function Conversion(GTMConvParam $param) : ?GTMConversionCommand
    {
        $conversionID = Marketing::Get($param->value);
        return ($conversionID !== "") ? new GTMConversionCommand($conversionID) : null;
    }

    /**
     * The scripts of the tracking into '$head': the pixel, gtag with the Ads and the Analytics config, the
     * conversion of any page
     */
    public static function AddTo(HTMLHead $head) : void
    {
        $pixel = Marketing::Get(Marketing::KEY_PIXEL);
        if ($pixel !== "") $head->addScript(new FBPixel($pixel));

        $ads = Marketing::Get(Marketing::KEY_ADS);
        $analytics = Marketing::Get(Marketing::KEY_ANALYTICS);
        if ($ads === "" && $analytics === "") return;

        //one gtag.js - loaded with the first ID, configured for each
        $head->addScript(new GTAG(($ads !== "") ? $ads : $analytics));
        foreach (array($ads, $analytics) as $id) {
            if ($id === "") continue;
            $config = new GTMCommand();
            $config->setCommand(GTMCommand::COMMAND_CONFIG);
            $config->setType($id);
            $head->addScript($config);
        }

        $conversion = Marketing::Conversion(GTMConvParam::VIEW_ANY_PAGE);
        if ($conversion instanceof GTMConversionCommand) $head->addScript($conversion);
    }
}
