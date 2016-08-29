<?php

/**
 * @package elemental
 */
class ElementContent extends BaseElement
{

    private static $db = array(
        'HTML' => 'HTMLText'
    );

    private static $title = "Content Block";

    private static $description = "Block of text with heading, blockquote, list and paragraph styles";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if ($this->isEndofLine('ElementContent') && $this->hasExtension('VersionViewerDataObject')) {
            $fields = $this->addVersionViewer($fields, $this);
        }

        return $fields;
    }
}
