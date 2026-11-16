<?php
//a page: /pages/<dpID>/<slug>/
$config = TemplateConfig::Factory();
$config->contentClass = DynamicPageContent::class;
$config->requireAuth = false;
