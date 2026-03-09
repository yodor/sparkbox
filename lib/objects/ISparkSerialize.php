<?php
interface ISparkSerializable {
    public function wrap() : ISparkUnserializable;
}

interface ISparkUnserializable {
    public function unwrap() : ISparkSerializable;
}