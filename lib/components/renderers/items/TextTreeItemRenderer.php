<?php
include_once("components/renderers/items/NestedSetItemRenderer.php");
include_once("components/renderers/IActionsCollection.php");
include_once("components/renderers/ActionRenderer.php");

class TextTreeItemRenderer extends NestedSetItemRenderer implements IActionsCollection
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

        $this->action_renderer->enableTextTranslation(false);
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

    public function getAction(string $title) : Action
    {
        return $this->actions[$title];
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions() : array
    {
        return $this->actions;
    }

    public function renderActions(array &$row)
    {
        if (count($this->actions) < 1) return;

        echo "<div class='node_actions'>";
        $this->action_renderer->enableActionFromLabel(true);
        foreach ($this->actions as $key => $action) {
            $this->action_renderer->setAction($action);

            $this->action_renderer->setData($this->data_row);
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
        $this->action_renderer->enableActionFromLabel(false);
        $this->action_renderer->setAttribute("action", "TextTreeItemAction");
        $this->action_renderer->setData($this->data_row);
        $this->action_renderer->render();


    }

    public function renderImpl()
    {


        $this->renderHandle();
        $this->renderText();
        $this->renderActions($this->data_row);
    }

}
