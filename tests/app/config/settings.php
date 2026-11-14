<?php
//test application - HTTP level tests (storage requests) through the real sparkbox.php bootstrap
Spark::Set(Config::DB_ENABLED, TRUE);
Spark::Set(Config::STORAGE_CACHE_ENABLED, TRUE);
//cache: tests/sparkcache/app (Spark::CachePath) - emptied by run.php

//the test machine may have no DNS - EmailValidator domain check off
Spark::Set(Config::EMAIL_DOMAIN_CHECK, FALSE);
