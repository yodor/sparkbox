<?php

interface IBindingCollection {

    /**
     * Collect and return Array of key=>value to be used in prepared statement binding.
     *
     * Format is bindingKey=>value ie ":name"=>"value"
     *
     * @return array
     */
    public function getBindings() : array;

}