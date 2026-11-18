<?php
$config = BeanConfig::Factory();
$config->contentClass = PublicationsContent::class;
$config->beanClass = NewsItemsBean::class;
$config->requireAuth = false;
