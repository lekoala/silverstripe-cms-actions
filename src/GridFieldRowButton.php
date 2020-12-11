<?php

namespace LeKoala\CmsActions;

use ReflectionClass;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;

/**
 * A boilerplate to create row level buttons
 *
 * It create the "Actions" columns if it doesn't exist yet
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/forms/how_tos/create_a_gridfield_actionprovider/
 */
abstract class GridFieldRowButton implements GridField_ColumnProvider, GridField_ActionProvider
{

    /**
     * A silverstripe icon
     *
     * @var string
     */
    protected $fontIcon;

    /**
     * Adds class grid-field__icon-action--hidden-on-hover if set
     *
     * @var boolean
     */
    protected $hiddenOnHover = true;

    /**
     * @var int
     */
    protected $parentID;

    abstract function getButtonLabel();

    abstract function doHandle(GridField $gridField, $actionName, $arguments, $data);

    public function getActionName()
    {
        $class = (new ReflectionClass($this->record))->getShortName();
        // ! without lowercase, in does not work
        return strtolower(str_replace('Button', '', $class));
    }

    /**
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::create_tag()
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return array('class' => 'grid-field__col-compact');
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            // No titles for action column
            return array('title' => '');
        }
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return array('Actions');
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return array($this->getActionName());
    }

    /**
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $actionName = $this->getActionName();
        $field = GridField_FormAction::create(
            $gridField,
            $actionName . '_' . $record->ID,
            false,
            $actionName,
            array(
                'RecordID' => $record->ID,
                'ParentID' => $this->parentID
            )
        )
            ->addExtraClass('gridfield-button-' . $actionName . ' no-ajax')
            ->setAttribute('title', $this->getButtonLabel());

        if ($this->hiddenOnHover) {
            $field->addExtraClass('grid-field__icon-action--hidden-on-hover');
        }

        if ($this->fontIcon) {
            $field->addExtraClass('grid-field__icon-action btn--icon-large font-icon-' . $this->fontIcon);
        } else {
            // TODO: add some way to do something nice
        }

        return $field->Field();
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == $this->getActionName()) {
            $result = $this->doHandle($gridField, $actionName, $arguments, $data);
            if ($result) {
                return $result;
            }

            // Do something!
            $controller =  Controller::curr();
            return $controller->redirectBack();
        }
    }

    /**
     * Get the parent record id
     *
     * @return int
     */
    public function getParentID()
    {
        return $this->parentID;
    }

    /**
     * Set the parent record id
     *
     * This will be passed as ParentID along RecordID in the arguments parameter
     *
     * @param int $id
     * @return $this
     */
    public function setParentID($id)
    {
        $this->parentID = $id;
        return $this;
    }

    /**
     * Get a silverstripe icon
     *
     * @return  string
     */
    public function getFontIcon()
    {
        return $this->fontIcon;
    }

    /**
     * Set a silverstripe icon
     *
     * @param  string  $fontIcon  A silverstripe icon
     *
     * @return $this
     */
    public function setFontIcon(string $fontIcon)
    {
        $this->fontIcon = $fontIcon;
        return $this;
    }

    /**
     * Get adds class grid-field__icon-action--hidden-on-hover if set
     *
     * @return  boolean
     */
    public function getHiddenOnHover()
    {
        return $this->hiddenOnHover;
    }

    /**
     * Set adds class grid-field__icon-action--hidden-on-hover if set
     *
     * @param  boolean  $hiddenOnHover  Adds class grid-field__icon-action--hidden-on-hover if set
     *
     * @return $this
     */
    public function setHiddenOnHover(bool $hiddenOnHover)
    {
        $this->hiddenOnHover = $hiddenOnHover;
        return $this;
    }
}
