<?php
class OutputBuffer {

    protected string $data = "";
    protected bool $started = false;

    public function __construct() {

    }

    /**
     * Clear the output buffer doing ob_end_clean if it is started
     * @return void
     */
    public function clear() : void
    {
        $this->data = "";
        if ($this->started) {
            ob_end_clean();
            $this->started = false;
        }
    }

    public function &data() : string
    {
        return $this->data;
    }

    public function start() : void
    {
        if ($this->started) return;
        $this->started = true;
        ob_start();
    }

    public function get() : string
    {
        return $this->data;
    }

    public function set(string $data) : void
    {
        $this->data = $data;
    }

    public function append(string $data) : void
    {
        $this->data .= $data;
    }

    public function end(bool $append=false) : void
    {
        if (!$this->started) return;

        $buffer = ob_get_contents();

        if ($append) {
            $this->append($buffer);
        }
        else {
            $this->set($buffer);
        }

        ob_end_clean();
        $this->started = false;
    }
}
?>
