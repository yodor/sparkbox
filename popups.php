<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");
include_once("lib/panels/ConfirmMessageDialog.php");

$page = new DemoPage();

$dialog = new ConfirmMessageDialog();

$field1 = DataInputFactory::CREATE(DataInputFactory::TEXTFIELD, "message1", "Message", false);
$field1->setValue("Sample message text");

$field2 = DataInputFactory::CREATE(DataInputFactory::TEXTAREA, "message2", "Confirmation Message", false);
$field2->setValue("Sample confirmation message text");


$page->startRender();

$field1->getRenderer()->renderField($field1);
echo "<BR>";
StyledButton::DefaultButton()->renderButton("Show Message", "javascript:onShowMessage(this)");

echo "<HR>";

$field2->getRenderer()->renderField($field2);
echo "<BR>";
StyledButton::DefaultButton()->renderButton("Show Confirm", "javascript:onShowConfirm(this)");
?>
    <script type='text/javascript'>
        function onShowMessage() {
            var message_text = $(".TextField input[type='text']").val();
            showAlert(message_text);

        }

        function onShowConfirm() {
            var message_text = $(".TextArea textarea").val();
            showConfirm(message_text, onSampleConfirmOK, onSampleConfirmCancel);
        }

        function onSampleConfirmCancel(confirm_dialog) {

            showAlert("You pressed 'Cancel'", function (alert_dialog) {
                alert("cancel");
                alert_dialog.modal_pane.pane().remove();
                confirm_dialog.modal_pane.pane().remove();
            });
        }

        function onSampleConfirmOK(elm) {
            showAlert("You pressed 'OK'");
        }
    </script>
<?php

$page->finishRender();


?>