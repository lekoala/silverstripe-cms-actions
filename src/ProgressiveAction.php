<?php

namespace LeKoala\CmsActions;

trait ProgressiveAction
{
    protected $progressive = false;

    /**
     * Get the value of progressive
     * @return mixed
     */
    public function getProgressive()
    {
        return $this->progressive;
    }

    /**
     * Set the value of progressive
     *
     * @param mixed $progressive
     * @return $this
     */
    public function setProgressive($progressive)
    {
        $this->progressive = $progressive;

        return $this;
    }
}
