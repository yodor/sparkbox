<?php

interface ICacheable
{
    public function outputBufferStart() : void;
    public function outputBufferFinish() : void;
}
