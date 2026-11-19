<?php
//test module: no frame - every path answers its content as a fragment
$module = Module::Active();
$module->pageClass = "";
$module->addPageCSS(Spark::Get(Config::LOCAL) . "/fragment.css");
