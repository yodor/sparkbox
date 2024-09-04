<?php
include_once("utils/URLBuilder.php");
include_once("utils/URLParameter.php");
include_once("utils/DataParameter.php");
include_once("components/renderers/items/DataIteratorItem.php");

class Action extends DataIteratorItem
{
    /**
     * @var Closure|null
     */
    protected ?Closure $check_code = NULL;

    /**
     * @var URLBuilder
     */
    protected URLBuilder $urlbuilder;

    /**
     * Render the action as contents of this Action if contents are not set
     * @var bool
     */
    public bool $action_as_contents = TRUE;


    /**
     * @param string $action
     * @param string $href
     * @param array $parameters
     * @param Closure|null $check_code
     */
    public function __construct(string $action = "", string $href = "", array $parameters = array(), Closure $check_code = NULL)
    {
        parent::__construct(false);
        $this->setComponentClass("Action");

        $this->tagName = "A";

        $this->urlbuilder = new URLBuilder();
        $this->urlbuilder->buildFrom($href);

        if ($action) {
            $this->setAttribute("action", $action);
            $this->setContents($action);
        }

        foreach ($parameters as $parameter) {

            $this->urlbuilder->add($parameter);

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

    public function getURLBuilder(): URLBuilder
    {
        return $this->urlbuilder;
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

        $this->urlbuilder->setData($data);

        //set contents from DataIteratorItem
        if ($this->value_key) {
            $this->setContents($this->value);
        }

//        //override only if not empty
//        if ($this->action_as_contents) {
//
//            $action = $this->getAttribute("action");
//
//            if ($action) {
//                $this->setContents($action);
//            }
//        }

    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $url = $this->urlbuilder->url();

        if ($url) {
            $this->setAttribute("href", $url);
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
                $action->getURLBuilder()->buildFrom($item->getHref());
                $action->translation_enabled = $translate;
                $action->setContents($item->getTitle());
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
