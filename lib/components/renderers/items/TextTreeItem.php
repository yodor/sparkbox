<?php
include_once("components/renderers/items/NestedSetItem.php");
include_once("utils/IActionCollection.php");
include_once("objects/ActionCollection.php");

include_once("components/Action.php");

class TextTreeItem extends NestedSetItem implements IActionCollection, IPhotoRenderer
{

    protected int $icon_width = -1;
    protected int $icon_height = -1;

    /**
     * @var ActionCollection
     */
    protected ActionCollection $actions;

    /**
     * @var Action
     */
    protected Action $text_action;

    /**
     * @var bool
     */
    protected bool $render_related_count = false;

    /**
     * @var string
     */
    protected string $key_related_count = "";

    protected ?StorageItem $icon = null;

    /**
     * @var string checkbox input name
     */
    protected string $inputName = "";

    public function __construct()
    {
        parent::__construct(false);

        //construct default empty action with no parameters
        $this->text_action = new Action();
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

    public function setIcon(StorageItem $si) : void
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

    public function setTextAction(Action $text_action) : void
    {
        $this->text_action = $text_action;
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

        if ($this->icon instanceof StorageItem) {
            $this->icon->setData($data);
        }

        if ($this->render_related_count && isset($this->data[$this->key_related_count])) {
            $this->text_action->setContents($this->label." (".$this->data[$this->key_related_count].")");
        }
        else {
            $this->text_action->setContents($this->label);
        }
    }

    protected function renderHandle() : void
    {
        echo "<div class='Handle'>";
        echo "<div class='Button'></div>";
        echo "</div> ";
    }

    protected function renderIcon() : void
    {
        if (is_null($this->icon)) return;

        echo "<span class='Icon'>";
        if ($this->icon->id > 0) {
            $src = $this->icon->hrefImage($this->icon_width, $this->icon_height);
            echo "<img src='$src'>";
        }
        echo "</span>";

    }

    protected function renderCheckbox() : void
    {
        if (!$this->isCheckboxEnabled()) return;

        echo "<div class='node_checkbox'>";

        echo "<input type='checkbox' value='{$this->getID()}' name='{$this->inputName}[]'  ";
        if ($this->isChecked()) echo "CHECKED";
        echo ">";

        echo "</div>";


    }

    protected function renderText() : void
    {
        $this->text_action->render();
    }

    protected function renderActions() : void
    {
        if ($this->actions->count() < 1) return;

        echo "<div class='node_actions'>";
        Action::RenderActions($this->actions->toArray());
        echo "</div>";
    }

    protected function renderImpl()
    {
        $this->renderHandle();
        $this->renderCheckbox();
        $this->renderIcon();
        $this->renderText();
        $this->renderActions();
    }

    public function setPhotoSize(int $width, int $height): void
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
