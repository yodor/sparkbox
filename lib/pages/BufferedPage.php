<?php
include_once("pages/SparkPage.php");

class BufferedPage extends SparkPage
{

    /**
     * @return void
     */
    protected function headFinalize() : void
    {
        $this->head()->setTitle("%title%");
        $this->head()->addMeta("description", "%meta_description%");//do in obCallback
        //no parent call
    }

    public function obCallback(string &$buffer)
    {
        $replace = array(
            "%title%"=> strip_tags($this->preferred_title),
            "%meta_description%" => Spark::MetaDescription($this->description)
        );

        $buffer = strtr($buffer, $replace);
    }

    public function startRender(): void
    {
        $this->buffer->start();
        parent::startRender();
    }

    public function finishRender(): void
    {
        parent::finishRender();
        $this->buffer->end();
        $this->obCallback($this->buffer->data());

        echo $this->buffer->get();
    }
}
?>
