<?php

trait CanSetRawColumns {

    public function setColumn(SQLColumn $column) {
        $this->fieldset->setColumn($column);
    }
}