<?php
exit;
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/utils/ValueInterleave.php");


$menu=array();

$content = new AdminPage("Raw SQL");

$content->beginPage($menu);


$db = DBDriver::get();

$sql="";
if (isset($_POST["sql"]))
{
	$sql=$db->escapeString($_POST["sql"]);
}

echo "<form method=post>";
echo "SQL: <br>";
echo "<textarea name=sql rows=15 style='width:100%;    -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;'>$sql</textarea><br>";

StyledButton::DefaultButton()->drawSubmit("Query");

echo "<hr>";

echo "</form>";


if (strlen($sql)>0)
{
	
	$db->transaction();
	$res = $db->query($sql);
	if ($res===TRUE)
	{
		echo "Success.";
		$db->commit();
	}
	else if ($res===FALSE)
	{		
		$db->rollback();
		echo "Error:<Br>".$db->getError();
	}
	else if ($res) {
		
		echo "<table id=list cellpadding=0 cellspacing=0>";
		
		$numFields = $db->numFields($res);
		for ($a=0;$a<$numFields;$a++)
		{
			echo "<th>".$db->fieldName($res,$a)."</th>";
		}

		$v = new ValueInterleave("class=even","class=odd");
		

		while ($row=$db->fetchRow($res))
		{
			$cls = $v->value();
			echo "<tr>";
			foreach ($row as $key=>$val)
			{
				echo "<td $cls>".dumpVal($val)."</td>";
			}
			echo "</tr>";
			$v->advance();
		}
		echo "</table>";
		
		$db->commit();
	}
}


$content->finishPage();


?>