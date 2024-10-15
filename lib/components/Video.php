<?php
include_once("components/Container.php");

class Source extends Component
{
    public function __construct(string $src = "", string $type = "")
    {
        parent::__construct(false);
        $this->setTagName("SOURCE");
        if ($src) {
            $this->setSrc($src);
        }
        if ($type) {
            $this->setType($type);
        }

    }
    public function setSrc(string $src) : void
    {
        $this->setAttribute("src", $src);
    }
    public function setType(string $type) : void
    {
        $this->setAttribute("type", $type);
    }
}

class Video extends Container
{
    public function __construct()
    {
        parent::__construct(false);
        $this->setTagName("VIDEO");
    }
}

?>