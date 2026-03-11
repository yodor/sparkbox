<?php

interface ISQLBinding {
    public function getBindingKey() : string;
    public function getBindingValue() : string;

}