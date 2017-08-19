-- phpMyAdmin SQL Dump
-- version 4.1.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 30 май 2015 в 18:29
-- Версия на сървъра: 5.5.25a
-- PHP Version: 5.3.27

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `demo`
--

-- --------------------------------------------------------

--
-- Структура на таблица `admin_access`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `admin_access` (
  `aclID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userID` int(11) unsigned NOT NULL,
  `role` varchar(100) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`aclID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура на таблица `admin_users`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `admin_users` (
  `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET ascii NOT NULL,
  `password` varchar(32) CHARACTER SET ascii NOT NULL,
  `context` text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `level` int(11) NOT NULL DEFAULT '0',
  `access_level` enum('Limited Access','Full Access') NOT NULL DEFAULT 'Limited Access',
  `suspend` tinyint(1) DEFAULT '0',
  `last_active` datetime DEFAULT NULL,
  `counter` bigint(20) unsigned DEFAULT '0',
  `fullname` text NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `adminID` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`email`),
  KEY `parentID` (`adminID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура на таблица `attributes`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `attributes` (
  `maID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`maID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Структура на таблица `brands`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `brands` (
  `brandID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `photo` longblob,
  PRIMARY KEY (`brandID`),
  UNIQUE KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Структура на таблица `class_attributes`
--
-- Създаване: 24 апр 2015 в 17:59
--

CREATE TABLE IF NOT EXISTS `class_attributes` (
  `caID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pclsID` int(11) unsigned NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `attribute_name` varchar(255) NOT NULL,
  `default_value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`caID`),
  UNIQUE KEY `class_attributes` (`class_name`,`attribute_name`),
  KEY `attribute_name` (`attribute_name`),
  KEY `class_name` (`class_name`),
  KEY `pclsID` (`pclsID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Структура на таблица `class_attribute_values`
--
-- Създаване: 23 апр 2015 в 21:37
--

CREATE TABLE IF NOT EXISTS `class_attribute_values` (
  `cavID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prodID` int(11) unsigned NOT NULL,
  `caID` int(11) unsigned NOT NULL,
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cavID`),
  UNIQUE KEY `prodID_2` (`prodID`,`caID`),
  KEY `prodID` (`prodID`),
  KEY `caID` (`caID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `color_chips`
--
CREATE TABLE IF NOT EXISTS `color_chips` (
`prodID` int(11) unsigned
,`pi_ids` text
,`colors` text
,`color_codes` text
,`color_photos` text
,`have_chips` text
,`color_ids` text
,`product_photos` varchar(256)
);
-- --------------------------------------------------------

--
-- Структура на таблица `config`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `config` (
  `cfgID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `config_val` longblob,
  `section` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cfgID`),
  KEY `config_key` (`config_key`),
  KEY `section` (`section`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

--
-- Структура на таблица `dynamic_pages`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `dynamic_pages` (
  `dpID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_title` varchar(255) NOT NULL DEFAULT '',
  `item_date` date DEFAULT '0000-00-00',
  `visible` tinyint(1) DEFAULT '0',
  `subtitle` text,
  `content` text NOT NULL,
  `position` int(11) unsigned NOT NULL,
  `photo` longblob,
  PRIMARY KEY (`dpID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

-- --------------------------------------------------------

--
-- Структура на таблица `faq_items`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `faq_items` (
  `fID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `section` enum('General','Orders','Returns','Credit Limit','Territories','Shipping','Contact') NOT NULL DEFAULT 'General',
  `question` varchar(255) NOT NULL,
  `answer` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`fID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура на таблица `gallery_photos`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `gallery_photos` (
  `gpID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `mime` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `size` int(11) NOT NULL,
  `photo` longblob NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` int(11) DEFAULT NULL,
  `caption` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`gpID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=68 ;

-- --------------------------------------------------------

--
-- Структура на таблица `genders`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `genders` (
  `gnID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gender_title` varchar(32) NOT NULL,
  PRIMARY KEY (`gnID`),
  UNIQUE KEY `gender_title` (`gender_title`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory`
--
CREATE TABLE IF NOT EXISTS `inventory` (
`piID` int(11) unsigned
,`prodID` int(11) unsigned
,`stock_amount` int(11)
,`insert_date` timestamp
,`price` decimal(10,2)
,`old_price` decimal(10,2)
,`buy_price` decimal(10,2)
,`weight` decimal(10,3) unsigned
,`size_value` varchar(255)
,`pclrID` int(11) unsigned
,`color` varchar(255)
,`color_code` varchar(10)
,`have_chip` int(1)
,`pclrpID` bigint(11) unsigned
,`ppID` bigint(11) unsigned
,`discount_amount` bigint(11)
,`sell_price` decimal(26,6)
);
-- --------------------------------------------------------

--
-- Структура на таблица `inventory_attribute_values`
--
-- Създаване: 25 апр 2015 в 09:42
--

CREATE TABLE IF NOT EXISTS `inventory_attribute_values` (
  `cavID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `piID` int(11) unsigned NOT NULL,
  `caID` int(11) unsigned NOT NULL,
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cavID`),
  UNIQUE KEY `inventory_attributes` (`piID`,`caID`),
  KEY `caID` (`caID`),
  KEY `piID` (`piID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory_colors`
--
CREATE TABLE IF NOT EXISTS `inventory_colors` (
`color` varchar(255)
,`pclrID` int(11) unsigned
,`piID` int(11) unsigned
,`prodID` int(11) unsigned
,`have_chip` int(1)
,`pclrpID` bigint(11) unsigned
,`ppID` bigint(11) unsigned
,`color_code` varchar(10)
);
-- --------------------------------------------------------

--
-- Структура на таблица `languages`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `languages` (
  `langID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `lang_code` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`langID`),
  UNIQUE KEY `language` (`language`),
  UNIQUE KEY `lang_code` (`lang_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Структура на таблица `mce_images`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `mce_images` (
  `imageID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `section` varchar(255) NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `ownerID` int(11) DEFAULT NULL,
  `photo` longblob NOT NULL,
  `auth_context` varchar(255) DEFAULT NULL,
  `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`imageID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=27 ;

-- --------------------------------------------------------

--
-- Структура на таблица `menu_items`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `menu_items` (
  `menuID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_title` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `parentID` int(10) unsigned NOT NULL DEFAULT '0',
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  PRIMARY KEY (`menuID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=39 ;

-- --------------------------------------------------------

--
-- Структура на таблица `news_items`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `news_items` (
  `newsID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_title` varchar(255) NOT NULL,
  `item_date` date NOT NULL,
  `content` text NOT NULL,
  `photo` longblob NOT NULL,
  PRIMARY KEY (`newsID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Структура на таблица `page_photos`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `page_photos` (
  `ppID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `mime` varchar(255) NOT NULL DEFAULT '',
  `size` int(11) NOT NULL DEFAULT '0',
  `photo` longblob NOT NULL,
  `width` int(11) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0',
  `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `caption` text,
  `dpID` int(11) unsigned NOT NULL DEFAULT '0',
  `position` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ppID`),
  KEY `dpID` (`dpID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32 ;

-- --------------------------------------------------------

--
-- Структура на таблица `products`
--
-- Създаване: 24 апр 2015 в 16:09
--

CREATE TABLE IF NOT EXISTS `products` (
  `prodID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `class_name` varchar(255) DEFAULT NULL,
  `brand_name` varchar(255) NOT NULL,
  `product_code` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `product_summary` text NOT NULL,
  `product_description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `keywords` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `gender` varchar(32) DEFAULT NULL,
  `buy_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `weight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `catID` int(11) unsigned NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `view_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `order_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `visible` tinyint(1) DEFAULT '0',
  `promotion` tinyint(1) DEFAULT '0',
  `importID` int(11) unsigned DEFAULT NULL,
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `insert_date` datetime NOT NULL,
  PRIMARY KEY (`prodID`),
  KEY `catID` (`catID`),
  KEY `importID` (`importID`),
  KEY `gender` (`gender`),
  KEY `brand_name` (`brand_name`),
  KEY `update_date` (`update_date`),
  KEY `insert_date` (`insert_date`),
  KEY `promotion` (`promotion`),
  KEY `visible` (`visible`),
  KEY `class_name` (`class_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Структура на таблица `product_categories`
--
-- Създаване: 12 авг 2014 в 19:18
--

CREATE TABLE IF NOT EXISTS `product_categories` (
  `catID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `parentID` int(10) unsigned NOT NULL DEFAULT '0',
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `photo` longblob,
  PRIMARY KEY (`catID`),
  UNIQUE KEY `parentID_2` (`parentID`,`category_name`),
  KEY `category_name` (`category_name`),
  KEY `parentID` (`parentID`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4242 ;

-- --------------------------------------------------------

--
-- Структура на таблица `product_classes`
--
-- Създаване: 24 апр 2015 в 14:09
--

CREATE TABLE IF NOT EXISTS `product_classes` (
  `pclsID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `class_name` varchar(255) NOT NULL,
  PRIMARY KEY (`pclsID`),
  UNIQUE KEY `class_name` (`class_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Структура на таблица `product_colors`
--
-- Създаване:  3 май 2015 в 09:12
--

CREATE TABLE IF NOT EXISTS `product_colors` (
  `pclrID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `color` varchar(255) NOT NULL,
  `color_photo` longblob COMMENT 'color_chip',
  `prodID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`pclrID`),
  UNIQUE KEY `color_gallery` (`color`,`prodID`),
  KEY `prodID` (`prodID`),
  KEY `color` (`color`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Структура на таблица `product_color_photos`
--
-- Създаване: 21 апр 2015 в 00:17
--

CREATE TABLE IF NOT EXISTS `product_color_photos` (
  `pclrpID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `photo` longblob NOT NULL,
  `pclrID` int(11) unsigned NOT NULL,
  `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `position` int(11) NOT NULL DEFAULT '0',
  `caption` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`pclrpID`),
  KEY `pclrID` (`pclrID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

--
-- Структура на таблица `product_features`
--
-- Създаване: 29 юли 2014 в 11:04
--

CREATE TABLE IF NOT EXISTS `product_features` (
  `pfID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feature` varchar(255) NOT NULL,
  `prodID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`pfID`),
  KEY `prodID` (`prodID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Структура на таблица `product_inventory`
--
-- Създаване: 27 май 2015 в 14:31
--

CREATE TABLE IF NOT EXISTS `product_inventory` (
  `piID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prodID` int(11) unsigned NOT NULL,
  `pclrID` int(11) unsigned DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `size_value` varchar(255) DEFAULT NULL,
  `stock_amount` int(11) NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `buy_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `old_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `weight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`piID`),
  UNIQUE KEY `prodID_2` (`prodID`,`pclrID`,`size_value`),
  KEY `prodID` (`prodID`),
  KEY `pclrID` (`pclrID`),
  KEY `size_value` (`size_value`),
  KEY `color` (`color`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

-- --------------------------------------------------------

--
-- Структура на таблица `product_photos`
--
-- Създаване: 25 апр 2015 в 22:40
--

CREATE TABLE IF NOT EXISTS `product_photos` (
  `ppID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `photo` longblob NOT NULL,
  `prodID` int(11) unsigned NOT NULL,
  `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `caption` varchar(255) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ppID`),
  KEY `prodID` (`prodID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `sellable_products`
--
CREATE TABLE IF NOT EXISTS `sellable_products` (
`price_min` decimal(26,6)
,`price_max` decimal(26,6)
,`color_gallery` varchar(256)
,`pids` text
,`size_values` text
,`sell_prices` text
,`stock_amounts` text
,`old_prices` text
,`color_pids` text
,`colors` text
,`color_photos` text
,`have_chips` text
,`color_ids` text
,`product_photos` varchar(256)
,`color_codes` text
,`piID` int(11) unsigned
,`stock_amount` int(11)
,`i_price` decimal(10,2)
,`i_old_price` decimal(10,2)
,`i_buy_price` decimal(10,2)
,`i_weight` decimal(10,3) unsigned
,`size_value` varchar(255)
,`pclrID` int(11) unsigned
,`color` varchar(255)
,`color_code` varchar(10)
,`have_chip` int(1)
,`pclrpID` bigint(11) unsigned
,`ppID` bigint(11) unsigned
,`discount_amount` bigint(11)
,`sell_price` decimal(26,6)
,`inventory_date` timestamp
,`prodID` int(11) unsigned
,`class_name` varchar(255)
,`brand_name` varchar(255)
,`product_code` varchar(50)
,`product_name` varchar(255)
,`product_summary` text
,`product_description` text
,`keywords` text
,`gender` varchar(32)
,`buy_price` decimal(10,2) unsigned
,`price` decimal(10,2) unsigned
,`weight` decimal(10,3) unsigned
,`catID` int(11) unsigned
,`old_price` decimal(10,2)
,`view_counter` int(11) unsigned
,`order_counter` int(11) unsigned
,`visible` tinyint(1)
,`promotion` tinyint(1)
,`importID` int(11) unsigned
,`update_date` timestamp
,`insert_date` datetime
);
-- --------------------------------------------------------

--
-- Структура на таблица `site_texts`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `site_texts` (
  `textID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `hash_value` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`textID`),
  UNIQUE KEY `hash_value` (`hash_value`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1097 ;

-- --------------------------------------------------------

--
-- Структура на таблица `site_text_usage`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `site_text_usage` (
  `stuID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `textID` int(10) unsigned NOT NULL,
  `usedby` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `capture_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stuID`),
  UNIQUE KEY `textID_2` (`textID`,`usedby`),
  KEY `textID` (`textID`),
  KEY `usedby` (`usedby`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=354267 ;

-- --------------------------------------------------------

--
-- Структура на таблица `store_colors`
--
-- Създаване: 23 апр 2015 в 22:31
--

CREATE TABLE IF NOT EXISTS `store_colors` (
  `sclrID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `color` varchar(255) NOT NULL,
  `color_code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`sclrID`),
  UNIQUE KEY `color` (`color`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Структура на таблица `store_promos`
--
-- Създаване: 19 май 2015 в 10:39
--

CREATE TABLE IF NOT EXISTS `store_promos` (
  `spID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `target` enum('Product','Category') NOT NULL,
  `targetID` int(11) unsigned NOT NULL,
  `discount_percent` int(11) NOT NULL,
  PRIMARY KEY (`spID`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  KEY `targetID` (`targetID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура на таблица `store_sizes`
--
-- Създаване: 27 апр 2015 в 14:42
--

CREATE TABLE IF NOT EXISTS `store_sizes` (
  `pszID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `size_value` varchar(255) NOT NULL,
  PRIMARY KEY (`pszID`),
  UNIQUE KEY `size_value` (`size_value`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Структура на таблица `translation_beans`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `translation_beans` (
  `btID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `field_name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `bean_id` int(11) unsigned NOT NULL,
  `translated` text COLLATE utf8_unicode_ci NOT NULL,
  `langID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`btID`),
  UNIQUE KEY `table_name` (`table_name`,`field_name`,`langID`,`bean_id`),
  KEY `langID` (`langID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Структура на таблица `translation_phrases`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `translation_phrases` (
  `trID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `langID` int(11) unsigned NOT NULL,
  `textID` int(11) unsigned NOT NULL,
  `translated` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`trID`),
  UNIQUE KEY `langID_2` (`langID`,`textID`),
  KEY `langID` (`langID`),
  KEY `textID` (`textID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=73 ;

-- --------------------------------------------------------

--
-- Структура на таблица `users`
--
-- Създаване: 24 яну 2014 в 12:15
--

CREATE TABLE IF NOT EXISTS `users` (
  `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL DEFAULT '',
  `last_active` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `counter` int(11) unsigned NOT NULL DEFAULT '0',
  `date_signup` datetime NOT NULL,
  `suspend` tinyint(1) NOT NULL DEFAULT '0',
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirm_code` varchar(32) DEFAULT '',
  PRIMARY KEY (`userID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure for view `color_chips`
--
DROP TABLE IF EXISTS `color_chips`;

CREATE VIEW `color_chips` AS (select `i`.`prodID` AS `prodID`,group_concat(`ic`.`piID` separator '|') AS `pi_ids`,group_concat(`ic`.`color` separator '|') AS `colors`,group_concat(`ic`.`color_code` separator '|') AS `color_codes`,group_concat((select group_concat(coalesce(`ic1`.`pclrpID`,0) separator ',') from `inventory_colors` `ic1` where (`ic1`.`piID` = `i`.`piID`)) separator '|') AS `color_photos`,group_concat(`ic`.`have_chip` separator '|') AS `have_chips`,group_concat(`ic`.`pclrID` separator '|') AS `color_ids`,(select group_concat(`pp`.`ppID` separator '|') from `product_photos` `pp` where (`pp`.`prodID` = `i`.`prodID`) order by `pp`.`position`) AS `product_photos` from (`inventory` `i` left join `inventory_colors` `ic` on((`ic`.`piID` = `i`.`piID`))) group by `i`.`prodID`);

-- --------------------------------------------------------

--
-- Structure for view `inventory`
--
DROP TABLE IF EXISTS `inventory`;

CREATE VIEW `inventory` AS (select `pi`.`piID` AS `piID`,`pi`.`prodID` AS `prodID`,`pi`.`stock_amount` AS `stock_amount`,`pi`.`insert_date` AS `insert_date`,`pi`.`price` AS `price`,`pi`.`old_price` AS `old_price`,`pi`.`buy_price` AS `buy_price`,`pi`.`weight` AS `weight`,`pi`.`size_value` AS `size_value`,`pi`.`pclrID` AS `pclrID`,`pi`.`color` AS `color`,`sc`.`color_code` AS `color_code`,coalesce((length(`pclr`.`color_photo`) > 0),0) AS `have_chip`,(select `pcp`.`pclrpID` from `product_color_photos` `pcp` where (`pcp`.`pclrID` = `pi`.`pclrID`) order by `pcp`.`position` limit 1) AS `pclrpID`,(select `pp`.`ppID` from `product_photos` `pp` where (`pp`.`prodID` = `pi`.`prodID`) order by `pp`.`position` limit 1) AS `ppID`,coalesce(`sp`.`discount_percent`,0) AS `discount_amount`,(`pi`.`price` - ((`pi`.`price` * coalesce(`sp`.`discount_percent`,0)) / 100.0)) AS `sell_price` from ((((`product_inventory` `pi` left join `product_colors` `pclr` on((`pclr`.`pclrID` = `pi`.`pclrID`))) left join `store_colors` `sc` on((`sc`.`color` = `pclr`.`color`))) left join `products` `p` on((`p`.`prodID` = `pi`.`prodID`))) left join `store_promos` `sp` on(((`sp`.`targetID` = `p`.`catID`) and (`sp`.`target` = 'Category') and (`sp`.`start_date` < now()) and (`sp`.`end_date` > now())))));

-- --------------------------------------------------------

--
-- Structure for view `inventory_colors`
--
DROP TABLE IF EXISTS `inventory_colors`;

CREATE VIEW `inventory_colors` AS (select `si`.`color` AS `color`,`si`.`pclrID` AS `pclrID`,`si`.`piID` AS `piID`,`si`.`prodID` AS `prodID`,`si`.`have_chip` AS `have_chip`,`si`.`pclrpID` AS `pclrpID`,`si`.`ppID` AS `ppID`,`sc`.`color_code` AS `color_code` from (`inventory` `si` join `store_colors` `sc` on((`sc`.`color` = `si`.`color`))) where (`si`.`pclrID` is not null) group by `si`.`pclrID`);

-- --------------------------------------------------------

--
-- Structure for view `sellable_products`
--
DROP TABLE IF EXISTS `sellable_products`;

CREATE VIEW `sellable_products` AS (select min(`si`.`sell_price`) AS `price_min`,max(`si`.`sell_price`) AS `price_max`,(select group_concat(`pcp`.`pclrpID` order by `pcp`.`position` ASC separator '|') from `product_color_photos` `pcp` where (`pcp`.`pclrID` = `si`.`pclrID`)) AS `color_gallery`,group_concat(`si`.`piID` separator '|') AS `pids`,group_concat(`si`.`size_value` separator '|') AS `size_values`,group_concat(`si`.`sell_price` separator '|') AS `sell_prices`,group_concat(`si`.`stock_amount` separator '|') AS `stock_amounts`,group_concat(`si`.`old_price` separator '|') AS `old_prices`,`cc`.`pi_ids` AS `color_pids`,`cc`.`colors` AS `colors`,`cc`.`color_photos` AS `color_photos`,`cc`.`have_chips` AS `have_chips`,`cc`.`color_ids` AS `color_ids`,`cc`.`product_photos` AS `product_photos`,`cc`.`color_codes` AS `color_codes`,`si`.`piID` AS `piID`,`si`.`stock_amount` AS `stock_amount`,`si`.`price` AS `i_price`,`si`.`old_price` AS `i_old_price`,`si`.`buy_price` AS `i_buy_price`,`si`.`weight` AS `i_weight`,`si`.`size_value` AS `size_value`,`si`.`pclrID` AS `pclrID`,`si`.`color` AS `color`,`si`.`color_code` AS `color_code`,`si`.`have_chip` AS `have_chip`,`si`.`pclrpID` AS `pclrpID`,`si`.`ppID` AS `ppID`,`si`.`discount_amount` AS `discount_amount`,`si`.`sell_price` AS `sell_price`,`si`.`insert_date` AS `inventory_date`,`p`.`prodID` AS `prodID`,`p`.`class_name` AS `class_name`,`p`.`brand_name` AS `brand_name`,`p`.`product_code` AS `product_code`,`p`.`product_name` AS `product_name`,`p`.`product_summary` AS `product_summary`,`p`.`product_description` AS `product_description`,`p`.`keywords` AS `keywords`,`p`.`gender` AS `gender`,`p`.`buy_price` AS `buy_price`,`p`.`price` AS `price`,`p`.`weight` AS `weight`,`p`.`catID` AS `catID`,`p`.`old_price` AS `old_price`,`p`.`view_counter` AS `view_counter`,`p`.`order_counter` AS `order_counter`,`p`.`visible` AS `visible`,`p`.`promotion` AS `promotion`,`p`.`importID` AS `importID`,`p`.`update_date` AS `update_date`,`p`.`insert_date` AS `insert_date` from ((`inventory` `si` join `products` `p` on((`p`.`prodID` = `si`.`prodID`))) left join `color_chips` `cc` on((`cc`.`prodID` = `si`.`prodID`))) where (`p`.`visible` = 1) group by `si`.`prodID`,`si`.`pclrID`);

--
-- Ограничения за дъмпнати таблици
--

--
-- Ограничения за таблица `admin_access`
--
ALTER TABLE `admin_access`
  ADD CONSTRAINT `admin_access_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `admin_users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admin_access_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `admin_users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `admin_users` (`userID`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Ограничения за таблица `class_attributes`
--
ALTER TABLE `class_attributes`
  ADD CONSTRAINT `class_attributes_ibfk_9` FOREIGN KEY (`pclsID`) REFERENCES `product_classes` (`pclsID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `class_attributes_ibfk_7` FOREIGN KEY (`attribute_name`) REFERENCES `attributes` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `class_attributes_ibfk_8` FOREIGN KEY (`class_name`) REFERENCES `product_classes` (`class_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `class_attribute_values`
--
ALTER TABLE `class_attribute_values`
  ADD CONSTRAINT `class_attribute_values_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `class_attribute_values_ibfk_2` FOREIGN KEY (`caID`) REFERENCES `class_attributes` (`caID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `inventory_attribute_values`
--
ALTER TABLE `inventory_attribute_values`
  ADD CONSTRAINT `inventory_attribute_values_ibfk_2` FOREIGN KEY (`caID`) REFERENCES `class_attributes` (`caID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_attribute_values_ibfk_1` FOREIGN KEY (`piID`) REFERENCES `product_inventory` (`piID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `page_photos`
--
ALTER TABLE `page_photos`
  ADD CONSTRAINT `page_photos_ibfk_1` FOREIGN KEY (`dpID`) REFERENCES `dynamic_pages` (`dpID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_6` FOREIGN KEY (`class_name`) REFERENCES `product_classes` (`class_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`gender`) REFERENCES `genders` (`gender_title`),
  ADD CONSTRAINT `products_ibfk_4` FOREIGN KEY (`brand_name`) REFERENCES `brands` (`brand_name`),
  ADD CONSTRAINT `products_ibfk_5` FOREIGN KEY (`catID`) REFERENCES `product_categories` (`catID`);

--
-- Ограничения за таблица `product_colors`
--
ALTER TABLE `product_colors`
  ADD CONSTRAINT `product_colors_ibfk_2` FOREIGN KEY (`color`) REFERENCES `store_colors` (`color`) ON UPDATE CASCADE,
  ADD CONSTRAINT `product_colors_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `product_color_photos`
--
ALTER TABLE `product_color_photos`
  ADD CONSTRAINT `product_color_photos_ibfk_1` FOREIGN KEY (`pclrID`) REFERENCES `product_colors` (`pclrID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `product_features`
--
ALTER TABLE `product_features`
  ADD CONSTRAINT `product_features_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD CONSTRAINT `product_inventory_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_inventory_ibfk_3` FOREIGN KEY (`size_value`) REFERENCES `store_sizes` (`size_value`) ON UPDATE CASCADE,
  ADD CONSTRAINT `product_inventory_ibfk_4` FOREIGN KEY (`pclrID`) REFERENCES `product_colors` (`pclrID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `product_inventory_ibfk_5` FOREIGN KEY (`color`) REFERENCES `store_colors` (`color`) ON UPDATE CASCADE;

--
-- Ограничения за таблица `product_photos`
--
ALTER TABLE `product_photos`
  ADD CONSTRAINT `product_photos_ibfk_1` FOREIGN KEY (`prodID`) REFERENCES `products` (`prodID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `site_text_usage`
--
ALTER TABLE `site_text_usage`
  ADD CONSTRAINT `site_text_usage_ibfk_1` FOREIGN KEY (`textID`) REFERENCES `site_texts` (`textID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `translation_beans`
--
ALTER TABLE `translation_beans`
  ADD CONSTRAINT `translation_beans_ibfk_1` FOREIGN KEY (`langID`) REFERENCES `languages` (`langID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `translation_phrases`
--
ALTER TABLE `translation_phrases`
  ADD CONSTRAINT `translation_phrases_ibfk_1` FOREIGN KEY (`langID`) REFERENCES `languages` (`langID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `translation_phrases_ibfk_2` FOREIGN KEY (`textID`) REFERENCES `site_texts` (`textID`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
