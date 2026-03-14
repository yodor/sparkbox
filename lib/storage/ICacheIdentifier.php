<?php
interface ICacheIdentifier {
    public function getCacheName() : string;
}