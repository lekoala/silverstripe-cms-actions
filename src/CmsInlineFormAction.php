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
    use ProgressiveAction;

    /**
     * @var array
     */
    protected $params = [];


    /**
     * @var string
     */
    protected $buttonIcon = null;

    /**
     * @var boolean
     */
    protected $post = false;

    /**
     * This will be the selector that's click event gets called by {@see self}'s entwine event.
     *
     * This is a temporary hack since form.sumbit() doesn't seem to be working.
     * For example setting this up to work on CMSEditPage:
     * ```
     * CmsInlineFormAction::create('myAction', 'My Action')->setSubmitSelector('Form_ItemEditForm_action_save');
     * ```
     * You can also use this to hackishly publish on post.
     *
     * @var string
     */
    protected $submitSelector = '#Form_ItemEditForm_action_doSave';

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

    public function FieldHolder($properties = [])
    {
        $classes = $this->extraClass();
        if ($this->buttonIcon) {
            $classes .= " font-icon";
            $classes .= ' font-icon-' . $this->buttonIcon;
        }
        if ($this->progressive) {
            $classes .= " progressive-action";
        }
        $link = $this->getLink();
        $attrs = '';
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        if ($this->readonly) {
            $attrs .= ' style="display:none"';
        }
        if (strlen($this->submitSelector)) {
            $attrs .= " data-submit-selector=\"{$this->submitSelector}\"";
        }
        $title = $this->content;
        if ($this->post) {
            // This triggers a save action to the new location
            $content = '<button data-action="' . $link . '" class="btn ' . $classes . ' no-ajax"' . $attrs . '>';
            $content .= $title;
            $content .= '</button>';
        } else {
            $content = '<a href="' . $link . '" class="btn ' . $classes . ' action no-ajax"' . $attrs . '>';
            $content .= $title;
            $content .= '</a>';
        }
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

    /**
     * Get the value of post
     * @return boolean
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set the value of post
     *
     * @param boolean $post
     * @return $this
     */
    public function setPost($post)
    {
        $this->post = $post;
        return $this;
    }

    /**
     * Get the value of {@see self::$submitSelector}
     *
     * @return string
     */
    public function getSubmitSelector()
    {
        return $this->submitSelector;
    }

    /**
     * Set the value of {@see self::$submitSelector}
     *
     * Includes
     *
     * @param string $selector
     * @return $this
     */
    public function setSubmitSelector($selector)
    {
        $this->submitSelector = $selector;
        return $this;
    }
}
