<?php
//the modules of the test app reached through the root index.php (Module::Route) - their folders also exist
Module::AddRoute("admin");
Module::AddRoute("news");
Module::AddRoute("faq");
Module::AddRoute("pages");
Module::AddRoute("manage", "admin");
