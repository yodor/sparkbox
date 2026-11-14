<?php
Module::Active()->getAuthenticator()?->logout();

header("Location: " . Module::PathURL("login"));
exit;
