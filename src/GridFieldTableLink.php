<?php

namespace LeKoala\CmsActions;

use ReflectionClass;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

/**
 * Provide a simple way to declare links for GridField tables
 */
class GridFieldTableLink implements GridField_HTMLProvider
{
    use DefaultLink;

    /**
     * Fragment to write the button to
     * @var string
     */
    protected $targetFragment;

    /**
     * @var string
     */
    protected $actionName;

    /**
     * @var string
     */
    protected $buttonLabel;

    /**
     * @var string
     */
    protected $fontIcon;

    /**
     * @var int
     */
    protected $parentID;

    /**
     * @var string
     */
    protected $confirm;

    /**
     * @var string
     */
    protected $prompt;

    /**
     * @var string
     */
    protected $promptDefault;

    /**
     * @var array<string,mixed>
     */
    protected $attributes = [];

    /**
     * @var boolean
     */
    protected $noAjax = false;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param string $buttonLabel
     * @param string $actionName
     */
    public function __construct($targetFragment = "buttons-before-right", $buttonLabel = null, $actionName = null)
    {
        $this->targetFragment = $targetFragment;
        if ($buttonLabel) {
            $this->buttonLabel = $buttonLabel;
        }
        if ($actionName) {
            $this->actionName = $actionName;
        }
    }

    /**
     * @return mixed|string
     */
    public function getActionName()
    {
        if ($this->actionName) {
            return $this->actionName;
        }
        $class = (new ReflectionClass(get_called_class()))->getShortName();

        // ! without lowercase, in does not work
        return strtolower(str_replace('Button', '', $class));
    }

    /**
     * @return string
     */
    public function getButtonLabel()
    {
        return $this->buttonLabel;
    }

    /**
     * Place the export button in a <p> tag below the field
     * @param GridField $gridField
     * @return array<string,mixed>
     */
    public function getHTMLFragments($gridField)
    {
        $action = $this->getActionName();

        $button = CustomLink::create($action, $this->getButtonLabel());
        $button->addExtraClass('btn btn-secondary action_' . $action);
        if ($this->fontIcon) {
            $button->addExtraClass('font-icon-' . $this->fontIcon);
        }
        if ($this->noAjax) {
            $button->setNoAjax($this->noAjax);
        }
        //TODO: replace prompt and confirm with inline js
        if ($this->prompt) {
            $button->setAttribute('data-prompt', $this->prompt);
            $promptDefault = $this->getPromptDefault();
            if ($promptDefault) {
                $button->setAttribute('data-prompt-default', $promptDefault);
            }
        }
        if ($this->confirm) {
            $button->setAttribute('data-confirm', $this->confirm);
        }
        if ($this->newWindow) {
            $button->setNewWindow($this->newWindow);
        }
        if ($this->link) {
            $button->setLink($this->link);
        }
        foreach ($this->attributes as $attributeName => $attributeValue) {
            $button->setAttribute($attributeName, $attributeValue);
        }
        $button->setForm($gridField->getForm());

        return [$this->targetFragment => $button->Field()];
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * @param GridField $gridField
     * @return array<string>
     */
    public function getActions($gridField)
    {
        return [$this->getActionName()];
    }

    /**
     * Get the value of fontIcon
     *
     * @return string
     */
    public function getFontIcon()
    {
        return $this->fontIcon;
    }

    /**
     * Set the value of fontIcon
     *
     * @param string $fontIcon
     *
     * @return $this
     */
    public function setFontIcon($fontIcon)
    {
        $this->fontIcon = $fontIcon;

        return $this;
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
     * @param int $id
     * @return $this
     */
    public function setParentID($id)
    {
        $this->parentID = $id;

        return $this;
    }

    /**
     * Get the value of confirm
     *
     * @return string
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * Set the value of confirm
     *
     * @param string $confirm
     * @return $this
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;

        return $this;
    }

    /**
     * Get the value of prompt
     *
     * @return string
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Set the value of prompt
     *
     * @param string $prompt
     * @return $this
     */
    public function setPrompt($prompt)
    {
        $this->prompt = $prompt;

        return $this;
    }

    /**
     * Get the value of promptDefault
     *
     * @return string
     */
    public function getPromptDefault()
    {
        return $this->promptDefault;
    }

    /**
     * Set the value of promptDefault
     *
     * @param string $promptDefault
     * @return $this
     */
    public function setPromptDefault($promptDefault)
    {
        $this->promptDefault = $promptDefault;

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
