<?php

interface IItemRenderer
{
    public function setItem($item);

    public function getItem();

    public function renderSeparator($idx_curr, $items_total);


}

?>