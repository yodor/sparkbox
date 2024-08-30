<?php
include_once("utils/CSVTemplateLoader.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("class/beans/BrandsBean.php");
include_once("class/beans/AttributesBean.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/ClassAttributeValuesBean.php");
include_once("class/beans/ClassAttributesBean.php");

include_once("class/beans/ProductFeaturesBean.php");
include_once("class/beans/ProductPhotosBean.php");

include_once("class/beans/GendersBean.php");

class ProductsTemplateLoader extends CSVTemplateLoader
{
    public $testMode = 1;
    protected $db = NULL;

    protected $catID = -1;
    protected $brandID = -1;

    protected $fields = array();

    protected $description = "";
    protected $import_name = "";

    protected $importID = -1;

    public function setImportName($name)
    {
        $this->import_name = $name;
    }

    public function setDescription($descr)
    {
        $this->description = $descr;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function __construct($zipfile, DBDriver $db)
    {
        parent::__construct($zipfile);
        $this->db = $db;

        $this->pc = new ProductCategoriesBean();
        $this->pc->setDB($this->db);

        $this->br = new BrandsBean();
        $this->br->setDB($this->db);

        $this->attrs = new AttributesBean();
        $this->attrs->setDB($this->db);

        $this->prods = new ProductsBean();
        $this->prods->setDB($this->db);

        $this->ca = new ClassAttributesBean();
        $this->ca->setDB($this->db);

        $this->cav = new ClassAttributeValuesBean();
        $this->cav->setDB($this->db);

        $this->pfb = new ProductFeaturesBean();
        $this->pfb->setDB($this->db);

        $this->ppb = new ProductPhotosBean();
        $this->ppb->setDB($this->db);

        $this->prod_imp = new ProductImportBean();
        $this->prod_imp->setDB($this->db);

    }

    public function startLoad()
    {
        $imp_row = array();
        $imp_row["import_name"] = $this->import_name;
        $imp_row["description"] = $this->description;
        $importID = $this->prod_imp->insertRecord($imp_row, $this->db);
        if ($importID < 1) throw new Exception("Unbale to create product import hold: " . $this->db->getError());
        $this->importID = $importID;

    }

    public function finishLoad()
    {

    }

    public function processKeysRow($data)
    {

        $num = count($data);

        if ($num < 1 || !is_array($data)) {
            throw new Exception("Incorrect file format. Unable to read fields row. ");
        }

        $fkeys = array();

        $keys_field = $data[0];
        $keys_header = explode("|", $keys_field);
        if (strcmp($keys_header[0], "keys") != 0) {
            throw new Exception("Incorrect keys field format.");
        }

        foreach ($keys_header as $pos => $val) {
            if ($pos < 1) continue;
            list($keyfield, $keyvalue) = explode(":", $val);
            $fkeys[$keyfield] = (int)$keyvalue;
        }

        if (!isset($fkeys["catID"]) || $fkeys["catID"] < 1) throw new Exception("Incorrect Category key");
        $catID = (int)$fkeys["catID"];

        if (!isset($fkeys["brandID"]) || $fkeys["brandID"] < 1) throw new Exception("Incorrect Brand key");
        $brandID = (int)$fkeys["brandID"];

        try {
            $this->br->getByID($brandID);
        }
        catch (Exception $e) {
            throw new Exception("BrandID: $brandID not found in current brands. ");
        }

        try {
            $this->pc->getByID($catID);
        }
        catch (Exception $e) {
            throw new Exception("CatID: $catID not found in current product categories. ");
        }

        $this->brandID = $brandID;
        $this->catID = $catID;

        unset($data[0]);
        $this->fields = $data;

        $num = count($data);
        if ($num < 1 || !is_array($data)) {
            throw new Exception("Incorrect file format. Unable to read fields row. ");
        }

    }

    public function processDataRow($data)
    {
        unset($data[0]);

        $prod_row = array();
        $optional_attr = array();
        $features = array();
        $images = array();

        foreach ($data as $pos => $value) {
            $field = $this->fields[$pos];
            //optional attributes
            if (strpos($field, "opt:") === 0) {
                list($tmp, $opt_name) = explode(":", $field);
                $opt_name = str_replace("_", " ", $opt_name);

                $optional_attr[$opt_name] = $value;
                unset($data[$pos]);
            }
            //features
            else if (strpos($field, "product:features") === 0) {
                $features = explode(";", $value);
                unset($data[$pos]);
            }
            //images
            else if (strpos($field, "product:images") === 0) {
                $images = explode(";", $value);
                unset($data[$pos]);
            }

            else if (strpos($field, "gnID") === 0) {
                $genderID = GendersBean::gender2id($value);
                $prod_row["gnID"] = (int)$genderID;
            }
            else {
                $prod_row[$field] = $this->db->escape($value);
            }
        }

        $prod_row["brandID"] = (int)$this->brandID;
        $prod_row["catID"] = (int)$this->catID;
        $prod_row["importID"] = (int)$this->importID;
        $prod_row["visible"] = 0;

        //insert product
        $prodID = $this->prods->insert($prod_row, $this->db);
        if ($prodID < 1) {
            ob_start();
            var_dump($prod_row);
            $errcnt = ob_get_contents();
            ob_end_clean();

            throw new Exception("Error inserting the product data: " . $this->db->getError() . "<BR>" . $errcnt);
        }

        $this->processOptionalAttributes($prodID, $optional_attr);

        $this->processProductFeatures($prodID, $features);

        $this->processProductImages($prodID, $images);

        //check translatable fields;
        // 		if (strpos($data[$c],"|")>0){
        // 			list($phrase, $arabic) = explode("|",$data[$c]);
        //
        // 			$prod_row[$fields[$c]]=$db->escapeString(trim($phrase));
        // 			//call translator ensure 2 is arabic langID
        // 			$translationID = $tb->processTranslation(trim($phrase), trim($arabic), 2);
        //
        //
        // 		}

    }

    protected function processOptionalAttributes($prodID, $optional_attr)
    {
        foreach ($optional_attr as $opt_name => $value) {

            $attrs = $this->attrs;
            $ca = $this->ca;
            $cav = $this->cav;

            $qry = $attrs->queryField("name", $opt_name, 1);
            $qry->exec();

            if ($attr_row = $qry->next()) {
                $maID = (int)$attr_row[$attrs->key()];

                $qry1 = $ca->query();
                $qry1->select->where()->add("maID", "'$maID'")->add("catID", "'{$this->catID}'");
                $qry1->select->limit = " 1 ";
                $qry1->exec();

                if ($carow = $qry1->next()) {
                    $caID = $carow[$ca->key()];

                    $cavrow = array();
                    $cavrow["prodID"] = $prodID;
                    $cavrow[$ca->key()] = (int)$caID;
                    $cavrow["value"] = $this->db->escape($value);

                    $cavID = $cav->insert($cavrow, $this->db);

                    if ($cavID < 1) $this->notices[$this->currentRow] .= "!Unable to insert class attribute value '$value'. DBError: " . $this->db->getError();

                }
                else {
                    $this->notices[$this->currentRow] .= "!Optional attribute: '$opt_name' not found for this category({$this->catID}).";
                }
            }
            else {
                $this->notices[$this->currentRow] .= "!Optional attribute: '$opt_name' not found for this category({$this->catID}).";
            }
        }
    }

    protected function processProductFeatures($prodID, $features)
    {

        foreach ($features as $pos => $value) {
            if (strlen($value) < 1) continue;
            $pfbrow = array();
            $pfbrow["prodID"] = $prodID;
            $pfbrow["feature"] = $this->db->escape($value);
            $pfbID = $this->pfb->insert($pfbrow, $this->db);

            if ($pfbID < 1) $this->notices[$this->currentRow] .= "!Unable to insert product feature '$value'. DBError: " . $this->db->getError();

        }
    }

    protected function processProductImages($prodID, $images)
    {
        foreach ($images as $pos => $imgname) {

            if (strlen($imgname) < 1) continue;

            try {

                $ret_img = $this->zip->statName(SPARK_LOCAL . "/images/$imgname");

                if ($ret_img === FALSE) {
                    //image not found but continue
                    $this->notices[$this->currentRow] .= "!Image file '$imgname' not found in the images folder.";
                    continue;
                }

                $filedata = $this->zip->getFromName(SPARK_LOCAL . "/images/$imgname");

                $source = imagecreatefromstring($filedata);
                if ($source === FALSE) {
                    //unrecognized image format
                    $this->notices[$this->currentRow] .= "!Image file '$imgname' not recognized or can not be loaded.";
                    continue;
                }
                @imagedestroy($source);

                $fstorage = new FileStorageObject();
                $fstorage->setFilename($imgname);
                $fstorage->setData($filedata);

                $fstorage->setTimestamp(time());

                $field = new DataInput("image", "Image", 0);
                $field->setValue($fstorage);

                $validator = new ImageUploadValidator();

                $validator->validate($field);

                $istorage = $field->getValue();
                if ($istorage instanceof ImageStorageObject) {

                    $photo_row = array();
                    $istorage->deconstruct($photo_row);
                    $photo_row["prodID"] = $prodID;

                    $ppbID = $this->ppb->insert($photo_row, $this->db);

                    if ($ppbID < 1) throw new Exception("Unable to insert");

                }
                else {
                    throw new Exception("ImageStorage not found in validated input");
                }

            }
            catch (Exception $e) {
                $this->notices[$this->currentRow] .= "!Unable to insert product image '$imgname'. " . $e->getMessage() . " | DBError: " . $this->db->getError();
            }

        }
    }
}
