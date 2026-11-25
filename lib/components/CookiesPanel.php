<?php
include_once("components/PageSection.php");
include_once("components/Container.php");
include_once("components/Button.php");
include_once("components/TextComponent.php");
include_once("components/InlineScript.php");
include_once("utils/url/DynamicPageURL.php");

/**
 * The cookie consent panel (sparkfront js/SparkCookies.js keeps the choice; css/CookiesPanel.css). A frame shows it
 * when the site tracks its visitors (Marketing::Enabled) - SparkSitePage, and the store frame through StoreCookies.
 *
 * Nothing but the necessary cookies before a choice. Accept all and Reject are equal buttons; Settings shows the
 * two choices (analytics, ads) and Save. A 'cookies' link of the footer opens the panel again (sparkCookies.open()).
 *
 *   div.section.cookies[role=dialog][accepted][settings] > .content > .info
 *     .text (+ the terms link), .choices > label > input[name=analytics|ads], .buttons > a.ColorButton
 */
class CookiesPanel extends PageSection
{
    const string OPEN_URL = "javascript:document.sparkCookies.open()";

    public function __construct()
    {
        parent::__construct();
        $this->addClassName("cookies");
        //a div: role 'dialog' is not allowed on an aside
        $this->setTagName("div");
        $this->setAttribute("aria-label", tr("Cookie Consent"));
        $this->setAttribute("role", "dialog");
        $this->setAttribute("tabindex", "0");
        $this->content->items()->append(CookiesPanel::InfoPanel());
    }

    public function requiredStyle() : array
    {
        $ret = parent::requiredStyle();
        $ret[] = CookiesPanel::Stylesheet();
        return $ret;
    }

    public function finishRender() : void
    {
        parent::finishRender();
        CookiesPanel::RenderState();
    }

    /**
     * Show or hide the panel (the element just before this script) as the page is parsed - by the stored choice
     * (SparkGTM, a boot script). The page is the same for every visitor (the CDN keeps it), so the choice is read
     * here and not by the server; SparkCookies takes over the panel when the page is parsed.
     */
    public static function RenderState() : void
    {
        $script = new InlineScript();
        $script->setCode("(function (panel) { if (panel && typeof SparkGTM === 'function') panel.setAttribute('accepted', SparkGTM.StoredConsent() ? 'true' : 'false'); })(document.currentScript && document.currentScript.previousElementSibling);");
        $script->render();
    }

    public static function Stylesheet() : string
    {
        return Spark::Get(Config::SPARK_LOCAL) . "/css/CookiesPanel.css";
    }

    /**
     * The panel: the text, the choices, the buttons
     * @return Container
     */
    public static function InfoPanel() : Container
    {
        $container = new Container(false);
        $container->setComponentClass("info");

        $esc = fn(string $text) : string => htmlspecialchars($text, ENT_QUOTES);

        $terms = DynamicPageURL::Group("terms")->toString();
        $text = new TextComponent(
            $esc(tr("We use the cookies the site needs to work. With your consent we also use cookies to analyse the visits and for advertising. You can change your choice at any time from the 'cookies' icon at the bottom of the page.")) .
            " <a href='" . $esc($terms) . "'>" . $esc(tr("Terms and Conditions")) . "</a>");
        $text->setComponentClass("text");
        $container->items()->append($text);

        $choice = function (string $name, string $label, bool $locked = false) use ($esc) : string {
            $attributes = $locked ? " checked disabled" : "";
            return "<label class='choice $name'><input type='checkbox' name='$name'$attributes> " . $esc($label) . "</label>";
        };
        $choices = new TextComponent(
            $choice("necessary", tr("Necessary (always active)"), true) .
            $choice("analytics", tr("Visit analytics")) .
            $choice("ads", tr("Advertising and marketing")));
        $choices->setComponentClass("choices");
        $container->items()->append($choices);

        $buttons = new Container(false);
        $buttons->setComponentClass("buttons");
        foreach (array(
                     "accept" => array(tr("Accept all"), "acceptAll"),
                     "reject" => array(tr("Reject"), "rejectAll"),
                     "settings" => array(tr("Settings"), "showSettings"),
                     "save" => array(tr("Save my choice"), "saveSettings"),
                 ) as $name => [$label, $method]) {
            $button = Button::Action($label, "javascript:document.sparkCookies.$method()");
            $button->setComponentClass("ColorButton");
            $button->addClassName($name);
            $buttons->items()->append($button);
        }
        $container->items()->append($buttons);

        return $container;
    }
}
