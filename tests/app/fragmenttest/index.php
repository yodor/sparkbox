<?php
//test module without a frame (pageClass empty) - the content of a path is the answer (ContentFragmentResponder)
include_once("templates/Template.php");
include_once("templates/Module.php");

Module::Run("fragmenttest");
