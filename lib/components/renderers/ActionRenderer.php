<?php
include_once("components/Component.php");
include_once("actions/Action.php");

class ActionRenderer extends Component
{
    /**
     * @var Action|null
     */
    protected $action = NULL;

    /**
     * DBTableBean result row data
     * @var array|null
     */
    protected $data = NULL;

    public $render_title = TRUE;

    protected $action_from_label = TRUE;

    protected $separator_enabled = FALSE;

    protected $text_translation_enabled = TRUE;

    protected $tagName = "A";

    public function __construct(Action $action = NULL, array $data = NULL)
    {
        parent::__construct();

        $this->data = $data;

        if ($action) {
            $this->setAction($action);
        }
    }

    public function enableSeparator(bool $mode)
    {
        $this->separator_enabled = $mode;
    }

    public function enableActionFromLabel(bool $mode)
    {
        $this->action_from_label = $mode;
    }

    public function enableTextTranslation(bool $mode)
    {
        $this->text_translation_enabled = $mode;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ActionRenderer.css";
        return $arr;
    }

    public function setAction(Action $action)
    {
        $this->action = $action;

    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function setData(array &$row)
    {
        $this->data = $row;
    }

    protected function processAttributes()
    {
        parent::processAttributes();

        if ($this->action->getTitle()) {
            $this->setAttribute("title", tr($this->action->getTitle()));
        }

        if ($this->action_from_label) {
            $this->setAttribute("action", $this->action->getTitle());
        }

        if ($this->action instanceof RowSeparatorAction) {
            $this->setAttribute("action", "RowSeparator");
        }
        else if ($this->action instanceof PipeSeparatorAction) {
            $this->setAttribute("action", "PipeSeparator");
        }

        if ($this->action->isEmptyAction()) {
            $this->tagName = "SPAN";
        }
        else {
            $this->tagName = "A";

            $this->appendAttributes($this->action->getAttributes());

            $this->setAttribute("href", $this->action->getHref($this->data));

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


    public function renderActions(array $actions)
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
