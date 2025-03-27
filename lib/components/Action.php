<?php
include_once("utils/url/URL.php");
include_once("utils/url/URLParameter.php");
include_once("utils/url/DataParameter.php");
include_once("components/renderers/items/DataIteratorItem.php");

class Action extends DataIteratorItem
{
    /**
     * @var Closure|null
     */
    protected ?Closure $check_code = NULL;

    /**
     * @var URL
     */
    protected URL $url;

    /**
     * Render the action as contents of this Action if contents are not set
     * @var bool
     */
    public bool $action_as_contents = TRUE;


    /**
     * Construct new A HTML component
     * @param string $action Inner contents and 'action' attribute value if not empty
     * @param string $href Set the URL and the 'href' attribute value
     * @param array $parameters Array of URLParameters to append to the URL of this Action
     * @param Closure|null $check_code If set controls rendering from its return value true/false. Used during setData
     */
    public function __construct(string $action = "", string $href = "", array $parameters = array(), ?Closure $check_code = NULL)
    {
        parent::__construct();
        $this->setComponentClass("Action");

        $this->setTagName("A");

        $this->url = new URL($href);

        if ($action) {
            $this->setAttribute("action", $action);
            $this->setContents($action);
        }

        foreach ($parameters as $parameter) {

            $this->url->add($parameter);

        }

        $this->check_code = $check_code;

        $this->translation_enabled = TRUE;

    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function getURL(): URL
    {
        return $this->url;
    }

    public function setURL(URL $url) : void
    {
        $this->url = $url;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        if ($this->check_code) {
            debug("Action has check_code anonymous function set: " . $this->getContents());
            $check_code = $this->check_code;
            if (!$check_code($this, $data)) {
                debug("check_code disabled rendering of this action");
                $this->render_enabled = FALSE;
                return;
            }
            else {
                $this->render_enabled = TRUE;
            }
        }

        $this->url->setData($data);

        //set contents from DataIteratorItem
        if ($this->value_key) {
            $this->setContents($this->value);
        }


    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        if ($this->url->toString()) {
            $this->setAttribute("href", $this->url->toString());
        }
        else {
            $this->removeAttribute("href");
        }

    }

    public function getCheckCode() : ?Closure
    {
        return $this->check_code;
    }

    public function setCheckCode(?Closure $check_code)
    {
        $this->check_code = $check_code;
    }

    public static function RenderActions(array $actions, bool $separator = FALSE, bool $translate = FALSE)
    {
        foreach ($actions as $item) {
            if ($item instanceof MenuItem) {
                $action = new Action();
                $action->getURL()->fromString($item->getHref());
                $action->translation_enabled = $translate;
                $action->setContents($item->getName());
                $action->render();
            }
            else if ($item instanceof Action) {

                $item->render();
            }

            if ($separator) {
                echo "<span class='separator'> | </span>";
            }
        }
    }

    public function setAction(string $action) : void
    {
        $this->setAttribute("action", $action);
    }
    public function getAction() : string
    {
        return $this->getAttribute("action");
    }

    public static function PipeSeparator() : Action
    {
        $action = new Action();
        $action->translation_enabled = FALSE;
        $action->action_as_contents = false;
        $action->setTagName("SPAN");
        $action->setContents(" | ");
        $action->setAction("Pipe");
        return $action;
    }

    public static function RowSeparator() : Action
    {
        $action = new Action();
        $action->action_as_contents = false;
        $action->translation_enabled = FALSE;
        $action->setTagName("SPAN");
        $action->setAction("Row");
        return $action;
    }
}

?>
