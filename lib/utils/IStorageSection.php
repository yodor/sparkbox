<?php

interface IStorageSection
{
    public function setSection($section_name, $section_key);

    public function setOwnerID($ownerID);

    public function setAuthenticator(Authenticator $auth);


}

?>