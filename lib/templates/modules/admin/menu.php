<?php
//
// admin module - core menu. Store and site menu.php run after it and extend
// SparkTemplateAdminPage::Menu() (prepend / append / insert) or disable paths.
//
include_once("utils/menu/MenuItem.php");

$menu = new MenuItemList();

$content = new MenuItem("Content", "content");
$menu->append($content);

$content->append(new MenuItem("Pages", "pages"));
$content->append(new MenuItem("News", "news"));
$faq = new MenuItem("FAQ", "faq");
$faq->append(new MenuItem("Sections", "sections"));
$content->append($faq);
$content->append(new MenuItem("Menus", "menus"));

$settings = new MenuItem("Settings", "settings");
$menu->append($settings);

$settings->append(new MenuItem("Admins", "admins"));
$languages = new MenuItem("Languages", "languages");
$translator = new MenuItem("Translator", "translator");
$languages->append($translator);
$settings->append($languages);

//one translator entry per language - its phrases
include_once("beans/LanguagesBean.php");
$languagesBean = new LanguagesBean();
$query = $languagesBean->query($languagesBean->key(), "lang_code");
$query->exec();
while ($row = $query->next()) {
    $code = strtoupper((string)$row["lang_code"]);
    $item = new MenuItem($code, "phrases?langID=" . (int)$row[$languagesBean->key()]);
    $item->enableTranslation(false);
    $translator->append($item);
}
$query->free();

SparkTemplateAdminPage::SetMenu($menu);
