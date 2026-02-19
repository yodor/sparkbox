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
    protected array $parameters = array();
    protected string $parameters_js = "";

    public function __construct(string $command = GTAGObject::COMMAND_EVENT)
    {
        parent::__construct();
        $this->command = $command;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function addParameter(string $name, string|array $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter(string $name, string $default = ""): string|array
    {
        if (!array_key_exists($name, $this->parameters)) return $default;
        return $this->parameters[$name];
    }

    public function removeParameter(string $name): void
    {
        if (array_key_exists($name, $this->parameters)) unset($this->parameters[$name]);
    }

    public function parameterExists(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
    public function setParameters(array $parameters) : void
    {
        $this->parameters = $parameters;
    }


    /**
     * Return the result of template parameters after replacing with data
     * @return string
     */
    public function getPrametersJS() : string
    {
        return $this->parameters_js;
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

        gtag( '{$this->command}', '{$this->type}', {$this->parameters_js} );
JS;

        $script->setContents($contents);
        return $script;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $replace = array("%" . $this->name . "%" => $this->value);
        $this->parameters_js = strtr($this->param_template, $replace);

    }

}