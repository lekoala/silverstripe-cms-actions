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
 * It create the "Actions" (or custom) column if it doesn't exist yet
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/forms/how_tos/create_a_gridfield_actionprovider/
 */
abstract class GridFieldRowButton implements GridField_ColumnProvider, GridField_ActionProvider
{
    /**
     * Column name
     *
     * @var string
     */
    protected $columnName = 'Actions';

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
     * Adds class no-ajax if false
     *
     * @var boolean
     */
    protected $ajax = false;

    /**
     * Adds Bootstrap style class if not $fontIcon (eg btn-primary / btn-dark / btn-warning etc)
     *
     * @var string one of the btn-XXX styles (Bootstrap)
     */
    protected $btnStyleClass = 'dark';

    /**
     * @var int
     */
    protected $parentID;

    /**
     * @param string $columnName name of the column for this button (default null -> 'Actions')
     */
    public function __construct($columnName = null)
    {
        if ($columnName) {
            $this->columnName = $columnName;
        }
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string Label for the gridfield button
     */
    abstract function getButtonLabel(GridField $gridField, $record, $columnName);

    abstract function doHandle(GridField $gridField, $actionName, $arguments, $data);

    public function getActionName()
    {
        $class = (new ReflectionClass(get_called_class()))->getShortName();
        // ! without lowercase, in does not work
        return strtolower(str_replace('Button', '', $class));
    }

    /**
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array($this->columnName, $columns)) {
            $columns[] = $this->columnName;
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
        // right-align if this column contains icon-buttons
        return ['class' => $this->fontIcon ? 'grid-field__col-compact' : ''];
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
        // No titles for action column IF icon button
        if ($columnName == $this->columnName && $this->fontIcon) {
            return ['title' => ''];
        }
        return ['title' => $columnName];
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return [$this->columnName];
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return [$this->getActionName()];
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
        $actionLabel = $this->getButtonLabel($gridField, $record, $columnName);

        $field = GridField_FormAction::create(
            $gridField,
            $actionName . '_' . $record->ID,
            ($this->fontIcon ? false : $actionLabel),
            $actionName,
            ['RecordID' => $record->ID, 'ParentID' => $this->parentID]
        )
            ->addExtraClass('gridfield-button-' . $actionName)
            ->setAttribute('title', $actionLabel);

        if (!$this->ajax) {
            $field->addExtraClass('no-ajax');
        }

        if ($this->hiddenOnHover) {
            $field->addExtraClass('grid-field__icon-action--hidden-on-hover');
        }

        if ($this->fontIcon) {
            $field->addExtraClass('grid-field__icon-action btn--icon-large font-icon-' . $this->fontIcon);
        } else {
            // Add a regular button
            $field->addExtraClass('btn btn-' . $this->btnStyleClass);
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
