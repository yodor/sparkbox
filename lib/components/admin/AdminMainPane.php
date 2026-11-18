<?php
include_once("components/Container.php");
include_once("components/admin/AdminHelpPane.php");

/**
 * The main pane of the admin frame: the header (page actions, title, filters), the base (the help pane, then the
 * content of the path), the footer
 */
class AdminMainPane extends Container
{
    protected Container $actionsTitle;
    protected Container $actions;
    protected Container $title;
    protected Container $filters;
    protected Container $base;
    protected AdminHelpPane $helpPane;

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("mainPane");

        $header = new Container(false);
        $header->setComponentClass("header");
        $this->items()->append($header);

        $this->actionsTitle = new Container(false);
        $this->actionsTitle->setComponentClass("actions_title");
        $header->items()->append($this->actionsTitle);

        $this->actions = new Container(false);
        $this->actions->setComponentClass("actions");
        $this->actionsTitle->items()->append($this->actions);

        $this->title = new Container(false);
        $this->title->setComponentClass("title");
        $this->actionsTitle->items()->append($this->title);

        $this->filters = new Container(false);
        $this->filters->setComponentClass("filters");
        $header->items()->append($this->filters);

        $this->base = new Container(false);
        $this->base->setComponentClass("base");
        $this->items()->append($this->base);

        $this->helpPane = new AdminHelpPane();
        $this->base->items()->append($this->helpPane);

        $footer = new Container(false);
        $footer->setComponentClass("footer");
        $this->items()->append($footer);
    }

    /**
     * The row of the page actions and the title (the help action goes at its end)
     */
    public function actionsTitle() : Container
    {
        return $this->actionsTitle;
    }

    /**
     * The page actions (back, the content's actions)
     */
    public function actions() : Container
    {
        return $this->actions;
    }

    public function title() : Container
    {
        return $this->title;
    }

    /**
     * The filters of the content (IContentActions::fillPageFilters)
     */
    public function filters() : Container
    {
        return $this->filters;
    }

    /**
     * Where the content goes (after the help pane)
     */
    public function base() : Container
    {
        return $this->base;
    }

    public function helpPane() : AdminHelpPane
    {
        return $this->helpPane;
    }

    public function stylesheets() : array
    {
        return array_merge(array(Spark::Get(Config::SPARK_LOCAL) . "/css/admin/AdminMainPane.css"), $this->helpPane->stylesheets());
    }
}
