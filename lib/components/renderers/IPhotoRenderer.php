<?php

interface IPhotoRenderer
{

    public function setPhotoSize(int $width, int $height);

    public function getPhotoWidth();

    public function getPhotoHeight();
}

?>