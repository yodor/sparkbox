<?php
include_once("lib/components/Component.php");

class FBPixel extends Component
{
    protected $pixelID = "";
    protected $trackEvents = NULL;

    public function __construct($pixelID)
    {
        parent::__construct();
        $this->pixelID = $pixelID;//685152751918503
        $this->trackEvents = array();

    }

    public function addTrackEvent($eventName, $eventParam)
    {
        $this->trackEvents[$eventName] = $eventParam;
    }

    public function requiredScript()
    {
        ob_start();
        ?>
        <!-- Facebook Pixel Code -->
        <script>
            !function (f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function () {
                    n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '<?php echo $this->pixelID;?>');
            fbq('track', 'PageView');
            <?php
            foreach ($this->trackEvents as $eventName => $eventParam) {
                echo "fbq('track', '{$eventName}', {$eventParam} );";
            }
            ?>
        </script>
        <noscript>
            <img height="1" width="1" src="https://www.facebook.com/tr?id=<?php
            echo $this->pixelID; ?>&ev=PageView&noscript=1"/>
        </noscript>
        <!-- End Facebook Pixel Code -->
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    protected function renderImpl()
    {
        //
    }
}

?>