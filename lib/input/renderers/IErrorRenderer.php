<?php

interface IErrorRenderer 
{
  const MODE_TOOLTIP = 1;
  const MODE_SPAN = 2;
  const MODE_NONE = 0;

  public function processErrorAttributes();

}
?>