<?php
include_once("beans/SiteTextsBean.php");
include_once("beans/BeanTranslationsBean.php");
include_once("responders/json/JSONResponder.php");

class TranslateBeanResponder extends JSONResponder
{

    protected int $langID = -1;
    protected int $beanID = -1;

    protected string $field_name = "";
    protected string $bean_class = "";

    protected string $table_name = "";

    /**
     * @var BeanTranslationsBean
     */
    protected BeanTranslationsBean $translations;

    /**
     * @var SelectQuery
     */
    protected SelectQuery $query;

    public function __construct()
    {
        parent::__construct();
        $this->translations = new BeanTranslationsBean();

    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {

        parent::parseParams();

        if (!isset($_GET["langID"])) throw new Exception("langID not passed");
        $this->langID = (int)$_GET["langID"];

        if (!isset($_GET["beanID"])) throw new Exception("beanID not passed");
        $this->beanID = (int)$_GET["beanID"];

        if (!isset($_GET["field_name"])) throw new Exception("field_name not passed");
        $this->field_name = $_GET["field_name"];

        if (!isset($_GET["bean_class"])) throw new Exception("bean_class not passed");
        $this->bean_class = $_GET["bean_class"];

        if ($this->langID < 1) throw new Exception(tr("Please select translation language"));

        if ($this->beanID < 1) throw new Exception("bean_id parameter incorrect");

        $bean = new $this->bean_class();
        $this->table_name = $bean->getTableName();


        $itr = $this->translations->queryFull();
        $itr->stmt->where()->add("table_name", $this->table_name);
        $itr->stmt->where()->add("field_name", $this->field_name);
        $itr->stmt->where()->add("bean_id", $this->beanID);
        $itr->stmt->where()->add("langID", $this->langID);
        $itr->stmt->limit = " 1 ";

        $this->query = $itr;
    }

    protected function _store(JSONResponse $ret) : void
    {

        $translation = trim($_REQUEST["translation"]);
        if (strlen($translation) < 1) throw new Exception(tr("Input a text to be used as translation"));

        $btID = -1;

        $this->query->exec();
        if ($trow = $this->query->next()) {
            $btID = $trow[$this->query->key()];
        }

        $data = array();
        $data["translated"] = $translation;
        $data["langID"] = $this->langID;
        $data["field_name"] = $this->field_name;
        $data["table_name"] = $this->table_name;
        $data["bean_id"] = $this->beanID;

        if ($btID > 0) {
            $this->translations->update($btID, $data);
            $ret->message = tr("Translation Updated");
        }
        else {
            $btID = $this->translations->insert($data);
            $ret->message = tr("Translation Stored");
        }

        $ret->btID = $btID;

    }

    protected function _fetch(JSONResponse $response) : void
    {

        $this->query->stmt->set("translated");

        $response->translation = "";

        $this->query->exec();

        if ($trow = $this->query->next()) {
            $response->translation = $trow["translated"];
        }
        else {
            $response->message = tr("No translation found for selected language.") . "<BR>" . tr("Input the translation into the translation text area and click 'Translate'");
        }

    }

    protected function _clear(JSONResponse $ret) : void
    {

        $ret->translation = "";

        try {
            $delete = new SQLDelete($this->query->stmt);
            $query = new DBQuery();
            $query->exec($delete);

            if ($query->affectedRows()>0) {
                $ret->message = tr("Bean translation removed");
            }
            else {
                $ret->message = tr("No bean translation found for clearing");
            }
        }
        catch (Exception $e) {

            $ret->message = $e->getMessage();
        }


    }

}