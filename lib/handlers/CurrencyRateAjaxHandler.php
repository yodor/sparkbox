<?php

class CurrencyRateAjaxHandler extends JSONRequestHandler
{
    protected $srcID;
    protected $dstID;
    protected $crID;
    protected $rate;

    public function __construct()
    {
        parent::__construct("currency_rates");
        $this->srcID = -1;
        $this->dstID = -1;
        $this->crID = -1;
        $this->rate = 0.0;

    }

    protected function parseParams()
    {
        parent::parseParams();

        if (!isset($_REQUEST["srcID"])) throw new Exception("srcID not passed");
        $this->srcID = (int)$_GET["srcID"];

        if (!isset($_REQUEST["dstID"])) throw new Exception("dstID not passed");
        $this->dstID = (int)$_GET["dstID"];

        if (!isset($_REQUEST["crID"])) throw new Exception("crID not passed");
        $this->crID = (int)$_GET["crID"];

        if (!isset($_REQUEST["rate"])) throw new Exception("rate not passed");
        $this->rate = (float)$_GET["rate"];

    }

    protected function _setrate(JSONResponse $response)
    {
        $bean = new CurrencyRatesBean();
        $data = array("srcID"=>$this->srcID, "dstID"=>$this->dstID, "rate"=>$this->rate);
        if ($this->crID>0) {
            if (!$bean->update($this->crID, $data)) {
                throw new Exception("Error updating rates."."<HR>".$bean->getError());
            }
        }
        else {
            $this->crID = $bean->insert($data);
            if ($this->crID<1) throw new Exception("Error updating rates."."<HR>".$bean->getError());
        }

        $response->crID = $this->crID;
        $response->rate = $this->rate;
        $response->srcID = $this->srcID;
        $response->dstID = $this->dstID;

    }
}

?>