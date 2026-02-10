<?php
include_once("responders/json/JSONResponder.php");

class CurrencyRateResponder extends JSONResponder
{
    protected int $srcID = -1;
    protected int $dstID = -1;
    protected int $crID = -1;
    protected float $rate = 0.0;

    public function __construct()
    {
        parent::__construct();
        $this->srcID = -1;
        $this->dstID = -1;
        $this->crID = -1;
        $this->rate = 0.0;

    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        parent::parseParams();

        if (!isset($_REQUEST["srcID"])) throw new Exception("srcID not passed");
        $this->srcID = (int)$_GET["srcID"];

        if (!isset($_REQUEST["dstID"])) throw new Exception("dstID not passed");
        $this->dstID = (int)$_GET["dstID"];

        if (!isset($_REQUEST["rate"])) throw new Exception("rate not passed");
        $this->rate = round((float)$_GET["rate"],2);

    }

    protected function _setrate(JSONResponse $response) : void
    {
        $bean = new CurrencyRatesBean();

        $db = DBConnections::Open();
        try {
            $db->transaction();
            $sel = "DELETE FROM currency_rates WHERE (srcID='$this->srcID' AND dstID='$this->dstID') OR (dstID='$this->srcID' AND srcID='$this->dstID')";
            $db->query($sel);

            //forward rate
            $data_forward = array("srcID"=>$this->srcID, "dstID"=>$this->dstID, "rate"=>$this->rate);

            if (!$bean->insert($data_forward, $db)) throw new Exception("Error updating forward quote:"."<HR>".$db->getError());

            $data_backward = array("dstID"=>$this->srcID, "srcID"=>$this->dstID, "rate"=>round(1.0/$this->rate,2));

            if (!$bean->insert($data_backward, $db)) throw new Exception("Error updating backward quote:"."<HR>".$db->getError());

            $db->commit();

            $response->forward_rate = $data_forward["rate"];
            $response->backward_rate = $data_backward["rate"];
            $response->srcID = $this->srcID;
            $response->dstID = $this->dstID;
        }
        catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }

    }
}

?>
