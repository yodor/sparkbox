<?php
include_once("utils/ImageSizes.php");

interface IPhotoRenderer
{
    public function setPhotoSize(int|IImageSize $width, int|IImageSize $height = 0) : void;

    public function getPhotoWidth(): int;

    public function getPhotoHeight(): int;
}