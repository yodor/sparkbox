<?php
include_once("components/renderers/items/NestedSetItem.php");
include_once("components/renderers/IActionsCollection.php");
include_once("components/renderers/ActionRenderer.php");

class TextTreeItem extends NestedSetItem implements IActionsCollection
{

    protected $actions = NULL;
    protected $text_action = NULL;
    protected $action_renderer = NULL;

    public function __construct()
    {
        parent::__construct();

        $this->text_action = new Action("Text Action", "", array());

        $this->actions = array();
        $this->attribute_actions = array();

        $this->action_renderer = new ActionRenderer(NULL, NULL);

        $this->action_renderer->enableTextTranslation(FALSE);
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
        $this->actions[] = $a;
    }

    public function getAction(string $title): Action
    {
        return $this->actions[$title];
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function renderActions(array &$row)
    {
        if (count($this->actions) < 1) return;

        echo "<div class='node_actions'>";
        $this->action_renderer->enableActionFromLabel(TRUE);
        foreach ($this->actions as $key => $action) {
            $this->action_renderer->setAction($action);

            $this->action_renderer->setData($this->data);
            $this->action_renderer->render();

        }
        echo "</div>";

    }

    public function renderHandle()
    {
        echo "<div class='Handle'>";
        echo "<div class='Button'></div>";
        echo "</div> ";

    }

    public function renderText()
    {
        // 	    echo "<div class='Control'>";
        // 	    echo "<input type='checkbox'>";
        // 	    echo "</div> ";

        $this->text_action->setTitle($this->label);

        $this->action_renderer->setAction($this->text_action);
        $this->action_renderer->enableActionFromLabel(FALSE);
        $this->action_renderer->setAttribute("action", "TextTreeItemAction");
        $this->action_renderer->setData($this->data);
        $this->action_renderer->render();

    }

    protected function renderImpl()
    {
        $this->renderHandle();
        $this->renderText();
        $this->renderActions($this->data);
    }

}
