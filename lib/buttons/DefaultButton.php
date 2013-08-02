<?php
include_once ("lib/buttons/StyledButton.php");

class DefaultButton extends StyledButton
{

    public function __construct($type=StyledButton::TYPE_LINK)
    {
	      parent::__construct($type);

	      $this->image_align = StyledButton::ALIGN_LEFT;

    }

}

?>