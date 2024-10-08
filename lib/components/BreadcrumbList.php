<?php
include_once("components/Container.php");

class BreadcrumbListItem extends Component {


    public function __construct()
    {
        parent::__construct(false);
        $this->tagName = "LI";

        $this->setAttribute("itemscope", "");
        $this->setAttribute("itemprop", "itemListElement");
        $this->setAttribute("itemtype", "https://schema.org/ListItem");
    }

}

class BreadcrumbList extends Container implements IHeadContents
{

    protected BreadcrumbListItem $renderer;

    public function __construct()
    {
        $this->tagName = "UL";

        parent::__construct(false);

        $this->setAttribute("itemscope", "");
        $this->setAttribute("itemtype", "https://schema.org/BreadcrumbList");

        $this->renderer = new BreadcrumbListItem();

    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/BreadcrumbList.css";
        return $arr;
    }

    protected function renderImpl()
    {
        $iterator = $this->items->iterator();
        $pos = 1;
        while ($act = $iterator->next()) {

            if (!($act instanceof Action)) {
                var_dump($act);
                continue;
            }

            $this->renderer->startRender();

            $act->setAttribute("itemprop", "item");
            $act->setContents("<span itemprop='name'>".$act->getAttribute("action")."</span>");
            $act->render();

            echo "<meta itemprop='position' content='".($pos++)."' />";

            $this->renderer->finishRender();

        }
    }

}
?>
