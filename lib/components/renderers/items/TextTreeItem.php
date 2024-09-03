<?php
include_once("components/renderers/items/NestedSetItem.php");
include_once("utils/IActionCollection.php");
include_once("utils/ActionCollection.php");

include_once("components/Action.php");

class TextTreeItem extends NestedSetItem implements IActionCollection, IPhotoRenderer
{

    protected $icon_width = -1;
    protected $icon_height = -1;

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

    protected $icon = null;

    /**
     * @var string checkbox input name
     */
    protected string $inputName = "";

    public function __construct()
    {
        parent::__construct();

        //construct default empty action with no parameters
        $this->text_action = new Action("TextTreeItemAction");
        $this->text_action->translation_enabled = FALSE;

        $this->actions = new ActionCollection();

        //show related count in parentheses inside the label
        $this->render_related_count = true;
        $this->key_related_count = "related_count";
    }

    public function enableCheckbox(string $inputName) : void
    {
        $this->inputName = $inputName;
    }

    public function disableCheckbox() : void
    {
        $this->inputName = "";
    }

    public function isCheckboxEnabled() : bool
    {
        return (!empty($this->inputName));
    }

    public function setIcon(StorageItem $si)
    {
        $this->icon = $si;
    }
    public function getIcon() :?StorageItem
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

        $render = function(Action $action, int|string|null $idx)  {
            $action->render();
        };
        $this->actions->each($render);

        echo "</div>";

    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $this->text_action->setData($data);

        $this->actions->setData($data);

        if ($this->icon instanceof StorageItem) {
            $this->icon->setData($data);
        }
    }

    protected function renderHandle()
    {
        echo "<div class='Handle'>";
        echo "<div class='Button'></div>";
        echo "</div> ";
    }

    public function renderIcon()
    {
        if ($this->icon instanceof StorageItem) {
            echo "<span class='Icon'>";
            if ($this->icon->id > 0) {
                $src = $this->icon->hrefImage($this->icon_width, $this->icon_height);
                echo "<img src='$src'>";
            }
            echo "</span>";
        }
    }

    public function renderCheckbox()
    {
        if ($this->isCheckboxEnabled()) {
            echo "<div class='node_checkbox'>";

            echo "<input type='checkbox' value='{$this->getID()}' name='{$this->inputName}[]'  ";
            if ($this->isChecked()) echo "CHECKED";
            echo ">";
            echo "</div>";

        }
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
        $this->renderCheckbox();
        $this->renderIcon();
        $this->renderText();
        $this->renderActions();
    }

    public function setPhotoSize(int $width, int $height)
    {
        $this->icon_width = $width;
        $this->icon_height = $height;
    }

    public function getPhotoWidth(): int
    {
        return $this->icon_width;
    }

    public function getPhotoHeight(): int
    {
        return $this->icon_height;
    }
}
