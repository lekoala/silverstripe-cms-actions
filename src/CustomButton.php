<?php

namespace LeKoala\CmsActions;

/**
 * Common button functionnality that is shared between CustomAction and CustomLink
 */
trait CustomButton
{
    /**
     * Default classes applied in constructor
     * @config
     * @var array
     */
    private static $default_classes = [
        'btn', 'btn-info'
    ];

    /**
     * Whether to place the button in a dot-menu
     * @var bool
     */
    protected $dropUp = false;

    /**
     * An icon for this button
     * @var string
     */
    protected $buttonIcon;

    /**
     * The confirmation message
     * @var string
     */
    protected $confirmation;

    /**
     * Get the title of the link
     * Called by ActionsGridFieldItemRequest to build default message
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
     * @param string $is
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the dropUp value
     * Called by ActionsGridFieldItemRequest to determine placement
     *
     * @return bool
     */
    public function getDropUp()
    {
        return $this->dropUp;
    }

    /**
     * Set the value of dropUp
     *
     * @param bool $is
     * @return $this
     */
    public function setDropUp($is)
    {
        $this->dropUp = !!$is;
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setButtonType($type)
    {
        if ($this->extraClasses) {
            foreach ($this->extraClasses as $k => $v) {
                if (strpos($k, 'btn-') !== false) {
                    unset($this->extraClasses[$k]);
                }
            }
        }

        $btn = "btn-$type";
        $this->extraClasses[$btn] = $btn;

        return $this;
    }

    /**
     * Get the title with icon if set
     *
     * @return string
     */
    protected function getButtonTitle()
    {
        $title = $this->title;
        return $title;
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
     * Feel free to use {@link https://github.com/CSoellinger/silverstripe-admin-icons}
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
     * Get the value of confirmation
     */
    public function getConfirmation()
    {
        return $this->confirmation;
    }

    /**
     * Set the value of confirmation
     *
     * @param string|bool A confirm message or true for a generic message
     * @return $this
     */
    public function setConfirmation($confirmation)
    {
        if ($confirmation === true) {
            $confirmation = _t('CustomButton.CONFIRM_MESSAGE', 'Are you sure?');
        }
        $this->confirmation = $confirmation;
        return $this;
    }
}
