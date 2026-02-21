<?php
include_once("objects/data/DataObject.php");
include_once("utils/IScript.php");

class GTMCommand extends DataObject implements IScript
{
    const string COMMAND_CONFIG = "config";
    const string COMMAND_SET = "set";
    const string COMMAND_EVENT = "event";
    const string COMMAND_CONSENT = "consent";

    protected string $command = "";
    protected string $type = "";
    protected array $parameters = array();

    public function __construct(string $command = GTMCommand::COMMAND_EVENT)
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

    public function script() : Script
    {
        $script = new Script();

$contents = <<<JS
gtag('{$this->command}', '{$this->type}');
JS;
        if (count($this->parameters) > 0) {
            $parameters = json_encode($this->parameters);
$contents = <<<JS
gtag('{$this->command}', '{$this->type}', {$parameters});
JS;
        }

        $script->setContents($contents);
        return $script;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        foreach ($this->parameters as $name => $value) {
            if (isset($data[$name])) {
                $this->parameters[$name] = $data[$name];
            }
        }

    }

}