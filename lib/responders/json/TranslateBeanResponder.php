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
     * @var SQLQuery
     */
    protected SQLQuery $query;

    public function __construct()
    {
        parent::__construct("bean_translator");
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
        $this->field_name = DBConnections::Open()->escape($_GET["field_name"]);

        if (!isset($_GET["bean_class"])) throw new Exception("bean_class not passed");
        $this->bean_class = $_GET["bean_class"];

        if ($this->langID < 1) throw new Exception(tr("Please select translation language"));

        if ($this->beanID < 1) throw new Exception("bean_id parameter incorrect");

        $bean = new $this->bean_class();
        $this->table_name = $bean->getTableName();


        $itr = $this->translations->queryFull();
        $itr->select->where()->add("table_name", "'$this->table_name'");
        $itr->select->where()->add("field_name", "'$this->field_name'");
        $itr->select->where()->add("bean_id", $this->beanID);
        $itr->select->where()->add("langID", $this->langID);
        $itr->select->limit = " 1 ";

        $this->query = $itr;
    }

    protected function _store(JSONResponse $ret)
    {

        $translation = DBConnections::Open()->escape(trim($_REQUEST["translation"]));
        if (strlen($translation) < 1) throw new Exception(tr("Input a text to be used as translation"));

        $btID = -1;

        if ($this->query->exec() && $trow = $this->query->next()) {
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

    protected function _fetch(JSONResponse $response)
    {

        $this->query->select->fields()->set("translated");

        $response->translation = "";

        if ($this->query->exec() && $trow = $this->query->next()) {
            $response->translation = $trow["translated"];
        }
        else {
            $response->message = tr("No translation found for selected language.") . "<BR>" . tr("Input the translation into the translation text area and click 'Translate'");
        }

    }

    protected function _clear(JSONResponse $ret)
    {

        $delete = new SQLDelete($this->query->select);

        $ret->translation = "";

        $db = DBConnections::Open();

        $res = $db->query($delete->getSQL());

        if ($db->numRows($res)) {
            $ret->message = tr("Bean translation removed");
        }
        else {
            $ret->message = tr("No bean translation found for clearing");
        }

    }

}

?>
