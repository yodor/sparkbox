<?php

interface IStorageSection
{
    public function setSection(string $section_name, string $section_key);

    public function setOwnerID(int $ownerID);

    public function setAuthenticator(Authenticator $auth);

}

?>