<?php

interface IStorageSection
{
    public function setSection(string $section_name, string $section_key): void;

    public function setOwnerID(int $ownerID): void;

    public function setAuthenticator(Authenticator $auth): void;

}