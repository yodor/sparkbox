<?php
include_once("beans/SiteTextsBean.php");
include_once("beans/TranslationPhrasesBean.php");
include_once("handlers/JSONRequestHandler.php");


class TranslatePhraseAjaxHandler extends JSONRequestHandler
{

    private $trID = -1;
    private $textID = -1;
    private $langID = -1;


    public function __construct()
    {
        parent::__construct("translator");

    }

    protected function parseParams()
    {
        parent::parseParams();

        if (!isset($_REQUEST["langID"])) throw new Exception("langID not passed");
        $this->langID = (int)$_GET["langID"];
        if (!isset($_REQUEST["textID"])) throw new Exception("textID not passed");
        $this->textID = (int)$_GET["textID"];
        if (!isset($_REQUEST["trID"])) throw new Exception("trID not passed");
        $this->trID = (int)$_GET["trID"];

    }

    protected function _store(JSONResponse $ret)
    {
        global $g_tr;
        $trrow = array();

        $trrow["translated"] = DBConnections::Get()->escape(trim($_REQUEST["translation"]));
        if (strlen($trrow["translated"]) < 1) throw new Exception(tr("Input a text to be used as translation"));

        if ($this->trID < 1) {
            //insert;

            $trrow["langID"] = $this->langID;
            $trrow["textID"] = $this->textID;
            $trID = $g_tr->insert($trrow);
            if ($trID < 1) throw new Exception($g_tr->getError());
            $ret->trID = $trID;
            $ret->message = tr("Translation Stored");
        }
        else {

            if (!$g_tr->update($this->trID, $trrow)) throw new Exception($g_tr->getError());
            $ret->trID = $this->trID;
            $ret->message = tr("Translation Updated");
        }


    }

    protected function _fetch(JSONResponse $ret)
    {

        global $g_tr, $g_sp;

        $strow = $g_sp->getByID($this->textID);

        $ret->original_text = $strow["value"];

        $qry = $g_tr->queryField("trID", $this->trID, 1);
        $qry->select->fields = " translated ";
        $qry->exec();

        if ($trrow=$qry->next()) {
            $ret->translation = $trrow["translated"];
        }
        else {
            $ret->translation = "";
        }

        $ret->trID = $this->trID;
        $ret->langID = $this->langID;
        $ret->textID = $this->textID;

    }

    protected function _clear(JSONResponse $ret)
    {
        global $g_tr;
        $g_tr->deleteID($this->trID);
        $ret->message = tr("Translation was removed");

    }

}

?>