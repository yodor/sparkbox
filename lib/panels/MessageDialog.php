<?php
include_once ("lib/components/Component.php");
include_once ("lib/buttons/StyledButton.php");

class MessageDialog extends Component implements IFinalRenderer, IHeadRenderer
{
	const TYPE_PLAIN = 0;
	const TYPE_ERROR = 1;
	const TYPE_INFO = 2;
	const TYPE_QUESTION = 3;
	
	
	const BUTTON_ACTION_CONFIRM = "confirm";
	const BUTTON_ACTION_CANCEL = "cancel";
	const BUTTON_ACTION_CLOSE = "close";
    
	protected $type = MessageDialog::TYPE_INFO;
	protected $buttons = array();

	protected $title = "";
	protected $id = "";
	protected $icon_class = "";
	
	
	
	public $show_close_button = false;
	
	public function __construct($title="Message", $id = "message_dialog")
	{
	      parent::__construct();

	      $this->title = $title;
	      $this->id = $id;

	      $this->attributes["id"] = $id;

	      $btn_ok = StyledButton::DefaultButton();
	      $btn_ok->setButtonType(StyledButton::TYPE_BUTTON);
	      $btn_ok->setText("OK");
	      $btn_ok->setAttribute("action", MessageDialog::BUTTON_ACTION_CONFIRM);

	      $this->buttons[MessageDialog::BUTTON_ACTION_CONFIRM] = $btn_ok;

	      $this->setClassName("PopupPanel SimplePanel");


	      $this->setDialogType(MessageDialog::TYPE_INFO);

	}

	public function renderScript()
	{
	    echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/popups/MessageDialog.js'></script>";
	    echo "\n";
	}
	public function renderStyle()
	{
	    echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/MessageDialog.css' type='text/css' >";
	    echo "\n";
	}

	public function clearButtons()
	{
	    $this->buttons = array();
	}
	public function getButtonForAction($action)
	{
	      if (isset($this->buttons[$action]))return $this->buttons[$action];
	}
	public function getButtonAt($pos)
	{
	      $key_pos = array_values(array_keys($this->buttons));

	      return $this->buttons[$key_pos[$pos]];
	}
	public function getButton($text)
	{
	      return $this->buttons[$text];
	}
	public function appendButton(StyledButton $btn)
	{
	      $this->buttons[$btn->getText()] = $btn;
	}
	public function getButtons()
	{
	      return $this->buttons;
	}

	public function startRender()
	{

		parent::startRender();

		if (strlen($this->title)>0){
		      echo "<div class='caption'>";

		      if ($this->show_close_button) {
			  $b = StyledButton::DefaultButton();
			  $b->setText("X");
			  $b->setAttribute("action", MessageDialog::BUTTON_ACTION_CLOSE);
			  $b->render();
		      }

		      echo "<span class='caption_text'>".tr($this->title)."</span>";


		      echo "<div class=clear></div>";
		      echo "</div>";
		}
		
		echo "<div class='Inner'>";

		if ($this->type === MessageDialog::TYPE_PLAIN) {
		
		}
		else {
		    echo "<div class='message_icon {$this->icon_class}'></div>";
		    echo "<div class='message_text'>";
		}
	}

	public function renderImpl()
	{	

	
		
	}

	public function finishRender()
	{
		if ($this->type === MessageDialog::TYPE_PLAIN) {
		
		}
		else {
		  echo "</div>";//message_text
		}

		echo "<div class=clear></div>";

		$this->drawButtons();

		echo "<div class=clear></div>";

		echo "</div>"; //inner
		
		parent::finishRender();
		

	}
	public function drawButtons()
	{
		if (count($this->buttons)>0) {
		    echo "<div class='buttons_bar'>";
		    foreach ($this->buttons as $pos=>$btn) {
// 			echo "<div class='button_slot'>";
			$btn->render();
// 			echo "</div>";
		    }
		    echo "<div class=clear></div>";
		    echo "</div>";
		}
	}
	public function setDialogType($type)
	{
		$this->type = $type;
		
		$icon_class="";
		if ($this->type==MessageDialog::TYPE_ERROR){
			$icon_class="icon_error";
		}
		else if ($this->type==MessageDialog::TYPE_QUESTION){
			$icon_class="icon_question";
		}
		else if ($this->type==MessageDialog::TYPE_INFO) {
			$icon_class="icon_info";
		}
		else if ($this->type==MessageDialog::TYPE_PLAIN) {
			$icon_class="";
			$this->clearButtons();
		}
		
		$this->icon_class = $icon_class;
	}

	public function renderFinal()
	{
	    try {
		$this->startRender();
		$this->renderImpl();
		$this->finishRender();
	    }
	    catch (Exception $e) {
		echo $e->getMessage();
	    }
	}
}
?>
