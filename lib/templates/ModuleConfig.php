<?php

class ModuleConfig
{
    public string $name = "";
    public string $location = "";
    public string $pageClass = "";
    public string $authClass = "";
    public ?AuthContext $authContext = null;

    protected static ?ModuleConfig $instance = null;

    public static function Active() : ?ModuleConfig
    {
        return ModuleConfig::$instance;
    }

    public function __construct()
    {
        ModuleConfig::$instance = $this;
    }

}