<?php
include_once("input/DataInputFactory.php");
include_once("pages/AdminLoginPage.php");

include_once("input/validators/EmailValidator.php");
include_once("beans/AdminUsersBean.php");

include_once("mailers/ForgotPasswordMailer.php");

include_once("forms/processors/FormProcessor.php");
include_once("forms/renderers/FormRenderer.php");
include_once("components/TextComponent.php");

class ForgotPasswordProcessor extends FormProcessor
{
    protected function processImpl(InputForm $form) : void
    {
        parent::processImpl($form);

        $ub = new AdminUsersBean();

        $email = $form->getInput("email")->getValue();

        if (!$ub->emailExists($email)) {
            throw new Exception(tr("This email is not registered with us"));
        }

        $users = new AdminUsersBean();

        $random_pass = Authenticator::RandomToken(8);
        $loginURL = new URL(Spark::Get(Config::ADMIN_LOCAL)."/login.php");
        $fpm = new ForgotPasswordMailer($email, $random_pass, $loginURL->fullURL());
        $db = DBConnections::Open();
        try {
            $db->transaction();

            $userID = $users->email2id($email);
            $update_row["password"] = md5($random_pass);
            if (!$users->update($userID, $update_row, $db)) throw new Exception("Unable to update records: " . $db->getError());

            $fpm->send();

            $db->commit();

        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }
}

class AdminLoginForgotPassword extends AdminLoginPage
{
    protected InputForm $form;

    public function __construct()
    {
        parent::__construct();

    }

    public function initialize() : void
    {

        $this->setTitle(tr("Forgot Password"));


        $this->form = new InputForm();
        $this->form->addInput(DataInputFactory::Create(InputType::EMAIL, "email", "Input your registered email", 1));

        $frend = new FormRenderer($this->form);
        $frend->getSubmitButton()->setContents("Send");
        $frend->addClassName("LoginFormRenderer");

        $frend->setCaption(Spark::Get(Config::SITE_TITLE) . "<BR><small>" . tr("Administration") . "</small>");



        $this->items()->append($frend);

    }

    public function startRender(): void
    {
        $proc = new ForgotPasswordProcessor();

        $proc->process($this->form);

        if ($proc->getStatus() === IFormProcessor::STATUS_OK) {
            Session::SetAlert(tr("Your new password was sent to your email") . ": " . $this->form->getInput("email")->getValue());
            header("Location: login.php");
            exit;
        }
        else {
            Session::setAlert($proc->getMessage());
        }

        parent::startRender();
    }

}
