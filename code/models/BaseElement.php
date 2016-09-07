<?php

/**
 * @package elemental
 */
class BaseElement extends Widget
{
    /**
     * @var array $db
     */
    private static $db = array(
        'ExtraClass' => 'Varchar(255)',
        'HideTitle' => 'Boolean',
        'Style' => 'Varchar'
    );

    /**
     * @var array $has_one
     */
    private static $has_one = array(
        'List' => 'ElementList' // optional.
    );

    /**
     * @var array $has_many
     */
    private static $has_many = array(
        'VirtualClones' => 'ElementVirtualLinked'
    );

    /**
     * @var string
    */
    private static $title = "Base Element";

    /**
     * @var string
     */
    private static $singular_name = 'Base Element';

    /**
     * @var array
     */
    private static $summary_fields = array(
        'ID' => 'ID',
        'Title' => 'Title',
        'ElementType' => 'Type'
    );

    /**
     * @var array
     */
    private static $searchable_fields = array(
        'ID' => array(
            'field' => 'NumericField'
        ),
        'Title',
        'LastEdited'
    );

	/**
     * @var string
     */
    private static $description = "Base class for elements";

    /**
     * @var boolean
     */
    private static $enable_title_in_template = true;

	/**
     * @var array
     */
    private static $styles = array();


    public function getCMSFields()
    {
        $fields = $this->scaffoldFormFields(array(
            'includeRelations' => ($this->ID > 0),
            'tabbed' => true,
            'ajaxSafe' => true
        ));

        $fields->insertAfter(new ReadonlyField('ClassNameTranslated', _t('BaseElement.TYPE', 'Type'), $this->i18n_singular_name()), 'Title');
        $fields->removeByName('ListID');
        $fields->removeByName('ParentID');
        $fields->removeByName('Sort');
        $fields->removeByName('ExtraClass');

        if (!$this->config()->enable_title_in_template) {
            $fields->removeByName('HideTitle');
            $title = $fields->fieldByName('Root.Main.Title');

            if ($title) {
                $title->setRightTitle('For reference only. Does not appear in the template.');
            }
        }

        if ($styles = $this->config()->get('styles')) {
            $fields->addFieldsToTab('Root.Main', $styles = new DropdownField('Style', 'Style', $styles));

            $styles->setEmptyString('Select a custom style..');
        } else {
            $fields->removeByName('Style');
        }

        $fields->addFieldToTab('Root.Settings', new TextField('ExtraClass', 'Extra CSS Classes to add'));

        if (!is_a($this, 'ElementList')) {
            $lists = ElementList::get()->filter('ParentID', $this->ParentID);

            if ($lists->exists()) {
                $fields->addFieldToTab('Root.Settings',
                    $move = new DropdownField('MoveToListID', 'Move this to another list', $lists->map('ID', 'CMSTitle'), '')
                );

                $move->setEmptyString('Select a list..');
                $move->setHasEmptyDefault(true);
            }
        }


        if($virtual = $fields->dataFieldByName('VirtualClones')) {
            if($this->Parent() && $this->Parent()->exists() && $this->Parent()->getOwnerPage() && $this->Parent()->getOwnerPage()->exists()) {
                $tab = $fields->findOrMakeTab('Root.VirtualClones');
                $tab->setTitle(_t('BaseElement.VIRTUALTABTITLE', 'Linked To'));

                $tab->push(new LiteralField('DisplaysOnPage', sprintf(
                    "<p>The original content block appears on <a href='%s'>%s</a></p>",
                    $this->Parent()->getOwnerPage()->Link(),
                    $this->Parent()->getOwnerPage()->MenuTitle
                )));

                $virtual->setConfig(new GridFieldConfig_Base());
                $virtual
                    ->setTitle(_t('BaseElement.OTHERPAGES', 'Other pages'))
                    ->getConfig()
                        ->removeComponentsByType('GridFieldAddExistingAutocompleter')
                        ->removeComponentsByType('GridFieldAddNewButton')
                        ->removeComponentsByType('GridFieldDeleteAction')
                        ->removeComponentsByType('GridFieldDetailForm')
                        ->addComponent(new ElementalGridFieldDeleteAction());

                $virtual->getConfig()
                    ->getComponentByType('GridFieldDataColumns')
                    ->setDisplayFields(array(
                        'getPage.Title' => 'Title',
                        'getPage.Link' => 'Link'
                    ));
            }
        }

        $this->extend('updateCMSFields', $fields);

        if ($this->IsInDB()) {
            if ($this->isEndofLine('BaseElement') && $this->hasExtension('VersionViewerDataObject')) {
                $fields = $this->addVersionViewer($fields, $this);
            }
        }

        return $fields;
    }

    /**
     * Version viewer must only be added at if this is the final getCMSFields for a class.
     * in order to avoid having to rename all fields from eg Root.Main to Root.Current.Main
     * To do this we test if getCMSFields is from the current class
     */
    public function isEndofLine($className)
    {
        $methodFromClass = ClassInfo::has_method_from(
            $this->ClassName, 'getCMSFields', $className
        );

        if($methodFromClass) {
            return true;
        }
    }


    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->Sort) {
            $parentID = ($this->ParentID) ? $this->ParentID : 0;

            $this->Sort = DB::query("SELECT MAX(\"Sort\") + 1 FROM \"Widget\" WHERE \"ParentID\" = $parentID")->value();
        }

        if ($this->MoveToListID) {
            $this->ListID = $this->MoveToListID;
        }
    }

    /**
     * @return string
     */
    public function i18n_singular_name()
    {
        return _t(__CLASS__, $this->config()->title);
    }

    /**
     * @return string
     */
    public function getElementType()
    {
        return $this->i18n_singular_name();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if ($title = $this->getField('Title')) {
            return $title;
        } else {
            if (!$this->isInDb()) {
                return;
            }

            return $this->config()->title;
        }
    }

    /**
     * @return string
     */
    public function getCMSTitle()
    {
        if ($title = $this->getField('Title')) {
            return $this->config()->title . ': ' . $title;
        } else {
            if (!$this->isInDb()) {
                return;
            }
            return $this->config()->title;
        }
    }

    public function ControllerTop()
    {
        return Controller::curr();
    }

    public function getPage()
    {
        $area = $this->Parent();

        if ($area instanceof ElementalArea) {
            return $area->getOwnerPage();
        }

        return null;
    }

    public function getCssStyle()
    {
        $styles = $this->config()->get('styles');
        $style = $this->Style;

        if (isset($styles[$style])) {
            return strtolower($styles[$style]);
        }
    }

    /**
     * Override the {@link Widget::forTemplate()} method so that holders are not
     * rendered twice. The controller should render with widget inside the
     *
     * @return HTML
     */
    public function forTemplate($holder = true) {
        return parent::forTemplate($holder = false);
    }

    /**
     * Override the {@link Widget::Content()} method which is called from
     * `forTemplate()`. Here we gather templates from the `$template` property.
     *
     * @return HTML
     */
    public function Content() {
        return $this->renderWith($this->getCandidateTemplates());
    }

    /**
     * Note: Can be overloaded in subclasses to specify additional
     * template options.
     *
     * @return array Array of candidate templates
     */
    public function getCandidateTemplates($templates = array()) {
        if ($this->Style) {
            $style = $this->class . "_{$this->Style}";
            array_push($templates, $style);
        }

        array_push($templates, $this->class);

        return $templates;
    }

    /**
     * @return string
     */
    public function getEditLink() {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            'admin/elemental/BaseElement/EditForm/field/BaseElement/item',
            $this->ID,
            'edit'
        );
    }

    public function onBeforeVersionedPublish()
    {

    }
}
