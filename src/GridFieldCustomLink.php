<?php

namespace LeKoala\CmsActions;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridField;

/**
 * Expose a custom link in a GridField at row level
 * Action must be declared in getCMSActions to work
 */
class GridFieldCustomLink extends GridFieldRowLink
{
    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string The link to the action
     */
    public function getLink($gridField, $record, $columnName)
    {
        return Controller::join_links($gridField->Link('item'), $record->ID, 'doCustomLink') . '?CustomLink=' . $this->name;
    }
}
