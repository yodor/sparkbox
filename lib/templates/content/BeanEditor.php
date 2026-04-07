<?php
include_once("templates/TemplateContent.php");
include_once("components/BeanFormEditor.php");
/**
 * If request condition is BeanKeyCondition will use it to set where filter on the view bean and add field to the transactor
 * Class BeanEditorPage
 */
class BeanEditor extends TemplateContent
{

    /**
     * @var InputForm|null
     */
    protected ?InputForm $form = null;

    public function __construct()
    {
        parent::__construct();
    }


    public function setForm(InputForm $form): void
    {
        $this->form = $form;
    }

    public function getForm(): ?InputForm
    {
        return $this->form;
    }

    public function processInput(): void
    {
        $this->editor()->processInput();
    }

    public function isProcessed(): bool
    {
        return $this->editor()->isProcessed();
    }

    public function initialize(): void
    {
        $this->cmp = new BeanFormEditor($this->bean, $this->form);

        $transactor = $this->editor()->getTransactor();

        //match reference bean from condition if set
        if (Template::Condition() && $this->config->useCondition) {

            //copy keyID
            $keyID = Template::Condition()->getID();
            $keyName = Template::Condition()->getBean()->key();

            Debug::ErrorLog("useCondition is enabled - assigning transactor insert value $keyName=$keyID");
            $transactor->assignInsertValue($keyName, $keyID);

        }

        if ($this->bean instanceof OrderedDataBean) {
            $maxPosition = $this->bean->getMaxPosition() + 1;
            Debug::ErrorLog("OrderedDataBean - assigning transactor insert value position=$maxPosition");
            $transactor->assignInsertValue("position", $maxPosition);
        }
    }

    public function editor(): BeanFormEditor
    {
        if ($this->cmp instanceof BeanFormEditor) return $this->cmp;
        throw new Exception("Incorrect component class - expected BeanFormEditor");
    }

    public function setup(TemplateConfig $config): void
    {
        parent::setup($config);
        if ($config->formClass) {
            $this->setForm(SparkLoader::Factory("forms")->instance($config->formClass, InputForm::class));
        }
    }

    protected function getContentTitle(): string
    {
        return "Editor";
    }
}