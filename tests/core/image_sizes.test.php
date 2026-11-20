<?php
/**
 * Image sizes: the registry (ImageSizes), the scaler bounds (each number an upper limit, proportions kept, never
 * enlarged unless allowed), the storage answering only enabled sizes (301 otherwise), the upload clamp.
 */
include_once("utils/ImageSizes.php");
include_once("utils/ImageScaler.php");
include_once("storage/DataBuffer.php");
include_once("storage/ImageStorageObject.php");
include_once("input/DataInputFactory.php");
include_once("input/validators/ImageUploadValidator.php");
include_once(__DIR__ . "/../harness/http.php");

Spark::Set(Config::IMAGE_SCALER_OUTPUT_FORMAT, "image/png");
Spark::Set(Config::IMAGE_SCALER_OUTPUT_QUALITY, 80);
Spark::Set(Config::IMAGE_SCALER_UPSCALE, false);
Spark::Set(Config::IMAGE_SCALER_WATERMARK_ENABLED, false);
Spark::Set(Config::IMAGE_SCALER_WATERMARK_POSITION, 4);

function pngOf(int $width, int $height) : string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 40, 120, 200));
    ob_start();
    imagepng($image);
    return ob_get_clean();
}

function scaledSize(int $boundWidth, int $boundHeight, int $width, int $height, bool $upscale = false) : string
{
    $buffer = new DataBuffer();
    $buffer->setData(pngOf($width, $height));
    $scaler = new ImageScaler($boundWidth, $boundHeight);
    $scaler->setUpscaleEnabled($upscale);
    $scaler->process($buffer);
    $image = imagecreatefromstring($buffer->data());
    return imagesx($image) . "x" . imagesy($image);
}

test("registry: the core sizes, the next larger enabled size, a disabled size stays disabled", function () {
    ImageSizes::Enable(...ImageSize::cases());
    assertSame(array(64, 128, 256), array_values(array_intersect(ImageSizes::Enabled(), array(64, 128, 256))));
    assertSame(128, ImageSizes::Allowed(128), "an enabled size");
    assertSame(128, ImageSizes::Allowed(100), "the next larger");
    assertSame(0, ImageSizes::Allowed(0), "no limit");
    $largest = max(ImageSizes::Enabled());
    assertSame($largest, ImageSizes::Allowed($largest + 1000), "above them all: the largest");
    assertSame(64, ImageSizes::Pixels(ImageSize::Tiny));
    assertSame(0, ImageSizes::Pixels(-1));

    ImageSizes::Enable(1200);
    assertTrue(ImageSizes::IsEnabled(1200), "a size of the site");
    ImageSizes::Disable(1200);
    ImageSizes::Enable(1200);
    assertTrue(!ImageSizes::IsEnabled(1200), "a disabled size is not enabled again by a later layer");
});

test("scaler: each number an upper limit, proportions kept, nothing cut, never enlarged", function () {
    if (!function_exists("imagecreatetruecolor")) skip("gd not available");
    assertSame("256x384", scaledSize(256, 0, 600, 900), "width bound - a portrait");
    assertSame("171x256", scaledSize(0, 256, 600, 900), "height bound - a portrait");
    assertSame("171x256", scaledSize(256, 256, 600, 900), "box - a portrait fits by its height");
    assertSame("256x171", scaledSize(256, 256, 900, 600), "box - a landscape fits by its width");
    assertSame("100x50", scaledSize(256, 0, 100, 50), "smaller than the bound: as it is");
    assertSame("256x128", scaledSize(256, 0, 100, 50, true), "enlarged only when allowed");
    assertSame("600x900", scaledSize(0, 0, 600, 900), "no bounds: the original");
});

test("storage: an enabled size is answered, another one 301 to the allowed address", function () {
    if (!function_exists("imagecreatetruecolor")) skip("gd not available");
    $pdo = TestPDO();
    $object = new ImageStorageObject();
    $object->setFilename("blue.png");
    $object->setData(pngOf(600, 900));
    $pdo->prepare("INSERT INTO pics (photo) VALUES (?)")->execute(array(serialize($object)));
    $id = (int)$pdo->lastInsertId();
    try {
        //query style
        [$status, , $body] = httpRequest("storage.php", "cmd=image&class=PicsBean&id=$id&width=128&height=0");
        assertSame(200, $status, "enabled width");
        $image = imagecreatefromstring($body);
        assertSame("128x192", imagesx($image) . "x" . imagesy($image), "scaled by the width, proportions kept");

        [$status, $headers] = httpRequest("storage.php", "cmd=image&class=PicsBean&id=$id&width=100&height=0");
        assertSame(301, $status, "not enabled");
        assertTrue(str_contains($headers["location"] ?? "", "width=128"), "to the next larger: " . ($headers["location"] ?? ""));

        [$status, $headers] = httpRequest("storage.php", "cmd=image&class=PicsBean&id=$id&width=128&height=64");
        assertSame(301, $status, "a box that is not square");
        assertTrue(str_contains($headers["location"] ?? "", "width=128") && str_contains($headers["location"] ?? "", "height=0"), "to the width: " . ($headers["location"] ?? ""));

        //slug style: the size segments of the path
        $route = "image/PicsBean/$id/300/0/blue.$id.webp";
        [$status, $headers] = httpRequest("storage.php", "route=" . $route, array(), "/assets/$route");
        assertSame(301, $status, "slug: not enabled");
        $largest = max(ImageSizes::Enabled());
        assertSame("/assets/image/PicsBean/$id/" . ImageSizes::Allowed(300) . "/0/blue.$id.webp", $headers["location"] ?? "", "slug: the size segment");
        assertTrue(ImageSizes::Allowed(300) === $largest || ImageSizes::Allowed(300) >= 300, "the next larger or the largest");
    }
    finally {
        $pdo->exec("DELETE FROM pics WHERE pid = $id");
    }
});

test("upload clamp: the original fits inside the box, not enlarged by default", function () {
    if (!function_exists("imagecreatetruecolor")) skip("gd not available");
    Spark::Set(Config::IMAGE_UPLOAD_DOWNSCALE, true);
    Spark::Set(Config::IMAGE_UPLOAD_UPSCALE, false);
    Spark::Set(Config::IMAGE_UPLOAD_STORE_QUALITY, 80);
    Spark::Set(Config::IMAGE_UPLOAD_DEFAULT_WIDTH, 1280);
    Spark::Set(Config::IMAGE_UPLOAD_DEFAULT_HEIGHT, 720);
    $validator = new ImageUploadValidator();
    $validator->setResizedSize(1280, 720);

    $clamp = function (int $width, int $height) use ($validator) : string {
        $object = new ImageStorageObject();
        $object->setData(pngOf($width, $height));
        $validator->processObject($object);
        $image = imagecreatefromstring($object->data());
        return imagesx($image) . "x" . imagesy($image) . " " . $object->getWidth() . "x" . $object->getHeight();
    };
    assertSame("1280x320 1280x320", $clamp(2000, 500), "a wide one fits by its width (it was scaled to the height and grew)");
    assertSame("480x720 480x720", $clamp(1000, 1500), "a tall one fits by its height");
    assertSame("640x360 640x360", $clamp(640, 360), "smaller: as it is");
});
