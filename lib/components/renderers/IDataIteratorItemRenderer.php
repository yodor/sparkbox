<?php
include_once("components/renderers/items/DataIteratorItem.php");
include_once("iterators/IDataIterator.php");

interface IDataIteratorItemRenderer
{
    public function setItemIterator(IDataIterator $query);


    public function getItemIterator(): IDataIterator;


    public function setItemRenderer(DataIteratorItem $item);


    public function getItemRenderer() : ?DataIteratorItem;

}