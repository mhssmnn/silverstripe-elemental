<?php

/**
 * @package elemental
 */
class ElementChildrenList extends BaseElement
{

    private static $db = array(
        'SortString' => 'Varchar(100)'
    );

    private static $has_one = array(
        'ParentPage' => 'SiteTree'
    );

    private static $title = "Show a list of pages";

    private static $enable_title_in_template = true;

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            $fields->addFieldsToTab('Root.Main', array(
                TreeDropdownField::create('ParentPageID', 'Parent page', 'SiteTree')
            ));
        });

        return parent::getCMSFields();
    }

    public function getChildrenList()
    {
        if ($page = $this->ParentPage()) {
            return $page->AllChildren()->sort($this->SortString);
        }

        return null;
    }
}
