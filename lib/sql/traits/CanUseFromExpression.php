<?php

trait CanUseFromExpression
{

    /**
     * Proxy method for FromExpression expr().
     *
     * Return instance to the internal FromExpression.
     *
     * Initialize the FromExpression buffer contents with \$text if \$text is not empty and not null.
     *
     * @param string|null $text
     * @return FromExpression Return the FromExpression itself
     * @throws Exception
     */
    public function from(?string $text=null) : FromExpression
    {
        if (!is_null($text) && strlen(trim($text))>0) return $this->_from->expr($text);
        return $this->_from;
    }
}