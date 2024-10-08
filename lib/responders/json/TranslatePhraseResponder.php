<?php
include_once("beans/SiteTextsBean.php");
include_once("beans/TranslationPhrasesBean.php");
include_once("responders/json/JSONResponder.php");

class TranslatePhraseResponder extends JSONResponder
{

    /**
     * @var TranslationPhrasesBean
     */
    protected $bean;

    private int $trID = -1;
    private int $textID = -1;
    private int $langID = -1;

    public function __construct()
    {
        parent::__construct();
        $this->bean = new TranslationPhrasesBean();
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        parent::parseParams();

        if (!$this->url->contains("langID")) throw new Exception("langID not passed");
        $this->langID = (int)$this->url->get("langID")->value();

        if (!$this->url->contains("textID")) throw new Exception("textID not passed");
        $this->textID = (int)$this->url->get("textID")->value();

        if (!$this->url->contains("trID")) throw new Exception("trID not passed");
        $this->trID = (int)$this->url->get("trID")->value();
    }

    protected function _store(JSONResponse $ret)
    {

        $trrow = array();

        $trrow["translated"] = DBConnections::Open()->escape(trim($_REQUEST["translation"]));
        if (strlen($trrow["translated"]) < 1) throw new Exception(tr("Input translation"));

        try {
            if ($this->trID > 0) {

                $this->bean->update($this->trID, $trrow);

                $ret->trID = $this->trID;
                $ret->message = tr("Translation updated");

            }
            else {
                //insert;
                $trrow["langID"] = $this->langID;
                $trrow["textID"] = $this->textID;

                $trID = $this->bean->insert($trrow);

                $ret->trID = $trID;
                $ret->message = tr("Translation stored");
            }
        }
        catch (Exception $ex) {
            $ret->message = $ex->getMessage();
        }


    }

    protected function _fetch(JSONResponse $response)
    {

        $qry = $this->bean->queryLanguageID($this->langID);

        $qry->select->where()->add("st.textID", $this->textID);

        //debug($qry->select->getSQL());

        $response->phrase = "";
        $response->translation = "";

        if ($qry->exec() && $data = $qry->next()) {
            $this->trID = $data["trID"];
            $response->phrase = $data["phrase"];
            $response->translation = $data["translation"];
        }


        $response->trID = $this->trID;
        $response->langID = $this->langID;
        $response->textID = $this->textID;

    }

    protected function _clear(JSONResponse $response)
    {

        $affectedRows = $this->bean->delete($this->trID);

        $response->trID = $this->trID;

        if ($affectedRows>0) {
            $response->message = tr("Translation removed");
        }
        else {
            $response->message = tr("No translation removed");
        }
    }

}

?>