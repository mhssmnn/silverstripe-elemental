<?php

/**
 * @package elemental
 */
class ElementPublishChildren extends DataExtension {

	public function onBeforeVersionedPublish() {
		$staged = array();

		foreach($this->owner->Elements() as $widget) {
			$staged[] = $widget->ID;

			$widget->publish('Stage', 'Live');
		}

		// remove any elements that are on live but not in draft or have been
		// unlinked from everything
		$widgets = Versioned::get_by_stage('BaseElement', 'Live', "ParentID = '". $this->owner->ID ."' OR ListID = '".$this->owner->ID."'");

		foreach($widgets as $widget) {
			if(!in_array($widget->ID, $staged)) {
				if($this->owner->hasMethod('shouldCleanupElement')) {
					if($this->owner->shouldCleanupElement($widget)) {
						$widget->deleteFromStage('Live');
					}
				} else {
					$widget->deleteFromStage('Live');
				}
			}
		}
	}
}