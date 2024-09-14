<?php

/**
 * Component rendering interface
 * TODO: make start and finish protected?
 * TODO: use output buffer of HTML body to render inner contents of pages;
 * TODO: Use renderImpl of Container (HTMLPage) to output all components added ie (Head, Body, ..)
 * TODO: Mix/Reorder dynamic output buffer contents and Components inside the items() collection of Container
 * TODO: Component1, OutputBuffer, Component2, ... OutpuBuffer
 * TODO: OutputBuffer create OutputComponent/BufferComponent? using multiple versions of end() to insert to Container collection
 */
interface IRenderer
{

    public function startRender();
    public function render();
    public function finishRender();

}

?>
