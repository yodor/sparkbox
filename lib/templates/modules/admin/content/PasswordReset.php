<?php
include_once("templates/TemplateContent.php");
include_once("forms/InputForm.php");
include_once("forms/renderers/FormRenderer.php");
include_once("forms/processors/FormProcessor.php");
include_once("forms/processors/IFormProcessor.php");
include_once("beans/AdminUsersBean.php");
include_once("beans/AdminPasswordResetsBean.php");
include_once("auth/PasswordHash.php");
include_once("pages/SparkPage.php");

/**
 * The new password of an administrator from the link of the reset mail (?token= - Password, AdminPasswordResetsBean):
 * the password twice, at least MIN_LENGTH characters; the link works once and for one hour.
 */
class PasswordReset extends TemplateContent
{
    const int MIN_LENGTH = 8;

    protected int $userID = -1;

    protected ?AdminPasswordResetsBean $resets = null;

    public static function Configure() : TemplateConfig
    {
        $config = parent::Configure();
        $config->requireAuth = false;
        return $config;
    }

    /**
     * To the path '$path' of the module with the alert '$message'
     */
    protected function redirect(string $path, string $message) : void
    {
        Session::SetAlert($message);
        header("Location: " . Module::PathURL($path));
        exit;
    }

    public function initialize(): void
    {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Referrer-Policy: no-referrer");

        $this->resets = new AdminPasswordResetsBean();
        $this->userID = $this->resets->userID((string)($_GET["token"] ?? ""));
        if ($this->userID < 1) {
            $this->redirect("password", tr("The password link is invalid or has expired. Please ask for a new one."));
        }

        SparkPage::Instance()->setTitle($this->getContentTitle());
        SparkPage::Instance()->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/LoginForm.css");

        $form = new InputForm();
        $form->setName("PasswordResetForm");
        $form->addInput(DataInputFactory::Create(InputType::PASSWORD, "password", tr("New password"), 1));
        $form->addInput(DataInputFactory::Create(InputType::PASSWORD, "password_repeat", tr("Repeat the new password"), 1));

        $frend = new FormRenderer($form);
        $frend->setAttribute("autocomplete", "off");
        $frend->getSubmitButton()->setContents(tr("Save"));
        $frend->addClassName("LoginFormRenderer");
        $frend->setCaption(tr("Enter the new password - at least %s characters.", PasswordReset::MIN_LENGTH));

        $this->cmp = $frend;
    }

    public function processInput(): void
    {
        parent::processInput();

        $form = $this->form();
        $proc = new FormProcessor();
        $proc->process($form);
        if ($proc->getStatus() === IFormProcessor::STATUS_ERROR) {
            Session::SetAlert($proc->getMessage());
            return;
        }
        if ($proc->getStatus() !== IFormProcessor::STATUS_OK) return;

        $password = (string)$form->getInput("password")->getValue();
        $repeat = (string)$form->getInput("password_repeat")->getValue();
        $form->getInput("password")->setValue("");
        $form->getInput("password_repeat")->setValue("");

        if (mb_strlen($password) < PasswordReset::MIN_LENGTH) {
            Session::SetAlert(tr("Invalid password length"));
            return;
        }
        if (strcmp($password, $repeat) !== 0) {
            Session::SetAlert(tr("The passwords do not match"));
            return;
        }

        $admins = new AdminUsersBean();
        $admins->checkPasswordColumn();
        $hash = new PasswordHash($password);
        if (!$admins->update($this->userID, array("password" => $hash->getValue()))) {
            Debug::ErrorLog("Admin password reset: update of administrator {$this->userID} failed: " . $admins->getDB()->getError());
            Session::SetAlert(tr("The password was not changed. Please try again later."));
            return;
        }
        //used - the link does not work again
        $this->resets->clear($this->userID);

        $this->redirect("login", tr("The password was changed. Log in with the new password."));
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public function getContentTitle(): string
    {
        return tr("Change password") . " - " . Spark::Get(Config::SITE_TITLE);
    }

    public function form(): InputForm
    {
        if (!($this->cmp instanceof FormRenderer)) throw new Exception("Incorrect component class - expecting FormRenderer");
        return $this->cmp->getForm();
    }
}
