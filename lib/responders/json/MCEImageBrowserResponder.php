<?php
include_once("responders/json/ImageUploadResponder.php");
include_once("utils/IStorageSection.php");
include_once("beans/MCEImagesBean.php");

include_once("input/validators/ImageUploadValidator.php");
include_once("forms/InputForm.php");
include_once("iterators/ArrayDataIterator.php");

class ImageDimensionForm extends InputForm
{
    public function __construct()
    {
        parent::__construct();
        $modes = array("fit_px" => "Fit Size (px)", "fit_prc"=> "Fit Size (%)");

        $render_modes = new ArrayDataIterator($modes);

        $field = DataInputFactory::Create(DataInputFactory::SELECT, "render_mode", "Render Mode", 0);
        $field->getRenderer()->setIterator($render_modes);
        $field->getRenderer()->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
        $field->getRenderer()->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
        $field->getRenderer()->na_label = "";

        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "caption", "Caption", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "width", "Width", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "height", "Height", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::CHECKBOX, "enable_popup", "Enable Popup", 0);
        $field->getRenderer()->setInputAttribute("tooltip", "Enable fullscreen view");
        $this->addInput($field);
    }
}

class MCEImageBrowserResponder extends ImageUploadResponder implements IStorageSection
{

    protected $field_name = NULL;

    protected $section_name = "global";
    protected $section_key = "";
    protected $ownerID = -1;
    protected $auth_context = NULL;

    public function __construct()
    {
        parent::__construct("mceImage");


        //do not require thumbnail. just create the imagestorage of the upload data
        $this->setPhotoSize(128, -1);
    }

    public function setSection(string $section_name, string $section_key)
    {
        $this->section_name = $section_name;
        $this->section_key = $section_key;
    }

    public function setOwnerID(int $ownerID)
    {
        $this->ownerID = $ownerID;
    }

    public function setAuthenticator(Authenticator $auth)
    {
        $this->auth_context = $auth;
    }

    protected function parseParams()
    {
        parent::parseParams();

    }

    public function getHTML(StorageObject $object, $field_name) : string
    {
        return "";
    }

    public function createUploadElement(array $imgrow)
    {
        $id = $imgrow["imageID"];

        ob_start();
        echo "<div class='Element' imageID=$id>";
        echo "<div class='remove_button'>X</div>";
        $img_href = StorageItem::Image($id, "MCEImagesBean", $this->width, $this->height);
        echo "<img class='image_contents' src='$img_href'>";
        echo "</div>";
        $html = ob_get_contents();
        ob_end_clean();

        return array("imageID" => $id, "html" => $html,);
    }

    protected function _find(JSONResponse $resp)
    {
        debug("Section: '{$this->section_name}' Section Key: '{$this->section_key}'");

        $bean = new MCEImagesBean();
        $qry = $bean->query();
        $qry->select->where()->add("section", "'{$this->section_name}'")->add("section_key", "'{$this->section_key}'");

        if ($this->ownerID > 0) {
            $qry->select->where()->add("ownerID", $this->ownerID);

        }

        if (isset($_GET["imageID"])) {
            $imageID = (int)$_GET["imageID"];
            $qry->select->where()->add("imageID", $imageID);
        }

        $qry->select->fields()->set("section", "section_key", "imageID", "ownerID", "auth_context");

        $num_images = $qry->exec();

        $resp->objects = array();

        while ($imgrow = $qry->next()) {
            $resp->objects[] = $this->createUploadElement($imgrow);
        }

        $resp->result_count = $num_images;

    }

    protected function assignUploadObjects(JSONResponse $resp, FileStorageObject $upload_object)
    {
        debug("assignUploadObjects");

        $bean = new MCEImagesBean();
        $bean_row = array();
        $bean_row["section"] = $this->section_name;
        if ($this->section_key) {
            $bean_row["section_key"] = $this->section_key;
        }
        if ($this->ownerID > 0) {
            $bean_row["ownerID"] = $this->ownerID;
        }
        if ($this->auth_context) {
            $bean_row["auth_context"] = get_class($this->auth_context);
        }

        $bean_row["photo"] = $upload_object->serializeDB();

        $imageID = $bean->insert($bean_row);
        if ($imageID < 1) throw new Exception("Unable to insert image object: " . $bean->getDB()->getError());

        debug("assignUploadObjects::stored to mce_images with imageID: $imageID");
        $bean_row["imageID"] = $imageID;

        $resp->objects = array();

        $resp->objects[] = $this->createUploadElement($bean_row);

        $resp->result_count = 1;
    }

    protected function _remove(JSONResponse $resp)
    {

        if (!isset($_GET["imageID"])) throw new Exception("imageID not passed");

        $imageID = (int)$_GET["imageID"];

        $bean = new MCEImagesBean();

        $image_row = $bean->getByID($imageID);
        if ($image_row["auth_context"]) {
            $authClass = $image_row["auth_context"];
            $ownerID = (int)$image_row["ownerID"];

            $user_data = array();
            $context = Authenticator::AuthorizeResource($image_row["auth_context"], $user_data, FALSE);

            if ($ownerID > 0 && $context->getID() != $ownerID) throw new Exception(tr("Authorization failed"));

        }

       $bean->delete($imageID);

    }

    protected function _renderDimensionDialog(JSONResponse $resp)
    {
        if (!isset($_GET["imageID"])) throw new Exception("imageID not passed");

        $imageID = (int)$_GET["imageID"];

        $form = new ImageDimensionForm();
        $frend = new FormRenderer($form);

        echo "<div class='ImageDimensionComponent'>";

        echo "<div class='preview'>";
        $img_href = StorageItem::Image($imageID, "MCEImagesBean", 240, -1);
        echo "<img src='$img_href'>";
        echo "</div>";

        echo "<div class='dimension'>";
        $frend->startRender();
        $frend->renderInputs();
        $frend->finishRender();
        echo "</div>";

        echo "</div>";
    }

}

?>
