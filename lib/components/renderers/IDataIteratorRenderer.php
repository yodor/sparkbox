<?php
include_once("components/renderers/items/DataIteratorItem.php");
include_once("iterators/IDataIterator.php");

interface IDataIteratorRenderer
{
    public function setIterator(IDataIterator $query): void;

    public function getIterator(): IDataIterator;

    public function setItemRenderer(DataIteratorItem $item): void;

    public function getItemRenderer(): ?DataIteratorItem;

}