<?php
include_once("input/renderers/InputField.php");

class TextCaptchaField extends TextField
{
    protected int $var1;
    protected int $var2;
    protected string $oper;
    protected array $available_operands = array("+", "-");
    protected int $result;
    protected string $label;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        if (Session::Contains($this->input->getName().".captcha.result")) {
            $this->var1 = Session::Get($this->input->getName().".captcha.var1");
            $this->var2 = Session::Get($this->input->getName().".captcha.var2");
            $this->oper = Session::Get($this->input->getName().".captcha.oper");
            $this->label = Session::Get($this->input->getName().".captcha.label");
        }
        else {
            $this->initResult();
        }
    }

    protected function initResult()
    {
        $n1 = random_int(1, 10);
        $n2 = random_int(1, 10);
        if ($n1>$n2) {
            $this->var1 = $n1;
            $this->var2 = $n2;
        }
        else {
            $this->var1 = $n2;
            $this->var2 = $n1;
        }

        $this->oper = $this->available_operands[random_int(0,1)];

        if (strcmp($this->oper, "+")==0) {
            $this->result = $this->var1 + $this->var2;
            $this->label = $this->var1." + ".$this->var2." = ? ";
        }
        else if (strcmp($this->oper, "-")==0) {
            $this->result = $this->var1 - $this->var2;
            $this->label = $this->var1." - ".$this->var2." = ? ";
        }
        Session::Set($this->input->getName().".captcha.var1", $this->var1);
        Session::Set($this->input->getName().".captcha.var2", $this->var2);
        Session::Set($this->input->getName().".captcha.oper", $this->oper);
        Session::Set($this->input->getName().".captcha.label", $this->label);
        Session::Set($this->input->getName().".captcha.result", $this->result);
    }

    public function resetResult()
    {
        Session::Clear($this->input->getName().".captcha.result");
    }
    public function getResult() : int
    {
        return Session::Get($this->input->getName().".captcha.result");
    }
    public function getLabel() : string
    {
        return $this->label;
    }
    public function renderImpl()
    {
        echo "<label>".$this->label."</label>";
        parent::renderImpl();

    }
}