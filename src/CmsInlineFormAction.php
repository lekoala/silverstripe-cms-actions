<?php

namespace LeKoala\CmsActions;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\LiteralField;

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
     * @param action $action The method to call when the button is clicked
     * @param title $title The label on the button
     * @param extraClass $extraClass A CSS class to apply to the button in addition to 'action'
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

    /**
     * @return string
     */
    public function Type()
    {
        return 'inline-action';
    }

    /**
     * @param $properties
     * @return FormField|string
     */
    public function FieldHolder($properties = [])
    {
        $classes = [$this->extraClass()];
        if ($this->buttonIcon) {
            $classes[] = "font-icon";
            $classes[] = sprintf('font-icon-%s', $this->buttonIcon);
        }
        if ($this->progressive) {
            $classes[] = "progressive-action";
        }
        $link = $this->getLink();
        $attrs = [];
        if ($this->newWindow) {
            $attrs[] = 'target="_blank"';
        }
        if ($this->readonly) {
            $attrs[] = 'style="display:none"';
        }
        if (strlen($this->submitSelector)) {
            $attrs[] = sprintf('data-submit-selector="%s"', $this->submitSelector);
        }
        $title = $this->content;
        if ($this->post) {
            $content = sprintf('<button data-action="%s" class="btn no-ajax %s" %s>%s</button>',
                $link,
                implode(' ', $classes),
                implode('', $attrs),
                $title
            );
        } else {
            $content = sprintf('<a href="%s" class="btn action no-ajax %s" %s>%s</a>',
                $link,
                implode(' ', $classes),
                implode('', $attrs),
                $title
            );
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
     * @param array $params
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
