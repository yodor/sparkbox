<?php
include_once("objects/data/DataObject.php");
include_once("utils/IScript.php");

class GTAGObject extends DataObject implements IScript
{
    const string COMMAND_CONFIG = "config";
    const string COMMAND_SET = "set";
    const string COMMAND_EVENT = "event";

    protected string $command = "";
    protected string $type = "";
    protected string $param_template = "";
    protected string $parameters = "";

    public function __construct(string $command = GTAGObject::COMMAND_EVENT)
    {
        parent::__construct();
        $this->command = $command;
    }

    public function setCommand(string $command) : void
    {
        $this->command = $command;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setType(string $type) : void
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

    public function setParamTemplate(string $template) : void
    {
        $this->param_template = $template;
    }

    public function getParamTemplate(): string
    {
        return $this->param_template;
    }

    public function script() : Script
    {
        $script = new Script();
        $contents = <<<JS

        gtag( '{$this->command}', '{$this->type}', {$this->parameters} );
JS;

        $script->setContents($contents);
        return $script;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $replace = array("%" . $this->name . "%" => $this->value);
        $this->parameters = strtr($this->param_template, $replace);

    }

}

?>
