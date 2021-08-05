<?php
include_once("utils/URLBuilder.php");
include_once("utils/URLParameter.php");
include_once("utils/DataParameter.php");
include_once("components/renderers/items/DataIteratorItem.php");

class Action extends DataIteratorItem
{


    /**
     * Generic class for handling action and parametrization of its href
     */

    protected $tagName = "A";

    protected $check_code = NULL;

    /**
     * @var URLBuilder
     */
    protected $urlbuilder;

    /**
     * Render the action as contents of this Action if contents are not set
     * @var bool
     */
    public $action_as_contents = TRUE;

    /**
     * Action constructor.
     *
     * @param string $contents
     * @param string $href
     * @param array $parameters
     * @param string $check_code this will be eval'ed before rendering
     */
    public function __construct(string $action = "", string $href = "", array $parameters = array(), Closure $check_code = NULL)
    {
        parent::__construct();

        $this->urlbuilder = new URLBuilder();
        $this->urlbuilder->buildFrom($href);

        if ($action) {
            $this->setAttribute("action", $action);
        }

        foreach ($parameters as $idx => $parameter) {

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

    public function setData(array &$row)
    {

        if ($this->check_code) {
            debug("Action has check_code anonymous function set: " . $this->getContents());
            $check_code = $this->check_code;
            if (!$check_code($this, $row)) {
                debug("check_code disabled rendering of this action");
                $this->render_enabled = FALSE;
                return;
            }
            else {
                $this->render_enabled = TRUE;
            }
        }

        $this->urlbuilder->setData($row);

    }

    protected function processAttributes()
    {
        parent::processAttributes();

        $url = $this->urlbuilder->url();

        if ($url) {
            $this->setAttribute("href", $url);
        }

    }

    protected function renderImpl()
    {
        if ($this->contents) {
            parent::renderImpl();
            return;
        }
        $action = $this->getAttribute("action");
        if (!$action) return;

        if ($this->action_as_contents) {

            if ($this->translation_enabled) {
                echo tr($action);
            }
            else {
                echo $action;
            }
        }

    }

    public function getCheckCode()
    {
        return $this->check_code;
    }

    public function setCheckCode($check_code)
    {
        $this->check_code = $check_code;
    }

    public static function RenderActions(array $actions, bool $separator = FALSE, bool $translate = FALSE)
    {
        foreach ($actions as $idx => $item) {
            if ($item instanceof MenuItem) {
                $action = new Action("", $item->getHref(), array());
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

}

class PipeSeparator extends Action
{
    public function __construct()
    {
        parent::__construct("Pipe");
        $this->action_as_contents = false;
        $this->contents = " | ";
        $this->tagName = "SPAN";
        $this->translation_enabled = FALSE;
    }
}

class RowSeparator extends Action
{
    public function __construct()
    {
        parent::__construct("Row");
        $this->action_as_contents = false;
        $this->tagName = "SPAN";
        $this->translation_enabled = FALSE;
    }
}


?>