<?php
interface IUnserializable {
    public function __unserialize(array $data): void;
}