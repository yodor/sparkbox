<?php
include_once("components/PageScript.php");

class SessionAlertPageScript extends PageScript
{

    protected string $alert = "";
    public function __construct()
    {
        parent::__construct();
        $alert = Session::GetAlert();
        Session::ClearAlert();
        if ($alert) {
            $this->alert = json_encode($alert);
        }
        else {
            $this->setRenderEnabled(false);
        }
    }
    function code(): string
    {

        return <<<JS
onPageLoad(function () {
    showAlert({$this->alert});
});
JS;

    }
}