<?php
include_once("components/InlineScript.php");

class SessionAlertScript extends InlineScript implements IPageComponent
{

    public function __construct()
    {
        parent::__construct();

        $this->enableOnPageLoad();

        $alert = Session::GetAlert();
        Session::ClearAlert();

        if ($alert) {
            $alert = json_encode($alert);
            $this->setCode("showAlert($alert)");
        }
        else {
            $this->setRenderEnabled(false);
        }

    }

}