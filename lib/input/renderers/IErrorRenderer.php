<?php

interface IErrorRenderer
{
    const int MODE_TOOLTIP = 1;
    const int MODE_SPAN = 2;
    const int MODE_NONE = 0;

    public function setErrorRenderMode(int $mode) : void;

    public function getErrorRenderMode() : int;
}