<?php
include_once("components/InlineScript.php");

class GTMCommand extends InlineScript
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

    protected function finalize() : void
    {
        // Default arguments for the gtag function
        $arguments = [
            $this->command,
            $this->type
        ];

        // If parameters exist, append them to the arguments list
        if (count($this->parameters) > 0) {
            $arguments[] = $this->parameters;
        }

        // Map each argument to its JSON-encoded string representation
        $encodedArguments = array_map(function ($argument) {
            return json_encode($argument);
        }, $arguments);

        // Join arguments with a comma and space for the final JS call
        $jsArguments = implode(', ', $encodedArguments);

        $this->setCode("gtag({$jsArguments});");

        parent::finalize();

    }

}