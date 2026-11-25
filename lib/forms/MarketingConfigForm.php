<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");
include_once("objects/data/GTMConvParam.php");
include_once("utils/script/Marketing.php");

/**
 * The marketing settings of a site (Settings > Marketing, config section Marketing::SECTION): the tracking IDs
 * (Google Analytics, Google Ads, Facebook pixel) and the Google Ads conversions. The store form
 * (StoreMarketingConfigForm) adds the conversions of the products to conversions().
 */
class MarketingConfigForm extends InputForm
{
    protected InputGroup $conversions;

    public function __construct()
    {
        parent::__construct();

        $basic = new InputGroup("basic", "Basic");
        $basic->setDescription("Google and Facebook tracking IDs - the cookie panel is shown when one is set");
        $this->addGroup($basic);

        $this->addInput(DataInputFactory::Create(InputType::TEXT, Marketing::KEY_ANALYTICS, "Google Analytics ID (eg: G-XY12AB3CD1)", false), $basic);
        $this->addInput(DataInputFactory::Create(InputType::TEXT, Marketing::KEY_ADS, "Google ADs ID (eg: AW-123456789)", false), $basic);
        $this->addInput(DataInputFactory::Create(InputType::TEXT, GTMConvParam::VIEW_ANY_PAGE->value, "Google ADs Any Page Conversion ID", false), $basic);
        $this->addInput(DataInputFactory::Create(InputType::TEXT, Marketing::KEY_PIXEL, "Facebook Pixel ID", false), $basic);

        $this->conversions = new InputGroup("conversion", "Google ADs Conversion IDs");
        $this->conversions->setDescription("Format: AW-CONVERSION_ID/CONVERSION_LABEL");
        $this->addGroup($this->conversions);

        $this->addConversion(GTMConvParam::CONTACT_REQUEST, tr("Contact Request"));
    }

    /**
     * A conversion field into the conversions group
     */
    protected function addConversion(GTMConvParam $param, string $label) : void
    {
        $this->addInput(DataInputFactory::Create(InputType::TEXT, $param->value, $label, false), $this->conversions);
    }
}
