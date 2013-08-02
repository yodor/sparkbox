<?php

abstract class SitePage
{
	private static $instance = NULL;
	
	public static function getInstance()
	{
	  return self::$instance;
	}

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

		echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
		
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


	public function getPageClass()
	{
	  $sname = str_replace(".php","",basename($_SERVER["SCRIPT_NAME"]));
	  $pname = basename(dirname($_SERVER["SCRIPT_NAME"]));
	  return get_class($this)." ".$pname." ".$sname;
	}

}

?>