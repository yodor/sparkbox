<?php
include_once("objects/SparkEvent.php");

class BeanFormEditorEvent extends SparkEvent {
    const string FORM_BEAN_LOADED = "FORM_BEAN_LOADED";
    const string FORM_PROCESSED = "FORM_PROCESSED";
    const string FORM_VALUES_TRANSACTED = "FORM_VALUES_TRANSACTED";
    const string FORM_BEAN_TRANSACED = "FORM_BEAN_TRANSACED";
}
?>
