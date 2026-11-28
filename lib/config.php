<?php
enum Config
{

    const string MODULE_ADMIN = "MODULE_ADMIN";
    /**
     * Backend absolute cache path
     * Static(string): 'Parent folder of APP_PATH'/sparkcache/SITE_TITLE;
     */
    const string CACHE_PATH = "CACHE_PATH";
    /**
     * App/Site root - Server-side absolute path
     * Static(string)
     */
    const string APP_PATH = "APP_PATH";
    /**
     * Backend absolute path to sparkbox deployment
     */
    const string SPARKBOX_PATH = "SPARKBOX_PATH";
    /**
     * App root/deployment location URL - without ending slash relative to DOCUMENT_ROOT
     * Static(string)
     */
    const string LOCAL = "LOCAL";
    /**
     * SparkBox frontend classes URL (js/css/images) - without ending slash
     * Default to LOCAL/sparkfront
     * Static(string)
     */
    const string SPARK_LOCAL = "SPARK_LOCAL";
    /**
     * Administrative module URL - without ending slash
     * Default to LOCAL/admin
     * Static(string)
     */
    const string ADMIN_LOCAL = "ADMIN_LOCAL";
    /**
     * Storage backend access URL
     * Default to LOCAL/storage.php
     * Static(string)
     */
    const string STORAGE_URL = "STORAGE_URL";
    /**
     * Current protocol - https://
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
     * Equal to ini_get upload_max_filesize in byte
     * Static(int)
     */
    const string UPLOAD_MAX_FILESIZE = "UPLOAD_MAX_FILESIZE";


    /**
     * Domain for cookies.
     * Default '' - the site's own cookies (host-only, no Domain attribute): a parent domain site and its
     * subdomain sites never see each other's session or login. '.example.com' in config/settings.php shares the
     * cookies with every subdomain of example.com.
     * (string)
     */
    const string COOKIE_DOMAIN = "COOKIE_DOMAIN";

    /**
     * Prefix of the SparkBox cookie names (Session: the session 'sb_session', the logins, the language) - the
     * cookies of another application on the same host do not collide with them.
     * Default 'sb_'
     * (string)
     */
    const string COOKIE_PREFIX = "COOKIE_PREFIX";

    /**
     * App/Site current full absolute URL ending slash '/'
     * i.e. protocol + domain + app_location
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
     * Enlarge an image smaller than the size it is asked for (the storage of a photo at a size - ImageScaler).
     * The upload has its own (IMAGE_UPLOAD_UPSCALE).
     * Default(bool): false
     */
    const string IMAGE_SCALER_UPSCALE = "IMAGE_SCALER_UPSCALE";
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
     * Default(string): the requested host without port and leading 'www.' - set it in config/settings.php
     */
    const string DEFAULT_SERVICE_NAME = "DEFAULT_SERVICE_NAME";
    /**
     * The colour of the mail frame - the line above the logo, the buttons, the links (MailView; the site theme accent)
     * Default(string): #333333
     */
    const string MAIL_ACCENT_COLOR = "MAIL_ACCENT_COLOR";
    /**
     * The background of the logo band of the mail frame - for a logo made for a dark header ('#000000')
     * Default(string): #ffffff
     */
    const string MAIL_HEADER_BACKGROUND = "MAIL_HEADER_BACKGROUND";
    /**
     * Mailer class sender email ie 'office@domain.com'
     * Default(string): info@ + the requested host without port and leading 'www.'
     * Deployment: set it in config/settings.php - with PHPMAILER_ENABLED it is also the SMTP login, so it must be
     * a real mailbox (Hostinger: created in hPanel → Emails)
     */
    const string DEFAULT_SERVICE_EMAIL = "DEFAULT_SERVICE_EMAIL";
    /**
     * EmailValidator checks the MX record of the email domain
     * Default(bool): TRUE
     */
    const string EMAIL_DOMAIN_CHECK = "EMAIL_DOMAIN_CHECK";
    /**
     * More than one language: the visitor may choose a language of the 'languages' table, and the content
     * translations (translation_beans - trbean(), the admin 'Translate' of the fields) are read. The texts of the code
     * are translated anyway (the language packs - Translator). Default(bool): false
     */
    const string TRANSLATOR_ENABLED = "TRANSLATOR_ENABLED";
    /**
     * Enable general DB access and create initial connection
     * Default(bool): false
     */
    const string DB_ENABLED = "DB_ENABLED";

    /**
     * The locale of the site - the one language setting: its language ('bg-bg' - 'bg') is the language of the site
     * (the language packs <layer>/lang/<code>.php, the 'lang' of the pages, the language of a new 'languages' table),
     * the rest formats the dates and numbers (intl). A site in another language than English must set it.
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
     * The theme of the site: the tokens of sparkfront/css/themes/<THEME>.css (default "light")
     */
    const string THEME = "THEME";

    /**
     * The public frames (SparkSitePage, StorePage) send their local stylesheets and scripts in bundles - three
     * requests in place of tens (HTMLHead::enableBundles, AssetBundle). Default true; false in config/settings.php
     * sends the files one by one (debugging a stylesheet or a script)
     */
    const string ASSET_BUNDLES = "ASSET_BUNDLES";

    /**
     * App/Site Title
     * Default(string): Default SparkBox Site
     */
    const string SITE_TITLE = "SITE_TITLE";


    const string KEY_JSON_REQUEST = "JSONRequest";

    const string PASSWORD_TYPE = "PASSWORD_TYPE";
    const string PASSWORD_OPTIONS = "PASSWORD_OPTIONS";

    /**
     * Send mail with PHPMailer over authenticated SMTP (Hostinger) instead of PHP mail().
     * Requires APP_PATH/config/mailer.php (from dist/mailer.php.dist: host, port, the sender mailbox password) -
     * a missing file stops every page. SMTP login = DEFAULT_SERVICE_EMAIL.
     * Default(bool): FALSE
     */
    const string PHPMAILER_ENABLED = "PHPMAILER_ENABLED";
    const string PHPMAILER_SMTP_HOST = "PHPMAILER_SMTP_HOST";
    const string PHPMAILER_SMTP_PORT = "PHPMAILER_SMTP_PORT";
    const string PHPMAILER_SMTP_PASSWORD = "PHPMAILER_SMTP_PASSWORD";
}