<?php

interface ISerializable {
    public function __serialize() : array;
}