<?php
/**
 * @package elemental
 */
class ElementLink extends BaseElement {

	private static $db = array(
		'LinkType' => "Enum('Internal, External', 'Internal')",
		'LinkText' => 'Varchar(255)',
		'LinkDescription' => 'Text',
		'NewWindow' => 'Boolean'
	);

	private static $has_one = array(
		'InternalLink' => 'SiteTree'
	);

	private static $title = "Link Element";

	private static $description = "";

	public function getCMSFields() {
		$this->beforeUpdateCMSFields(function($fields) {

			$type = OptionsetField::create('LinkType', 'Link Type', $this->dbObject('LinkType')->enumValues());
			$type->setHasEmptyDefault(false);

			// External
			$url = TextField::create('LinkURL', 'Link URL');
			$url->setRightTitle('Including protocol e.g: '.Director::absoluteBaseURL());
			$url->displayIf('LinkType')->isEqualTo('External');

			$window = CheckboxField::create('NewWindow', 'Open in a new window');
			$window->displayIf('LinkType')->isEqualTo('External');

			// Internal
			$sitetree = DisplayLogicWrapper::create(TreeDropdownField::create('InternalLinkID', 'Link To', 'SiteTree'));
			$sitetree->hideIf('LinkType')->isEqualTo('External');
			$text = TextField::create('LinkText', 'Link Text');
			$text->hideIf('LinkType')->isEqualTo('External');
			$desc = TextareaField::create('LinkDescription', 'Link Description');
			$desc->hideIf('LinkType')->isEqualTo('External');

			$fields->addFieldsToTab('Root.Main', array(				
				$type, $url, $window, $sitetree, $text, $desc				
			));

			$fields->removeByName('Type');
			$fields->removeByName('InternalLinkID');
		});

		return parent::getCMSFields();
	}
}