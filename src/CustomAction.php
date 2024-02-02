<?php

namespace LeKoala\CmsActions;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Custom actions to use in getCMSActions
 *
 * Actions handlers are declared on the DataObject itself
 *
 * Because it is an action, it will be submitted through ajax
 * If you want to create links that open files or show a new page, use CustomLink
 */
class CustomAction extends FormAction
{
    use CustomButton;

    /**
     * @var boolean
     */
    public $useButtonTag = true;

    /**
     * Used in ActionsGridFieldItemRequest::forwardActionToRecord
     * @var boolean
     */
    protected $shouldRefresh = false;

    /**
     * @var string
     */
    protected $redirectURL = null;

    public function __construct($name, $title, $form = null)
    {
        // Actually, an array works just fine!
        $name = 'doCustomAction[' . $name . ']';

        parent::__construct($name, $title, $form);
    }

    public function actionName()
    {
        return rtrim(str_replace('action_doCustomAction[', '', $this->name), ']');
    }

    public function Type()
    {
        return 'action';
    }

    /**
     * @param array<string,mixed> $properties
     * @return DBHTMLText
     */
    public function Field($properties = [])
    {
        $icon = $this->buttonIcon;
        if (!$icon) {
            $icon = $this->icon;
        }
        if ($icon) {
            $this->addExtraClass('font-icon');
            $this->addExtraClass('font-icon-' . $icon);
            $this->addExtraClass('btn-mobile-collapse'); // we can collapse by default on mobile with an icon
        }
        // Note: type should stay "action" to properly submit
        $this->addExtraClass('custom-action');
        if ($this->confirmation) {
            $this->setAttribute('data-message', Convert::raw2htmlatt($this->confirmation));
            $this->setAttribute('onclick', 'return confirm(this.dataset.message);return false;');
        }

        if ($this->hasLastIcon()) {
            $this->addExtraClass('btn-mobile-collapse'); // we can collapse by default on mobile with an icon
        }

        return parent::Field($properties);
    }

    /**
     * Get the value of shouldRefresh
     * @return mixed
     */
    public function getShouldRefresh()
    {
        return $this->shouldRefresh;
    }

    /**
     * Set the value of shouldRefresh
     *
     * @param mixed $shouldRefresh
     * @return $this
     */
    public function setShouldRefresh($shouldRefresh)
    {
        $this->shouldRefresh = $shouldRefresh;

        return $this;
    }

    /**
     * Get the value of redirectURL
     * @return mixed
     */
    public function getRedirectURL()
    {
        return $this->redirectURL;
    }

    /**
     * Set the value of redirectURL
     *
     * @param mixed $redirectURL
     * @return $this
     */
    public function setRedirectURL($redirectURL)
    {
        $this->redirectURL = $redirectURL;

        return $this;
    }
}
