<?php

interface IPhotoRenderer
{
    public function setPhotoSize(int $width, int $height): void;

    public function getPhotoWidth(): int;

    public function getPhotoHeight(): int;
}

?>
