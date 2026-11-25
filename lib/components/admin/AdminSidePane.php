<?php
include_once("components/Container.php");
include_once("components/Button.php");
include_once("components/MenuBar.php");
include_once("components/TextComponent.php");
include_once("templates/Module.php");

/**
 * The side pane of the admin frame (SparkAdminPage): the logged administrator (name, logout) and the
 * admin menu
 */
class AdminSidePane extends Container
{
    public function __construct(MenuBar $menu)
    {
        parent::__construct(false);
        $this->setComponentClass("sidePane");

        $adminData = new Container(false);
        $adminData->setComponentClass("user_data");
        $this->items()->append($adminData);

        $authContext = Module::Active()->getAuthContext();
        if (!is_null($authContext)) {
            $username = $authContext->getData()->get(AuthContext::FULLNAME);
            if ($username) {
                $adminName = new TextComponent($username);
                $adminName->setComponentClass("username");
                $adminData->items()->append($adminName);
            }
            $adminData->items()->append(Button::LocationButton(tr("Logout"), Module::PathURL("logout")));
        }

        $this->items()->append($menu);
    }

    /**
     * The stylesheets of the pane - the frame adds them after its own
     * @return array
     */
    public function stylesheets() : array
    {
        return array(Spark::Get(Config::SPARK_LOCAL) . "/css/admin/AdminSidePane.css");
    }
}
