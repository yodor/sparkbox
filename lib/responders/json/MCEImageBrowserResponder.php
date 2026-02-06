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

        $field = DataInputFactory::Create(InputType::SELECT, "render_mode", "Render Mode", 0);

        $renderer = $field->getRenderer();
        if ($renderer instanceof SelectField) {
            $renderer->setIterator($render_modes);
            $renderer->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
            $renderer->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
            $renderer->setDefaultOption(null);
        }
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "caption", "Caption", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "width", "Width", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "height", "Height", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::CHECKBOX, "enable_popup", "Enable Popup", 0);
        $field->getRenderer()->input()?->setAttribute("tooltip", "Enable fullscreen view");
        $this->addInput($field);
    }
}

class MCEImageBrowserResponder extends ImageUploadResponder implements IStorageSection
{

    protected string $field_name = "";

    protected string $section_name = "global";
    protected string $section_key = "";
    protected int $ownerID = -1;
    protected ?Authenticator $auth_context = null;

    public function __construct()
    {
        parent::__construct();

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

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        parent::parseParams();

    }

    public function getHTML(StorageObject $object, string $field_name) : string
    {
        //use uid as id
        $beanID = $object->UID();
        ob_start();
        echo "<div class='Element' imageID='$beanID'>";
        echo "<div class='remove_button'></div>";
        $img_href = StorageItem::Image(intval($beanID), "MCEImagesBean", $this->width, $this->height);
        echo "<img class='image_contents' src='$img_href'>";
        echo "</div>";
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    //return all images for section
    protected function _find(JSONResponse $resp)
    {
        Debug::ErrorLog("Section: '$this->section_name' Section Key: '$this->section_key'");

        $bean = new MCEImagesBean();
        $qry = $bean->query();
        $qry->select->where()->add("section", "'$this->section_name'");
        $qry->select->where()->add("section_key", "'$this->section_key'");

        if ($this->ownerID > 0) {
            $qry->select->where()->add("ownerID", $this->ownerID);
        }

        if (isset($_GET["imageID"])) {
            $imageID = (int)$_GET["imageID"];
            $qry->select->where()->add("imageID", $imageID);
        }

        $qry->select->fields()->set("section", "section_key", "imageID", "ownerID", "auth_context", "photo");

        $num_images = $qry->exec();

        $resp->objects = array();

        while ($result = $qry->nextResult()) {
            $imageID = $result->get("imageID");
            $object = @unserialize($result->get("photo"));
            if (! ($object instanceof ImageStorageObject)) {
                Debug::ErrorLog("Skipping non ImageStorageObject - ID: $imageID");
                continue;
            }
            try {
                Debug::ErrorLog("Creating response object for ID: $imageID");
                //force ID as uid
                $object->setUID($imageID);
                //getHTML is used inside the viewport
                $resp->objects[] = $this->createResponseObject($object, $this->getHTML($object, $this->field_name));
            }
            catch (Exception $e) {
                Debug::ErrorLog("Error creating response object: ".$e->getMessage());
            }
        }

        $total = count($resp->objects);
        Debug::ErrorLog("Response object_count: $total");
        $resp->object_count = $total;

    }

    protected function storeUploadObject(FileStorageObject $uploadObject) : void
    {
        $bean = new MCEImagesBean();
        $bean_row = array();
        $bean_row["section"] = $this->section_name;
        if ($this->section_key) {
            $bean_row["section_key"] = $this->section_key;
        }
        if ($this->ownerID > 0) {
            $bean_row["ownerID"] = $this->ownerID;
        }
        if ($this->auth_context instanceof Authenticator) {
            $bean_row["auth_context"] = get_class($this->auth_context);
        }

        $bean_row["photo"] = $uploadObject->serializeDB();

        $imageID = $bean->insert($bean_row);
        if ($imageID < 1) throw new Exception("Unable to insert image object: " . $bean->getDB()->getError());

        $uploadObject->setUID($imageID);

        Debug::ErrorLog("Stored object to mce_images using ID: $imageID");

    }

    protected function createResponseObject(FileStorageObject $uploadObject, string $html) : array
    {
        return array(
            //lastinsertid
            "imageID" => $uploadObject->uid(),
            "html" => $html
        );
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
        $frend->getSubmitLine()->setRenderEnabled(false);
        $frend->render();
        echo "</div>";

        echo "</div>";
    }

}

?>
