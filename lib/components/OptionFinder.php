<?php
include_once("components/Container.php");
include_once("components/Component.php");
include_once("components/InlineScript.php");
include_once("utils/language.php");
include_once("utils/url/URL.php");

/**
 * Find and choose one option of a long list by typing (sparkfront/js/OptionFinder.js): a text field and, under it,
 * the matching options to click - not a drop-down. The chosen key goes in a hidden field of the form.
 *
 * The options come once from the JSON source (setSource) - a JSONResponse whose 'contents' is a JSON list of
 * {key, title, text, prefix}: an option matches when 'prefix' (lower case) starts with the typed text (listed first)
 * or its title or text contains it. The typed text is underlined in the title and the text.
 *
 *   div.OptionFinder[name][source]
 *     input.query                       the typed text
 *     input[type=hidden][name]          the chosen key
 *     div.chosen > .title, .text        the chosen option
 *     ul.options > li.option > .title, .text
 *     div.hint                          nothing typed / nothing found
 */
class OptionFinder extends Container
{
    protected Component $query;
    protected Component $value;
    protected Container $chosen;
    protected Component $chosenTitle;
    protected Component $chosenText;
    protected Component $options;
    protected Component $hint;

    protected string $hintEmpty = "";
    protected string $hintNone = "";

    /**
     * @param string $name the name of the hidden field (the chosen key)
     */
    public function __construct(string $name)
    {
        parent::__construct();
        $this->setName($name);

        $this->query = new Component(false);
        $this->query->setTagName("input");
        $this->query->setClosingTagRequired(false);
        $this->query->setComponentClass("query");
        $this->query->setAttribute("type", "search");
        $this->query->setAttribute("autocomplete", "off");
        $this->query->setAttribute("aria-autocomplete", "list");
        $this->items()->append($this->query);

        $this->value = new Component(false);
        $this->value->setTagName("input");
        $this->value->setClosingTagRequired(false);
        $this->value->setComponentClass("");
        $this->value->setAttribute("type", "hidden");
        $this->value->setAttribute("name", $name);
        $this->items()->append($this->value);

        $this->chosen = new Container(false);
        $this->chosen->setComponentClass("chosen");
        $this->chosenTitle = new Component(false);
        $this->chosenTitle->setComponentClass("title");
        $this->chosenText = new Component(false);
        $this->chosenText->setComponentClass("text");
        $this->chosen->items()->append($this->chosenTitle);
        $this->chosen->items()->append($this->chosenText);
        $this->items()->append($this->chosen);

        $this->options = new Component(false);
        $this->options->setTagName("ul");
        $this->options->setComponentClass("options");
        $this->options->setAttribute("role", "listbox");
        $this->items()->append($this->options);

        $this->hint = new Component(false);
        $this->hint->setComponentClass("hint");
        $this->items()->append($this->hint);

        $this->hintEmpty = tr("Въведете първите букви");
        $this->hintNone = tr("Няма намерени");
    }

    public function requiredStyle(): array
    {
        $ret = parent::requiredStyle();
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/css/OptionFinder.css";
        return $ret;
    }

    public function requiredScript(): array
    {
        $ret = parent::requiredScript();
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/js/OptionFinder.js";
        return $ret;
    }

    /**
     * The JSON source of the options (read once)
     */
    public function setSource(URL $url) : void
    {
        $this->setAttribute("source", $url->toString());
    }

    public function setPlaceholder(string $text) : void
    {
        $this->query->setAttribute("placeholder", $text);
    }

    /**
     * The hints: nothing typed yet, nothing found
     */
    public function setHints(string $empty, string $none) : void
    {
        $this->hintEmpty = $empty;
        $this->hintNone = $none;
    }

    /**
     * The option chosen before (the saved one)
     */
    public function setChosen(string $key, string $title, string $text) : void
    {
        $this->value->setAttribute("value", $key);
        $this->chosenTitle->setText($title);
        $this->chosenText->setText($text);
    }

    public function startRender(): void
    {
        $this->setAttribute("hint-empty", $this->hintEmpty);
        $this->setAttribute("hint-none", $this->hintNone);
        if ((string)$this->value->getAttribute("value") === "") {
            $this->chosen->setAttribute("hidden", "");
        }
        else {
            $this->chosen->removeAttribute("hidden");
        }
        parent::startRender();
    }

    public function finishRender(): void
    {
        parent::finishRender();
        $name = json_encode($this->getName());
        $script = new InlineScript(true);
        $script->setCode("OptionFinder.Start($name);");
        $script->render();
    }
}
