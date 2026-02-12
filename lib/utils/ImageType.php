<?php
enum ImageType : string
{
    case TYPE_JPG = "image/jpg";
    case TYPE_JPEG = "image/jpeg";
    case TYPE_PNG = "image/png";
    case TYPE_WEBP = "image/webp";
}

enum ScaleMode : int
{
    case MODE_FULL = 0;
    case MODE_CROP = 1;
    case MODE_THUMB = 2;
}