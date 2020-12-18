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

    public function Type()
    {
        return 'custom-link';
    }

    public function FieldHolder($properties = array())
    {
        $link = $this->link;

        $title = $this->getButtonTitle();
        $classes = $this->extraClass();
        if ($this->noAjax) {
            $classes .= ' no-ajax';
        }

        $attrs = '';

        // note: links with target are never submitted through ajax
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        if ($this->confirmation) {
            $attrs .= ' data-message="' . Convert::raw2htmlatt($this->confirmation) . '"';
            $attrs .= ' onclick="return confirm(this.dataset.message);"';
        }

        $content = '<a href="' . $link . '" class="' . $classes . '"' . $attrs . '>' . $title . '</a>';
        $this->content = $content;
        return parent::FieldHolder();
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
