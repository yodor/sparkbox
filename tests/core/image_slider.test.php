<?php
/**
 * ImageSlider: the markup the script and the css read - the items, the popup relation, the lazy photos, the dots
 * (photo dots at their own size), the arrows, the script; one photo: no arrows, no dots, no script
 */
include_once("components/Component.php");
include_once("components/ImageSlider.php");
include_once("storage/StorageItem.php");
include_once("utils/ImageSizes.php");
include_once("utils/url/URL.php");

Spark::Set(Config::STORAGE_ITEM_SLUG, false);
Spark::Set(Config::IMAGE_SCALER_OUTPUT_FORMAT, "image/webp");

function sliderHTML(ImageSlider $slider) : string
{
    ob_start();
    $slider->render();
    return ob_get_clean();
}

test("photos: popup items of the slider, the first eager, the others lazy, the asked size", function () {
    $slider = new ImageSlider();
    $slider->setName("photos");
    $slider->setPhotoSize(0, ImageSize::Thumbnail);
    foreach (array(11, 12, 13) as $id) $slider->addImage(new StorageItem($id, "PicsBean"), null, "Photo $id");
    $html = sliderHTML($slider);

    assertSame(3, substr_count($html, "class='ImagePopup item'"), "three items");
    assertSame(3, substr_count($html, "relation='photos'"), "the popup shows the photos of this slider");
    assertSame(2, substr_count($html, "loading='lazy'"), "all but the first are lazy");
    assertTrue(!str_contains($html, "fetchpriority"), "no priority unless asked");
    assertTrue(str_contains($html, "width=0&amp;height=256"), "the asked size");
    assertTrue(str_contains($html, "alt='Photo 12'"), "the title as alt");
    assertSame(2, substr_count($html, "nav-arrow"), "the arrows");
    assertSame(3, substr_count($html, "class='dot'"), "plain dots");
    assertTrue(str_contains($html, "ImageSlider.Start(\"photos\""), "the script");
});

test("photo dots at their own size, a link instead of the popup, the main photo first", function () {
    $slider = new ImageSlider();
    $slider->setPhotoSize(640, 640);
    $slider->setDots(SliderDots::IMAGE);
    $slider->setPriority(true);
    $link = new URL("/details.php");
    $slider->addImage(new StorageItem(21, "PicsBean"), $link);
    $slider->addImage(new StorageItem(22, "PicsBean"), $link);
    $html = sliderHTML($slider);

    assertSame(2, substr_count($html, "width=128&amp;height=128"), "the dots at Small");
    assertSame(2, substr_count($html, "width=640&amp;height=640"), "the photos at the asked size");
    assertSame(2, substr_count($html, "href='/details.php'"), "links");
    assertSame(1, substr_count($html, "fetchpriority='high'"), "the main photo");
    assertTrue(preg_match("/name='ImageSlider\\d+'/", $html) === 1, "a name is given when none is set");
});

test("one photo: no arrows, no dots, no script; fade and strip are classes", function () {
    $slider = new ImageSlider();
    $slider->setEffect(SliderEffect::FADE);
    $slider->setStrip(true);
    $slider->addImage(new StorageItem(31, "PicsBean"));
    $html = sliderHTML($slider);

    assertTrue(!str_contains($html, "nav-arrow"), "no arrows");
    assertTrue(!str_contains($html, "class='dots'"), "no dots");
    assertTrue(!str_contains($html, "ImageSlider.Start"), "no script");
    assertTrue(str_contains($html, "strip") && str_contains($html, "fade"), "the classes");

    $slider->clear();
    assertSame(0, $slider->count(), "cleared");
});
