<?php
include_once("objects/SparkObject.php");
include_once("objects/ISparkUnseal.php");
include_once("objects/ISparkSeal.php");
include_once("objects/ISerializable.php");
include_once("objects/IUnserializable.php");

/**
 * DTO like serializer
 */
class SparkSealed implements ISparkUnseal, ISerializable, IUnserializable
{
    const string VERSION = "1.0";

    protected ?ISparkSeal $object = null;

    public function __construct(ISparkSeal $object)
    {
        $this->object = $object;
    }

    public function __serialize() : array
    {
        $result = array();
        $result["version"] = SparkSealed::VERSION;
        $refs = array();
        SparkLoader::ClassMap($this->object, $refs);
        foreach ($refs as $className=>$classPath) {
            $refs[$className] = SparkLoader::GetPrefix($className, $classPath);
        }
        $result["references"] = $refs;

        $result["blob"] = serialize($this->object);

        Debug::ErrorLog("Sealed [".get_class($this->object)."] - blob size: ".strlen($result["blob"]));

        return $result;
    }

    public function __unserialize(array $data) : void
    {

        if (!isset($data["version"])) throw new Exception("Version not found");
        if (strcmp($data["version"], SparkSealed::VERSION) !== 0) throw new Exception("Wrong version");

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
        if (!($object instanceof ISparkSeal)) throw new Exception("blob not instance of ISparkSerializable");
        $this->object = $object;
    }


    public function unwrap(): ISparkSeal
    {
        return $this->object;
    }
}