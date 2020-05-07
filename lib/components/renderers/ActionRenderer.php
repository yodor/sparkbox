<?php
include_once("components/Component.php");
include_once("components/renderers/IActionRenderer.php");
include_once("actions/Action.php");

class ActionRenderer extends Component implements IActionRenderer
{
    protected $action = NULL;

    /**
     * DBTableBean result row data
     * @var array|null
     */
    protected $data = NULL;

    public $render_title = true;

    protected $action_from_label = true;

    protected $separator_enabled = false;

    protected $text_translation_enabled = true;

    public function __construct(Action $action = NULL, array $data = NULL)
    {
        parent::__construct();
        $this->data = $data;
        $this->action_from_label = true;

        if ($action) {
            $this->setAction($action);
        }
    }

    public function enableSeparator($mode)
    {
        $this->separator_enabled = $mode;
    }

    public function enableActionFromLabel($mode)
    {
        $this->action_from_label = ($mode) ? true : false;
    }

    public function enableTextTranslation($mode)
    {
        $this->text_translation_enabled = ($mode) ? true : false;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "sparkfront/css/ActionRenderer.css";
        return $arr;
    }

    public function setAction(Action $action)
    {
        $this->action = $action;

        if ($this->action->getTitle()) {
            $this->setAttribute("title", tr($this->action->getTitle()));
        }

        if ($this->action_from_label) {
            $this->setAttribute("action", $this->action->getTitle());
        }
        else {
            $this->setAttribute("action", "");
        }

        if ($this->action instanceof RowSeparatorAction) {
            $this->setAttribute("action", "RowSeparator");
        }
        else if ($this->action instanceof PipeSeparatorAction) {
            $this->setAttribute("action", "PipeSeparator");
        }
    }

    public function getAction() : Action
    {
        return $this->action;
    }

    public function setData(array $row)
    {
        $this->data = $row;
    }

    public function startRender()
    {

        if ($this->action->isEmptyAction()) {
            $attrs = $this->prepareAttributes();
            echo "<span $attrs>";
        }
        else {
            $this->appendAttributes($this->action->getAttributes());
            if ($this->data) {
                $this->setAttribute("href", $this->action->getHref($this->data));
            }
            else {
                $this->setAttribute("href", $this->action->getHrefClean());
            }
            $attrs = $this->prepareAttributes();
            echo "<a $attrs>";
        }

    }

    protected function renderImpl()
    {
        if ($this->action instanceof EmptyAction) {
            //
        }
        else if ($this->action instanceof RowSeparatorAction) {
            //
        }
        else if ($this->action instanceof PipeSeparatorAction) {
            echo " | ";
        }
        else {
            if ($this->render_title) {
                if ($this->text_translation_enabled) {
                    echo tr($this->action->getTitle());
                }
                else {
                    echo $this->action->getTitle();
                }
            }
        }
    }

    public function finishRender()
    {
        if ($this->action->isEmptyAction()) {
            echo "</span>";
        }
        else {
            echo "</a>";
        }
    }

    public function renderActions($actions)
    {
        foreach ($actions as $idx => $item) {
            if ($item instanceof MenuItem) {
                $this->action = new Action($item->getTitle(), $item->getHref(), array());

            }
            else if ($item instanceof Action) {
                $this->action = $item;

            }
            $this->render();
            if ($this->separator_enabled) {
                echo "<span class='separator'> | </span>";
            }
        }
    }

}

?>
