<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");
include_once("lib/panels/ConfirmMessageDialog.php");

$page = new DemoPage();

function dumpCSS()
{
  echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/InputRenderer.css' type='text/css' >";
  echo "\n";
}
$dialog = new ConfirmMessageDialog();


$page->beginPage();

echo "<div class='InputField TextField'>";
echo "<input type=text  value='Sample Text Message'>";
echo "</div>";

echo "<BR>";

StyledButton::DefaultButton()->drawButton("Show Message", "javascript:onShowMessage(this)");

echo "<HR>";

echo "<div class='InputField TextArea'>";
echo "<textarea rows=5 cols=80>";
echo "Text to show as confirm message";
echo "</textarea>";
echo "</div>";

echo "<BR>";
StyledButton::DefaultButton()->drawButton("Show Confirm", "javascript:onShowConfirm(this)");

?>
<script type='text/javascript'>
function onShowMessage()
{
  var message_text = $(".TextField input[type='text']").val();
  showAlert(message_text);
  
}

function onShowConfirm()
{
  var message_text = $(".TextArea textarea").val();
  showConfirm(message_text , onSampleConfirmOK, onSampleConfirmCancel);
}

function onSampleConfirmCancel(confirm_dialog) 
{

  showAlert("You pressed 'Cancel'", function(alert_dialog) {
    alert("cancel");
    alert_dialog.modal_pane.pane().remove();
    confirm_dialog.modal_pane.pane().remove();
  });
}

function onSampleConfirmOK(elm)
{
  showAlert("You pressed 'OK'");
}
</script>
<?php

$page->finishPage();


?>