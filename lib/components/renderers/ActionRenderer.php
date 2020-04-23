<?php
include_once("lib/components/Component.php");
include_once("lib/actions/Action.php");

class ActionRenderer extends Component
{
    protected $action = NULL;
    protected $result_row = NULL;
    public $render_title = true;

    protected $action_from_label = true;

    protected $separator_enabled = false;

    protected $text_translation_enabled = true;

    public function __construct(Action $action = NULL, $result_row = NULL)
    {
        parent::__construct();
        $this->result_row = $result_row;
        $this->action_from_label = true;

        if ($action instanceof Action) {
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
        $arr[] = SITE_ROOT . "lib/css/ActionRenderer.css";
        return $arr;
    }

    public function setAction(Action $action)
    {
        $this->action = $action;
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

    public function setResultRow(&$row)
    {
        $this->result_row = $row;
    }

    public function startRender()
    {

        if ($this->action->isEmptyAction()) {
            $attrs = $this->prepareAttributes();
            echo "<span $attrs>";
        }
        else {
            $this->appendAttributes($this->action->getAttributes());
            if ($this->result_row) {
                $this->setAttribute("href", $this->action->getHref($this->result_row));
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
