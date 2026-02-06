<?php
class Config {

    /**
     * Backend cache path
     * Static(string): 'Parent folder of install_path'/sparkcache/SITE_TITLE;
     */
    const string CACHE_PATH = "CACHE_PATH";
    /**
     * App/Site root deployment location - Server-side path
     * Static(string)
     */
    const string INSTALL_PATH = "INSTALL_PATH";
    /**
     * App/Site root deployment - HTTP accessible - without ending slash
     * Static(string)
     */
    const string LOCAL = "LOCAL";
    /**
     * SparkBox frontend classes location (js/css/images) - HTTP accessible - without ending slash
     * Default to LOCAL/sparkfront
     * Static
     */
    const string SPARK_LOCAL = "SPARK_LOCAL";
    /**
     * Administrative module location - HTTP accessible - without ending slash
     * Default to LOCAL/admin
     * Static(string)
     */
    const string ADMIN_LOCAL = "ADMIN_LOCAL";
    /**
     * Data bean storage location - HTTP accessible - without ending slash
     * Default to LOCAL/storage.php
     * Static(string)
     */
    const string STORAGE_LOCAL = "STORAGE_LOCAL";
    /**
     * Current protocol
     * Static(string)
     */
    const string SITE_PROTOCOL = "SITE_PROTOCOL";
    /**
     * Current domain
     * Default to $_SERVER["HTTP_HOST"]
     * Static(string)
     */
    const string SITE_DOMAIN = "SITE_DOMAIN";

    /**
     * Equal to ini_get post_max_size in byte
     * Static(int)
     */
    const string UPLOAD_MAX_SIZE = "UPLOAD_MAX_SIZE";

    /**
     * Equal to ini_get memory_limit in bytes.
     * Static(int)
     */
    const string MEMORY_LIMIT = "MEMORY_LIMIT";

    /**
     * Domain for cookies.
     * Default '.SITE_DOMAIN'
     * Static(string)
     */
    const string COOKIE_DOMAIN = "COOKIE_DOMAIN";

    /**
     * App/Site current URL without path and ending slash '/'
     * Static(string)
     */
    const string SITE_URL = "SITE_URL";

    /**
     * Used in constructing breadcrumbs in site titles
     * Default(string) '::'
     */
    const string TITLE_PATH_SEPARATOR = "TITLE_PATH_SEPARATOR";

    /**
     * Uploaded images are clamped to this width depending on IMAGE_UPLOAD_DOWNSCALE and IMAGE_UPLOAD_UPSCALE flags
     * Default(int): 1280
     */
    const string IMAGE_UPLOAD_DEFAULT_WIDTH = "IMAGE_UPLOAD_DEFAULT_WIDTH";
    /**
     * Uploaded images are clamped to this height depending on IMAGE_UPLOAD_DOWNSCALE and IMAGE_UPLOAD_UPSCALE flags
     * Default(int): 720
     */
    const string IMAGE_UPLOAD_DEFAULT_HEIGHT = "IMAGE_UPLOAD_DEFAULT_HEIGHT";

    /**
     * Enable down scaling of uploaded images.
     * Clam width and height to values specified in (IMAGE_UPLOAD_DEFAULT_WIDTH,IMAGE_UPLOAD_DEFAULT_HEIGHT)
     * Default(bool): true
     */
    const string IMAGE_UPLOAD_DOWNSCALE = "IMAGE_UPLOAD_DOWNSCALE";

    /**
     * Enable up scaling of uploaded images.
     * Clam width and height to values specified in (IMAGE_UPLOAD_DEFAULT_WIDTH,IMAGE_UPLOAD_DEFAULT_HEIGHT)
     * Default(bool): false
     */
    const string IMAGE_UPLOAD_UPSCALE = "IMAGE_UPLOAD_UPSCALE";

    /**
     * Store uploaded images with specified quality value
     * Default(int): 80
     */
    const string IMAGE_UPLOAD_STORE_QUALITY = "IMAGE_UPLOAD_STORE_QUALITY";

    /**
     * Storage backend image output format
     * Default(string): image/webp
     */
    const string IMAGE_SCALER_OUTPUT_FORMAT = "IMAGE_SCALER_OUTPUT_FORMAT";
    /**
     * Storage backend image output quality
     * Default(int): 80
     */
    const string IMAGE_SCALER_OUTPUT_QUALITY = "IMAGE_SCALER_OUTPUT_QUALITY";

    /**
     * Filename of the image to use as watermark
     * Default(string): ""
     */
    const string IMAGE_SCALER_WATERMARK_FILENAME = "IMAGE_SCALER_WATERMARK_FILENAME";

    /**
     * Enable watermarking of backend images
     * Default(bool): false
     */
    const string IMAGE_SCALER_WATERMARK_ENABLED = "IMAGE_SCALER_WATERMARK_ENABLED";
    /**
     *  Watermark position
     *  Default(int): WatermarkPosition::BOTTOM_RIGHT->value
     */
    const string IMAGE_SCALER_WATERMARK_POSITION = "IMAGE_SCALER_WATERMARK_POSITION";

    /**
     * Mailer class sender name ie 'My Site Office'
     * Default(string): SITE_DOMAIN
     */
    const string DEFAULT_SERVICE_NAME = "DEFAULT_SERVICE_NAME";
    /**
     * Mailer class sender email ie 'office@domain.com'
     * Default(string): info@SITE_DOMAIN
     */
    const string DEFAULT_SERVICE_EMAIL = "DEFAULT_SERVICE_EMAIL";
    /**
     * Enable or disable Automatic language translations
     * Used in tr() and trbean()
     * Default(bool): false
     */
    const string TRANSLATOR_ENABLED = "TRANSLATOR_ENABLED";
    /**
     * Enable general DB access and create initial connection
     * Default(bool): false
     */
    const string DB_ENABLED = "DB_ENABLED";

    /**
     * Language of site - should match DEFAULT_LANGUAGE_ISO3
     * Default(string): english
     */
    const string DEFAULT_LANGUAGE = "DEFAULT_LANGUAGE";
    /**
     * Language of site ISO3 code should match DEFAULT_LANGUAGE
     * Default(string): eng
     */
    const string DEFAULT_LANGUAGE_ISO3 = "DEFAULT_LANGUAGE_ISO3";
    /**
     * Locale of site
     * Default(string): en-us
     */
    const string DEFAULT_LOCALE = "DEFAULT_LOCALE";

    /**
     * Enable caching of all images and data from the backend storage class
     * Default(bool): true
     */
    const string STORAGE_CACHE_ENABLED = "STORAGE_CACHE_ENABLED";

    /**
     * Enable caching of heavy components
     * Default(bool): false
     */
    const string PAGE_CACHE_ENABLED = "PAGE_CACHE_ENABLED";

    /**
     * Time in seconds to expire the cached page components (default 24 hours)
     * Default(int): 86400
     */
    const string PAGE_CACHE_TTL = "PAGE_CACHE_TTL";

    /**
     * PageCache cleanup routine is executed each 3600 seconds
     * Default(int): 3600
     */
    const string PAGE_CACHE_CLEANUP_DELTA = "PAGE_CACHE_CLEANUP_DELTA";

    //filesystem or database
    const string BEAN_CACHE_BACKEND = "BEAN_CACHE_BACKEND";
    const string PAGE_CACHE_BACKEND = "PAGE_CACHE_BACKEND";

    /**
     * Slugify storage backend URLs
     * Default(bool): false
     */
    const string STORAGE_ITEM_SLUG = "STORAGE_ITEM_SLUG";

    /**
     * Transliterate when slugging
     * Default(bool): true
     */
    const string SLUG_TRANSLITERATE = "SLUG_TRANSLITERATE";

    /**
     * Transliteration ID
     * Default(string): Bulgarian-Latin/BGN
     */
    const string TRANSLITERATOR_ID = "TRANSLITATOR_ID";

    /**
     * Timezone
     * Default(string): Europe/Sofia
     */
    const string TIMEZONE = "TIMEZONE";

    /**
     * App/Site Title
     * Default(string): Default SparkBox Site
     */
    const string SITE_TITLE = "SITE_TITLE";

}

?>