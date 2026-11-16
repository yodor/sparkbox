<?php
//the module address with ?id= (old links, the menu) or ?page_class= (a group: its newest page)
$config = TemplateConfig::Factory();
$config->contentClass = DynamicPageLink::class;
$config->requireAuth = false;
