<?php
include_once ("utils/IHeadScript.php");

class GTAG implements IHeadScript
{
    protected string $id = "";

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
        $script = ob_get_contents();
        ob_end_clean();
        return $script;
    }
}
