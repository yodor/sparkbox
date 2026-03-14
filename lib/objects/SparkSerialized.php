<?php
include_once("objects/SparkObject.php");

class SparkSerialized implements ISparkUnserializable
{
    const string VERSION = "1.0";

    protected ?ISparkSerializable $object = null;

    public function __construct(ISparkSerializable $object)
    {
        $this->object = $object;
    }

    public function __serialize() : array
    {
        $result = array();
        $result["version"] = SparkSerialized::VERSION;
        $refs = array();
        SparkLoader::ClassMap($this->object, $refs);
        foreach ($refs as $className=>$classPath) {
            $refs[$className] = SparkLoader::GetPrefix($className, $classPath);
        }
        $result["references"] = $refs;

        Debug::ErrorLog("Serializing [".get_class($this->object)."] -> ", array_keys($result));

        $result["blob"] = serialize($this->object);

        return $result;
    }

    public function __unserialize(array $data) : void
    {

        if (!isset($data["version"])) throw new Exception("Version not found");
        if (strcmp($data["version"], SparkSerialized::VERSION) !== 0) throw new Exception("Wrong version");

        if (!isset($data["blob"])) throw new Exception("Blob not found");

        if (!isset($data["references"]) || !is_array($data["references"])) throw new Exception("References not found");
        $refs = $data["references"];

        Debug::ErrorLog("[BLOB] -> ", $refs);
        foreach ($refs as $className=>$loaderPrefix) {
            if ($loaderPrefix) {
                SparkLoader::Factory($loaderPrefix)->define($className);
            }
            else {
                Debug::ErrorLog("Skip define for [$className] - empty loaderPrefix ");
            }
        }

        $object = unserialize($data['blob']);
        if (!($object instanceof ISparkSerializable)) throw new Exception("blob not instance of ISparkSerializable");
        $this->object = $object;
    }


    public function unwrap(): ISparkSerializable
    {
        return $this->object;
    }
}