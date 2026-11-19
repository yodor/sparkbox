<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

/**
 * The SEO inputs of a form - the same columns on every table that has them (seo_title, seo_description): the menu
 * items, the products, the categories, the sections ... A form adds them with SEOInputGroup::AddTo($this).
 * Empty values leave the page its default title and description (SEOData).
 */
class SEOInputGroup extends InputGroup
{
    const string NAME = "seo";

    const string TITLE = "seo_title";
    const string DESCRIPTION = "seo_description";

    public function __construct()
    {
        parent::__construct(SEOInputGroup::NAME, "SEO");
        $this->setDescription("The title and the description search engines show for this page - empty: the name and the text of the page");
    }

    /**
     * Add the group and its inputs to '$form'
     * @param InputForm $form
     * @param bool $translated the values are translated with the other texts of the row (the translator of the admin)
     * @return SEOInputGroup
     * @throws Exception
     */
    public static function AddTo(InputForm $form, bool $translated = true) : SEOInputGroup
    {
        $group = new SEOInputGroup();
        $form->addGroup($group);

        $field = DataInputFactory::Create(InputType::TEXT, SEOInputGroup::TITLE, "SEO Title (up to 60 characters)", 0);
        $field->enableTranslator($translated);
        $field->getRenderer()->input()?->setAttribute("maxLength", "60");
        $field->getRenderer()->input()?->setAttribute("size", "60");
        $form->addInput($field, $group);

        $field = DataInputFactory::Create(InputType::TEXTAREA, SEOInputGroup::DESCRIPTION, "SEO Description (up to 150 characters)", 0);
        $field->enableTranslator($translated);
        $field->getRenderer()->input()?->setAttribute("maxLength", "150");
        $field->getRenderer()->input()?->setAttribute("rows", "3");
        $field->getRenderer()->input()?->setAttribute("cols", "60");
        $form->addInput($field, $group);

        return $group;
    }
}
