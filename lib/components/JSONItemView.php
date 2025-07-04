<?php
include ("components/ItemView.php");

class ItemViewResponder extends JSONResponder
{

    /**
     * @var JSONItemView
     */
    protected JSONItemView $view;

    public function __construct(JSONItemView $view)
    {
        parent::__construct();
        $this->view = $view;
    }

    public function _renderItems(JSONResponse $resp)
    {
        debug("... start");
        $this->view->processResponse($resp);
        debug("... finish");
    }

}

class JSONItemView extends ItemView
{
    /**
     * @var ItemViewResponder
     */
    protected ItemViewResponder $responder;

    protected Button $button;

    protected Container $container;

    public function __construct(?IDataIterator $itr = NULL)
    {
        parent::__construct($itr);
        $this->responder = new ItemViewResponder($this);
        $this->getFooter()->setRenderEnabled(false);

        $this->container = new Container();
        $this->container->setClassName("loader");
        $this->button = new Button();
        $this->button->setContents("<label>".tr("Show More")."</label>"."<div class='progress-bar'><div class='circle border'></div></div>");

        $this->button->setAttribute("onClick", "javascript:loadMoreResults(this)");
        $this->button->setName("fetchResults");

        $this->container->items()->append($this->button);
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/JSONItemView.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/JSONItemView.js";
        return $arr;
    }

    public function startRender()
    {
        AbstractResultView::startRender();
        $this->viewport->startRender();
    }

    public function finishRender()
    {
        $this->viewport->finishRender();

        if ($this->paginator->currentPage() < $this->paginator->totalPages()-1) {
            $this->container->render();
        }

        AbstractResultView::finishRender();
    }

    public function processResponse(JSONResponse $resp)
    {
        //$this->processIterator();
        ob_start();
        $this->renderItems();
        $resp->html = ob_get_contents();
        ob_end_clean();
    }

}
?>
