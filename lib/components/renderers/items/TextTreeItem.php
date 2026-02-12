<?php
include_once("components/renderers/items/NestedSetItem.php");
include_once("utils/IActionCollection.php");
include_once("objects/ActionCollection.php");

include_once("components/ClosureComponent.php");
include_once("components/Action.php");
include_once("input/renderers/Input.php");

class TextTreeItem extends NestedSetItem implements IActionCollection
{

    /**
     * @var ActionCollection
     */
    protected ActionCollection $actions;

    /**
     * @var Action
     */
    protected Action $text_action;

    protected Input $checkbox;
    /**
     * @var bool
     */
    protected bool $render_related_count = false;

    /**
     * @var string
     */
    protected string $key_related_count = "";

    protected Image $icon;
    /**
     * @var string checkbox input name
     */
    protected string $inputName = "";

    public function __construct()
    {
        parent::__construct();

        $this->addClassName("TextTreeItem");

        $handle = new Container(false);
        $handle->setComponentClass("Handle");
        $button = new Container(false);
        $button->setComponentClass("Button");
        $handle->items()->append($button);
        $this->items()->append($handle);

        $node_checkbox = new Container(false);
        $node_checkbox->setComponentClass("node_checkbox");
        $node_checkbox->setRenderEnabled(false);
        $this->checkbox = new Input("checkbox");
        $node_checkbox->items()->append($this->checkbox);
        $this->items()->append($node_checkbox);

        $icon = new Container(false);
        $icon->setComponentClass("Icon");
        $this->icon = new Image();
        $icon->items()->append($this->icon);
        $this->items()->append($icon);

        //construct default empty action with no parameters
        $this->text_action = new Action();

        $this->items()->append($this->text_action);


        $this->actions = new ActionCollection();

        $renderer = function(ClosureComponent $cmp) : void {
            if ($this->actions->count() < 1) return;
            Action::RenderActions($this->actions->toArray());
        };

        $actions = new ClosureComponent($renderer);
        $actions->setComponentClass("node_actions");
        $this->items()->append($actions);

        //show related count in parentheses inside the label
        $this->render_related_count = true;
        $this->key_related_count = "related_count";



    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        if ($this->isChecked()) {
            $this->checkbox->setAttribute("checked");
        }
        else {
            $this->checkbox->removeAttribute("checked");
        }
        $this->checkbox->setAttribute("value", $this->getID());
    }

    public function enableCheckbox(string $inputName) : void
    {
        $this->checkbox->setName($inputName."[]");
        $this->items()->getByComponentClass("node_checkbox")?->setRenderEnabled(true);
    }

    public function disableCheckbox() : void
    {
        $this->checkbox->setName("");
        $this->items()->getByComponentClass("node_checkbox")?->setRenderEnabled(false);
    }

    public function isCheckboxEnabled() : bool
    {
        return $this->checkbox->isRenderEnabled();
    }

    public function getTextAction() : Action
    {
        return $this->text_action;
    }

    public function icon() : Image
    {
        return $this->icon;
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

    public function setActions(ActionCollection $actions) : void
    {
        $this->actions = $actions;
    }

    public function getActions(): ?ActionCollection
    {
        return $this->actions;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $this->text_action->setData($data);

        $this->actions->setData($data);

        if($this->icon->getStorageItem()) {
            $this->icon->getStorageItem()->setData($data);
        }
        else {
            $this->items()->getByComponentClass("Icon")?->setRenderEnabled(false);
        }


        if ($this->render_related_count && isset($this->data[$this->key_related_count])) {
            $this->text_action->setContents($this->label." (".$this->data[$this->key_related_count].")");
        }
        else {
            $this->text_action->setContents($this->label);
        }
    }


}