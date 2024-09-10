<?php
include_once("objects/data/DataObject.php");
include_once("utils/IHeadScript.php");

class GTAGObject extends DataObject implements IHeadScript
{
    const COMMAND_CONFIG = "config";
    const COMMAND_SET = "set";
    const COMMAND_EVENT = "event";

    protected $command;
    protected $type;
    protected $param_template;

    protected $parameters;

    public function __construct(string $command = GTAGObject::COMMAND_EVENT)
    {
        parent::__construct();
        $this->command = $command;
    }

    public function setCommand(string $command)
    {
        $this->command = $command;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Return the template parameters after replacing with data
     * @return string
     */
    public function getParameters(): string
    {
        return $this->parameters;
    }

    public function setParamTemplate(string $template)
    {
        $this->param_template = $template;
    }

    public function getParamTemplate(): string
    {
        return $this->param_template;
    }

    public function script() : string
    {
        ob_start();
        ?>
        <script>
            gtag('<?php echo $this->command;?>', '<?php echo $this->type;?>', <?php echo $this->parameters;?>);
        </script>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $replace = array("%" . $this->name . "%" => $this->value);
        $this->parameters = strtr($this->param_template, $replace);

    }

}

?>
