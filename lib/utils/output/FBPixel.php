<?php
include_once("utils/output/OutputScript.php");

class FBPixel extends OutputScript
{
    protected string $pixelID;
    protected array $trackEvents;

    public function __construct(string $pixelID)
    {
        parent::__construct();
        $this->pixelID = $pixelID;
        $this->trackEvents = array();
    }

    public function addTrackEvent(string $eventName, string $eventParam) : void
    {
        $this->trackEvents[$eventName] = $eventParam;
    }

    public function addTrackObject(FBTrackObject $fbtrack) : void
    {
        $this->addTrackEvent($fbtrack->getEvent(), $fbtrack->getParameters());
    }

    public function script() : string
    {
        $this->buffer->start();
        ?>
        <!-- Facebook Pixel Code Start -->
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
            <img height="1" width="1" src="https://www.facebook.com/tr?id=<?php echo $this->pixelID; ?>&ev=PageView&noscript=1"/>
        </noscript>
        <!-- End Facebook Pixel Code -->
        <?php
        $this->buffer->end();

        return $this->buffer->get();
    }

}

?>