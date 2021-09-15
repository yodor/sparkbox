<?php
include_once("components/Container.php");

class BreadcrumbListItem extends Component {


    public function __construct()
    {
        $this->tagName = "LI";
        parent::__construct();

        $this->setAttribute("itemscope", "");
        $this->setAttribute("itemprop", "itemListElement");
        $this->setAttribute("itemtype", "https://schema.org/ListItem");
    }

}

class BreadcrumbList extends Container implements IHeadContents
{

    protected $renderer = NULL;

    public function __construct()
    {
        $this->tagName = "UL";

        parent::__construct();

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
        foreach ($this->items as $pos=>$act) {
            if ($act instanceof Action) {
                $this->renderer->startRender();

                $act->setAttribute("itemprop", "item");
                $act->setContents("<span itemprop='name'>".$act->getAttribute("action")."</span>");
                $act->render();

                echo "<meta itemprop='position' content='".($pos+1)."' />";

                $this->renderer->finishRender();
            }
        }
    }

}
?>