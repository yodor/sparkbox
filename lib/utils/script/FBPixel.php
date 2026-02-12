<?php
include_once("components/Script.php");

class FBPixel extends Script
{
    protected string $pixelID;

    public function __construct(string $pixelID)
    {
        parent::__construct();
        if (strlen(trim($pixelID)) == 0) throw new Exception("PixelID cannot be empty");
        $this->pixelID = $pixelID;

        $contents = <<<JS
            //Facebook Pixel Code Start
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
            fbq('init', '{$this->pixelID}');
            fbq('track', 'PageView');
JS;
        $this->setContents($contents);

    }
//    protected function processAttributes(): void
//    {
//        parent::processAttributes();
//        //append the no noscript code
//        $noscript = "<noscript><img height=1 width=1 src='https://www.facebook.com/tr?id={$this->pixelID}&ev=PageView&noscript=1'></noscript>";
//        $this->buffer()->append($noscript);
//    }

    public function addTrackEvent(string $eventName, string $eventParam) : void
    {
        $fbq = "fbq('track', '{$eventName}', {$eventParam} );";
        $this->buffer()->append($fbq);
    }

    public function addTrackObject(FBTrackObject $fbtrack) : void
    {
        $this->addTrackEvent($fbtrack->getEvent(), $fbtrack->getParameters());
    }


}