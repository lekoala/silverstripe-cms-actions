<?php

namespace LeKoala\CmsActions;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\LiteralField;
use LeKoala\CmsActions\DefaultLink;

/**
 * A simple button that links to a given action or url
 *
 * This is meant to be used inside getCMSFields or getCMSUtils
 *
 * Action must be implemented on the controller (ModelAdmin for instance)
 * The data passed in the content of the form
 */
class CmsInlineFormAction extends LiteralField
{
    use DefaultLink;

    /**
     * @var array
     */
    protected $params = [];


    /**
     * @var string
     */
    protected $buttonIcon = null;

    /**
     * Create a new action button.
     * @param action The method to call when the button is clicked
     * @param title The label on the button
     * @param extraClass A CSS class to apply to the button in addition to 'action'
     */
    public function __construct($action, $title = "", $extraClass = 'btn-primary')
    {
        parent::__construct($action, $title);
        $this->addExtraClass($extraClass);
    }

    public function performReadonlyTransformation()
    {
        return $this->castedCopy(self::class);
    }

    public function getLink()
    {
        if (!$this->link) {
            $this->link = $this->getControllerLink($this->name, $this->params);
        }
        return $this->link;
    }

    /**
     * Get an icon for this button
     *
     * @return string
     */
    public function getButtonIcon()
    {
        return $this->buttonIcon;
    }

    /**
     * Set an icon for this button
     *
     * Feel free to use SilverStripeIcons constants
     *
     * @param string $buttonIcon An icon for this button
     * @return $this
     */
    public function setButtonIcon(string $buttonIcon)
    {
        $this->buttonIcon = $buttonIcon;
        return $this;
    }

    public function Type()
    {
        return 'inline-action';
    }

    public function FieldHolder($properties = array())
    {
        $classes = $this->extraClass();
        if($this->buttonIcon) {
            $classes .= " font-icon";
            $classes .= ' font-icon-'.$this->buttonIcon;
        }
        $link = $this->getLink();
        $attrs = '';
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        if ($this->readonly) {
            $attrs .= ' style="display:none"';
        }
        $content = '<a href="' . $link . '" class="btn ' . $classes . ' action no-ajax"' . $attrs . '>';
        $title = $this->content;
        $content .= $title;
        $content .= '</a>';
        $this->content = $content;

        return parent::FieldHolder($properties);
    }

    /**
     * Get the value of params
     *
     * @return  array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the value of params
     *
     * @param  array  $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }
}
