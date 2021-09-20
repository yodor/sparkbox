<?php
include_once ("utils/IHeadScript.php");

class GTAG implements IHeadScript
{
    protected $id;

    public function __construct()
    {
    }

    public function setID(string $id)
    {
        $this->id = $id;
    }

    public function script(): string
    {
        ob_start();
        echo "<!-- Start GTAG script for ID: $this->id -->";
        ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php
        echo $this->id; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('consent', 'default', {
                'ad_storage': 'consent',
                'analytics_storage': 'consent'
                'wait_for_update': 5000
            });

            gtag('js', new Date());
            gtag('config', '<?php echo $this->id;?>');
        </script>
        <?php
        echo "\r\n<!-- End GTAG script for ID: $this->id  -->\r\n";
        $script = ob_get_contents();
        ob_end_clean();
        return $script;
    }
}