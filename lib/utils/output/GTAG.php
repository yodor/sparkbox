<?php
include_once("utils/output/OutputScript.php");

class GTAG extends OutputScript
{
    protected string $id = "";

    public function __construct()
    {
        parent::__construct();
    }

    public function setID(string $id) : void
    {
        $this->id = $id;
    }

    protected function fillBuffer() : void
    {
        echo "\n<!-- Start GTAG script for ID: $this->id -->\n";
        ?>
        <script src="https://www.googletagmanager.com/gtag/js?id=<?php echo $this->id; ?>"></script>

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', '<?php echo $this->id;?>');

            gtag('consent', 'default', {
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'ad_storage': 'granted',
                'analytics_storage': 'granted',
                'wait_for_update': 5000
            });

        </script>
        <?php
        echo "\n<!-- End GTAG script for ID: $this->id  -->\n";

    }
}
