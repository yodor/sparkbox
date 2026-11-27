<?php
//the features of the test app (tests/app/features): 'teston' enabled, 'testoff' registered and off - core/features.test.php
Features::Register(APP_PATH . "/features/teston");
Features::Register(APP_PATH . "/features/testoff");
Features::Enable("teston");
