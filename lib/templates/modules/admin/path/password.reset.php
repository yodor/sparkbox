<?php
include_once("templates/modules/admin/content/PasswordReset.php");
//the new password from the link of the reset mail (public - the token is the authorization)
$config = PasswordReset::Configure();
