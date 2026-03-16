<?php

class SerializableObject extends SparkObject implements ISparkSeal, ISerializable, jsonSerializable
{
    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    public function wrap(): ISparkUnseal
    {
        return new SparkSealed($this);
    }
}