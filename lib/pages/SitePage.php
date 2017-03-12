<?php

abstract class SitePage
{
	private static $instance = NULL;
	
	public static function getInstance()
	{
	  return self::$instance;
	}

	protected $page_class = "";
	
	public function __construct()
	{
	    self::$instance =  $this;
	}

	protected function htmlStart()
	{
	    echo "<!DOCTYPE html>";

	    $dir = ' DIR="'.Session::get("page_dir").'" ';

	    echo "<html $dir  >\n";
	    echo "\n";

	}

	protected function dumpMetaTags()
	{
		echo "<meta http-equiv='content-type' content='text/html;charset=utf-8' >\n";
		echo "<meta http-equiv='Content-Style-Type' content='text/css' >\n";
// 		echo "<meta http-equiv='X-UA-Compatible' content='IE=9' >\n";
// 		echo "<meta http-equiv='X-UA-Compatible' content='IE=8' >\n";

		
		
		echo "\n";
	}

	protected function headStart()
	{

		echo "<head>\n";
		echo "<title>".SITE_TITLE."</title>\n";
		$this->dumpMetaTags();

		echo "\n";
		echo "\n";
		$this->dumpCSS();
		echo "\n";
		$this->dumpJS();
		echo "\n";
	}
	protected function dumpCSS()
	{
		echo "\n";
	}
	protected function dumpJS()
	{

		echo "\n";

	}

	protected function headEnd() {
		echo "</head>\n";
		echo "\n";
	}

	protected function bodyStart()
	{


		echo "<body class='".$this->getPageClass()."' >\n";
		echo "\n";

	}



	protected function bodyEnd()
	{
		echo "\n";


		echo "</body>\n";
		echo "\n";
	}

	protected function htmlEnd(){
		echo "\n";
		echo "</html>";
		echo "\n";
	}




	public abstract function beginPage();
	public abstract function finishPage();


	public function setPageClass($cls)
	{
            $this->page_class = $cls;
	}
	public function getPageClass()
	{
            if ($this->page_class) return $this->page_class;
            
            $sname = str_replace(".php","",basename($_SERVER["SCRIPT_NAME"]));
            $pname = basename(dirname($_SERVER["SCRIPT_NAME"]));
            return get_class($this)." ".$pname." ".$sname;
	}

}

?>
