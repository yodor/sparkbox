<?php
include_once("objects/ActionCollection.php");

/**
 * A content that gives its frame page actions (add, back, edit ...) and filters (search, filter forms) - the admin
 * frame shows them in its header. The bean contents (TemplateBeanContent) and the admin views of single records
 * (OrderDetails) are such contents.
 */
interface IContentActions
{
    public function fillPageActions(ActionCollection $collection): void;

    public function fillPageFilters(Container $filters): void;
}
