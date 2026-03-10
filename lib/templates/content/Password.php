<?php
include_once("templates/TemplateContent.php");
include_once("forms/InputForm.php");
include_once("forms/renderers/FormRenderer.php");
include_once("store/mailers/ForgotPasswordMailer.php");
include_once("forms/processors/FormProcessor.php");

class Password extends TemplateContent
{

    public function __construct()
    {
        parent::__construct();

    }

    public function initialize(): void
    {
        SparkPage::Instance()->setTitle($this->getContentTitle());
        SparkPage::Instance()->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/LoginForm.css");

        $form = new InputForm();
        $form->addInput(DataInputFactory::Create(InputType::EMAIL, "email", tr("Input your registered email"), 1));

        $frend = new FormRenderer($form);
        $frend->getSubmitButton()->setContents("Send");
        $frend->addClassName("LoginFormRenderer");

        //$frend->setCaption(Spark::Get(Config::SITE_TITLE) . "<BR><small>" . tr("Administration") . "</small>");

        $this->cmp = $frend;
    }

    public function processInput(): void
    {
        parent::processInput();

        $form = $this->form();

        $proc = new FormProcessor();
        $proc->process($form);



        if ($proc->getStatus() === IFormProcessor::STATUS_OK) {

            try {
                $email = $form->getInput("email")->getValue();
                if (!$email) throw new Exception("Empty email");

                $authenticator = Module::Active()->getAuthenticator();
                $random_pass = $authenticator->setRandomPassword($email);

                $loginURL = Module::PathURL("login");
                $fpm = new ForgotPasswordMailer($email, $random_pass, $loginURL);
                $fpm->send();

                Debug::ErrorLog("Password reset successful: " . $email);
                Session::SetAlert(tr("Your new password was sent to your email") . ": " .$email);

                Debug::ErrorLog("Redirecting: " . $loginURL);
                header("Location: " . $loginURL);
                exit;
            }
            catch (Exception $e) {
                Debug::ErrorLog("Password reset failed for email '$email': " . $e->getMessage());
                Session::SetAlert(tr("Password reset failed: ".$e->getMessage()));
            }

        }
        else {
            Session::setAlert($proc->getMessage());
        }
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public function getContentTitle(): string
    {
        return "Forgot Password - ".Spark::Get(Config::SITE_TITLE);
    }

    public function form(): InputForm
    {
        if (!($this->cmp instanceof FormRenderer)) throw new Exception("Incorrect component class - expecting FromRenderer");
        return $this->cmp->getForm();
    }

}