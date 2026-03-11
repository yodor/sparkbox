<?php

interface IBindingCollection {

    /**
     * Array of ":name"=>"real value"
     * @return array
     */
    public function getBindings() : array;

}