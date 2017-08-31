<?php
include_once("lib/pages/SitePage.php");
include_once("lib/components/AbstractResultView.php");
include_once("lib/components/renderers/items/TextItemRenderer.php");
include_once("lib/components/renderers/IItemRenderer.php");

include_once("lib/components/PageResultsPanel.php");

class ListView extends AbstractResultView implements IHeadRenderer
{

    protected $item_renderer;

    public function __construct(SQLIterator $itr)
    {
        parent::__construct($itr);
    }
    
    public function renderScript()
    {}

    public function renderStyle()
    {
        echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/ListView.css' type='text/css' >";
        echo "\n";
    }

    public function setItemRenderer(IItemRenderer $renderer)
    {
        $this->item_renderer = $renderer;
        $this->item_renderer->setParent($this);
    }
    public function getItemRenderer() 
    {
        return $this->item_renderer;
    }

    public function startRender() 
    {
        parent::startRender();

        echo "<div class='viewport'>";
        
        if (!$this->paginators_enabled) {
            if (strlen($this->caption)>0) {
                    echo "<div class='caption'>";
                    echo $this->caption;
                    echo "</div>";
            }
        }
        
    }

    public function finishRender() 
    {
        echo "<div class=clear></div>";
        echo "</div>";

        parent::finishRender();
    }


    public function renderImpl()
    {

        if (!$this->item_renderer)throw new Exception("ItemRenderer not set");

        $v = new ValueInterleave("even","odd");

        $this->position_index = 0;

        while ($this->itr->haveMoreResults($row)){
                
            $cls = $v->value();

            //$this->item_renderer->setClassName($cls);

            $this->item_renderer->setItem($row);
            $this->item_renderer->render();
            $this->item_renderer->renderSeparator($this->position_index, $this->total_rows);

            $this->position_index++;

            $v->advance();
        }
    }
    
    public function getIterator()
    {
        return $this->itr;
    }

}
?>
