<?php

namespace LeKoala\CmsActions;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\LiteralField;

/**
 * Custom links to include in getCMSActions
 *
 * Link handlers are declared on the DataObject itself
 */
class CustomLink extends LiteralField
{
    use CustomButton;
    use DefaultLink;
    use ProgressiveAction;

    /**
     * @var boolean
     */
    protected $noAjax = false;

    /**
     * @param string $name
     * @param string $title
     * @param string|array $link Will default to name of link on current record if not set
     */
    public function __construct($name, $title = null, $link = null)
    {
        if ($title === null) {
            $title = FormField::name_to_label($name);
        }

        parent::__construct($name, '');

        // Reset the title later on because we passed '' to parent
        $this->title = $title;

        if ($link && is_string($link)) {
            $this->link = $link;
        } else {
            $this->link = $this->getModelLink($name, $link);
        }
    }

    /**
     * @return string
     */
    public function Type()
    {
        if ($this->progressive) {
            return 'progressive-action';
        }

        return 'custom-link';
    }

    /**
     * @param array $properties
     * @return FormField|string
     */
    public function FieldHolder($properties = [])
    {
        $link = $this->link;

        $title = $this->getButtonTitle();
        $classes = [$this->extraClass()];
        if ($this->noAjax) {
            $classes[] = 'no-ajax';
        }

        if ($this->buttonIcon) {
            $classes[] = "font-icon";
            $classes[] = sprintf('font-icon-%s', $this->buttonIcon);
            $classes[] = "btn-mobile-collapse";
        }

        $attrs = [];

        // note: links with target are never submitted through ajax
        if ($this->newWindow) {
            $attrs[] = 'target="_blank"';
        }
        if ($this->confirmation) {
            $attrs[] = sprintf('data-message="%s"', Convert::raw2htmlatt($this->confirmation));
            if ($this->progressive) {
                $classes[] = "confirm";
            } else {
                $attrs[] = 'onclick="return confirm(this.dataset.message);"';
            }
        }
        foreach ($this->attributes as $attributeKey => $attributeValue) {
            $attrs[] = sprintf('%s="%s"', $attributeKey, $attributeValue);
        }

        $content = sprintf(
            '<a href="%s" class="%s" %s><span>%s</span></a>',
            $link,
            implode(' ', $classes),
            implode(' ', $attrs),
            $title
        );
        $this->content = $content;

        return parent::FieldHolder();
    }

    /**
     * Hide this action as it needs to exist to be forwarded to the model,
     * but you might not want to display it in the action bar
     *
     * @return $this
     */
    public function setHidden()
    {
        $this->addExtraClass("d-none");

        return $this;
    }

    /**
     * Get the value of noAjax
     * @return boolean
     */
    public function getNoAjax()
    {
        return $this->noAjax;
    }

    /**
     * Set the value of noAjax
     *
     * @param boolean $noAjax
     * @return $this
     */
    public function setNoAjax($noAjax)
    {
        $this->noAjax = $noAjax;

        return $this;
    }
}
