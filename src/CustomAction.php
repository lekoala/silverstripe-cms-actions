<?php

namespace LeKoala\CmsActions;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormAction;

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

    public function Field($properties = [])
    {
        if ($this->buttonIcon) {
            $this->addExtraClass('font-icon');
            $this->addExtraClass('font-icon-' . $this->buttonIcon);
        }
        // Note: type should stay "action" to properly submit
        $this->addExtraClass('custom-action');
        if ($this->confirmation) {
            $this->setAttribute('data-message', Convert::raw2htmlatt($this->confirmation));
            $this->setAttribute('onclick', 'return confirm(this.dataset.message);return false;');
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
}
