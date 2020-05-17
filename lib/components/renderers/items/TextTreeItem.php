<?php
include_once("components/renderers/items/NestedSetItem.php");
include_once("components/renderers/IActionsCollection.php");
include_once("components/Action.php");

class TextTreeItem extends NestedSetItem implements IActionsCollection
{

    protected $actions = NULL;
    protected $text_action = NULL;

    public function __construct()
    {
        parent::__construct();

        //construct default empty action with no parameters
        $this->text_action = new Action();

        $this->text_action->setAttribute("action", "TextTreeItemAction");

        $this->actions = array();

    }

    public function getTextAction()
    {
        return $this->text_action;
    }

    public function setTextAction(Action $text_action)
    {
        $this->text_action = $text_action;
    }

    public function addAction(Action $a)
    {
        $this->actions[$a->getContents()] = $a;
    }

    public function removeAction(string $title)
    {
        if (isset($this->actions[$title])) {
            unset($this->actions[$title]);
        }
    }

    public function getAction(string $contents): Action
    {
        return $this->actions[$contents];
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    protected function renderActions()
    {
        if (count($this->actions) < 1) return;

        echo "<div class='node_actions'>";

        foreach ($this->actions as $key => $action) {

            $action->render();

        }
        echo "</div>";

    }

    public function setData(array &$row)
    {
        parent::setData($row);

        $this->text_action->setData($row);

        foreach ($this->actions as $cnt => $action) {

            $action->setData($row);
        }
    }

    protected function renderHandle()
    {
        echo "<div class='Handle'>";
        echo "<div class='Button'></div>";
        echo "</div> ";
    }

    public function renderText()
    {

        $this->text_action->setContents($this->label);
        $this->text_action->render();

    }

    protected function renderImpl()
    {
        $this->renderHandle();
        $this->renderText();
        $this->renderActions();
    }

    public function addURLParameter(URLParameter $param)
    {
        // TODO: Implement addURLParameter() method.

    }

}
