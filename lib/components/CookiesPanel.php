<?php
include_once("components/PageSection.php");
include_once("components/Container.php");
include_once("components/Button.php");
include_once("components/TextComponent.php");
include_once("utils/url/DynamicPageURL.php");

/**
 * The cookie consent panel (sparkfront js/SparkCookies.js keeps the choice; css/CookiesPanel.css). A frame shows it
 * when the site tracks its visitors (Marketing::Enabled) - SparkSitePage, and the store frame through StoreCookies.
 *
 * Nothing but the necessary cookies before a choice. Accept all and Reject are equal buttons; Settings shows the
 * two choices (analytics, ads) and Save. A 'cookies' link of the footer opens the panel again (sparkCookies.open()).
 *
 *   aside.section.cookies[accepted][settings] > .content > .info
 *     .text (+ the terms link), .choices > label > input[name=analytics|ads], .buttons > a.ColorButton
 */
class CookiesPanel extends PageSection
{
    const string OPEN_URL = "javascript:document.sparkCookies.open()";

    public function __construct()
    {
        parent::__construct();
        $this->addClassName("cookies");
        $this->setTagName("aside");
        $this->setAttribute("aria-label", "Cookie Consent");
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
            $esc(tr("Използваме бисквитки, необходими за работата на сайта. С Ваше съгласие използваме и бисквитки за анализ на посещенията и за реклама. Можете да промените избора си по всяко време от иконата 'бисквитки' в долната част на страницата.")) .
            " <a href='" . $esc($terms) . "'>" . $esc(tr("Общи условия")) . "</a>");
        $text->setComponentClass("text");
        $container->items()->append($text);

        $choice = function (string $name, string $label, bool $locked = false) use ($esc) : string {
            $attributes = $locked ? " checked disabled" : "";
            return "<label class='choice $name'><input type='checkbox' name='$name'$attributes> " . $esc($label) . "</label>";
        };
        $choices = new TextComponent(
            $choice("necessary", tr("Необходими (винаги активни)"), true) .
            $choice("analytics", tr("Анализ на посещенията")) .
            $choice("ads", tr("Реклама и маркетинг")));
        $choices->setComponentClass("choices");
        $container->items()->append($choices);

        $buttons = new Container(false);
        $buttons->setComponentClass("buttons");
        foreach (array(
                     "accept" => array(tr("Приемам всички"), "acceptAll"),
                     "reject" => array(tr("Отказвам"), "rejectAll"),
                     "settings" => array(tr("Настройки"), "showSettings"),
                     "save" => array(tr("Запази избора"), "saveSettings"),
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
