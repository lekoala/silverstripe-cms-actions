<?php

namespace LeKoala\CmsActions;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * Provides a custom link for a single record
 *
 * Links do not trigger actions, use GridFieldRowButton instead
 */
class GridFieldRowLink implements GridField_ColumnProvider
{
    /**
     * HTML classes to be added to GridField buttons
     *
     * @var array<string,mixed>
     */
    protected $extraClass = [
        'grid-field__icon-action--hidden-on-hover' => true,
        'btn--icon-large'                          => true
    ];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var boolean
     */
    protected $newWindow = true;

    /**
     * @param string $name
     * @param string $title
     * @param string $icon
     */
    public function __construct($name, $title, $icon = 'white-question')
    {
        $this->name = $name;
        $this->title = $title;
        $this->addExtraClass('font-icon-' . $icon);
    }

    /**
     * Add a column 'Actions'
     *
     * @param GridField $gridField
     * @param array<string> $columns
     * @return void
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
     * @return array<string,string>
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'grid-field__col-compact'];
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array<string|int,string|null>
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName === 'Actions') {
            return ['title' => ''];
        }

        return [];
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array<string>
     */
    public function getColumnsHandled($gridField)
    {
        return ['Actions'];
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string The link to the action
     */
    public function getLink($gridField, $record, $columnName)
    {
        return Controller::join_links($gridField->Link('item'), $record->ID, $this->name);
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string The HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($this->newWindow) {
            $this->addExtraClass('no-ajax');
        }

        $data = new ArrayData(
            [
                'Link' => $this->getLink($gridField, $record, $columnName),
                'ExtraClass' => $this->getExtraClass(),
                'Title' => $this->title,
                'NewWindow' => $this->newWindow
            ]
        );

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);

        return $data->renderWith($template);
    }

    /**
     * Get the extra HTML classes to add for edit buttons
     *
     * @return string
     */
    public function getExtraClass()
    {
        return implode(' ', array_keys($this->extraClass));
    }

    /**
     * Add an extra HTML class
     *
     * @param string $class
     * @return $this
     */
    public function addExtraClass($class)
    {
        $this->extraClass[$class] = true;

        return $this;
    }

    /**
     * Remove an HTML class
     *
     * @param string $class
     * @return $this
     */
    public function removeExtraClass($class)
    {
        unset($this->extraClass[$class]);

        return $this;
    }


    /**
     * Get the value of name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of newWindow
     * @return bool
     */
    public function getNewWindow()
    {
        return $this->newWindow;
    }

    /**
     * Set the value of newWindow
     *
     * @param bool $newWindow
     * @return $this
     */
    public function setNewWindow($newWindow)
    {
        $this->newWindow = $newWindow;
        return $this;
    }
}
