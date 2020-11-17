<?php
include_once("components/renderers/items/NestedSetItem.php");
include_once("utils/IActionCollection.php");
include_once("utils/ActionCollection.php");

include_once("components/Action.php");

class TextTreeItem extends NestedSetItem implements IActionCollection
{

    /**
     * @var ActionCollection
     */
    protected $actions;

    /**
     * @var Action
     */
    protected $text_action;

    /**
     * @var bool
     */
    protected $render_related_count;

    /**
     * @var string
     */
    protected $key_related_count;

    public function __construct()
    {
        parent::__construct();

        //construct default empty action with no parameters
        $this->text_action = new Action("TextTreeItemAction");

        $this->actions = new ActionCollection();

        //show related count in parenthesis inside the label
        $this->render_related_count = true;
        $this->key_related_count = "related_count";
    }

    public function renderRelatedCount(bool $mode) : void
    {
        $this->render_related_count = $mode;
    }

    public function setKeyRelatedCount(string $key) : void
    {
        $this->key_related_count = $key;
    }

    public function getKeyRelatedCount() : string
    {
        return $this->key_related_count;
    }

    public function getTextAction(): Action
    {
        return $this->text_action;
    }

    public function setTextAction(Action $text_action)
    {
        $this->text_action = $text_action;
    }

    public function setActions(ActionCollection $actions)
    {
        $this->actions = $actions;
    }

    public function getActions(): ?ActionCollection
    {
        return $this->actions;
    }

    protected function renderActions()
    {
        if ($this->actions->count() < 1) return;

        echo "<div class='node_actions'>";

        $render = function(Action $action, int $idx)  {
            $action->render();
        };
        $this->actions->each($render);

        echo "</div>";

    }

    public function setData(array &$row)
    {
        parent::setData($row);

        $this->text_action->setData($row);

        $dataSetter = function(Action $action, int $idx) use($row) {
            $action->setData($row);
        };
        $this->actions->each($dataSetter);


    }

    protected function renderHandle()
    {
        echo "<div class='Handle'>";
        echo "<div class='Button'></div>";
        echo "</div> ";
    }

    public function renderText()
    {
        if ($this->render_related_count && isset($this->data[$this->key_related_count])) {
            $this->text_action->setContents($this->label." (".$this->data[$this->key_related_count].")");
        }
        else {
            $this->text_action->setContents($this->label);
        }
        $this->text_action->render();
    }

    protected function renderImpl()
    {
        $this->renderHandle();
        $this->renderText();
        $this->renderActions();
    }

}
