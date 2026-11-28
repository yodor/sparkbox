<?php
include_once("templates/TemplateContent.php");
include_once("forms/InputForm.php");
include_once("forms/renderers/FormRenderer.php");
include_once("forms/processors/FormProcessor.php");
include_once("forms/processors/IFormProcessor.php");
include_once("mailers/PasswordResetMailer.php");
include_once("beans/AdminUsersBean.php");
include_once("beans/AdminPasswordResetsBean.php");
include_once("pages/SparkPage.php");
include_once("utils/url/URLParameter.php");

/**
 * The admin 'forgot password': the e-mail of an administrator gets a one-time link (AdminPasswordResetsBean,
 * valid 1 hour) to the path password/reset (PasswordReset) - the password changes only there. The same answer for
 * every e-mail (the page does not tell which are registered); no link for a suspended administrator, no new link
 * within AdminPasswordResetsBean::THROTTLE seconds.
 */
class Password extends TemplateContent
{
    /**
     * The config of the password reset of the module authenticator - public
     * @return TemplateConfig
     */
    public static function Configure() : TemplateConfig
    {
        $config = parent::Configure();
        $config->requireAuth = false;
        return $config;
    }

    public function initialize(): void
    {
        SparkPage::Instance()->setTitle($this->getContentTitle());
        SparkPage::Instance()->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/LoginForm.css");

        $form = new InputForm();
        $form->addInput(DataInputFactory::Create(InputType::EMAIL, "email", tr("Input your registered email"), 1));

        $frend = new FormRenderer($form);
        $frend->getSubmitButton()->setContents(tr("Send"));
        $frend->addClassName("LoginFormRenderer");

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

        $email = trim((string)$form->getInput("email")->getValue());
        try {
            $admins = new AdminUsersBean();
            $userID = ($email !== "") ? $admins->email2id($email) : -1;
            if ($userID > 0 && !(int)$admins->getValue($userID, "suspend")) {
                $token = (new AdminPasswordResetsBean())->create($userID);
                if (is_null($token)) {
                    Debug::ErrorLog("Admin password reset: a link was sent to '$email' less than " . AdminPasswordResetsBean::THROTTLE . "s ago - no new link");
                }
                else {
                    $url = Module::PathURL("password/reset");
                    $url->add(new URLParameter("token", $token));
                    if (!(new PasswordResetMailer($email, $url))->send()) {
                        Debug::ErrorLog("Admin password reset: the mail to '$email' was not sent");
                    }
                }
            }
            else {
                Debug::ErrorLog("Admin password reset: no active administrator '$email'");
            }
        }
        catch (Exception $e) {
            Debug::ErrorLog("Admin password reset failed for '$email': " . $e->getMessage());
        }
        //the same answer for every e-mail
        Session::SetAlert(tr("If this email is registered, a link to change the password was sent to it"));
        header("Location: " . Module::PathURL("login"));
        exit;
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public function getContentTitle(): string
    {
        return tr("Forgotten password") . " - " . Spark::Get(Config::SITE_TITLE);
    }

    public function form(): InputForm
    {
        if (!($this->cmp instanceof FormRenderer)) throw new Exception("Incorrect component class - expecting FormRenderer");
        return $this->cmp->getForm();
    }

}
