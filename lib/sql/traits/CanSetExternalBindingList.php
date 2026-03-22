<?php

trait CanSetExternalBindingList {

    /**
     * Bind values and return unique binding key for each value - comma separated.
     *
     * Bind each value from the $list array to the internal statement bindings and return string suitable for using
     * inside IN and NOT IN SQL constructs.
     *
     * For each element a binding key is constructed like this : "L_".Spark::Hash($value)."_".$idx
     *
     * Return all binding keys comma separated.
     *
     * * \$keep_list = $delete->bindList([1,2,3,4,5]);
     * * \$delete->where()->addExpression("key NOT IN ($keep_list)");
     *
     * @param array<string|float|int|bool|null> $list
     * @return string
     * @throws Exception
     */
    public function bindList(array $list) : string
    {
        if (count($list) < 1) throw new Exception("list is empty");

        $idx = 0;
        $keysList = [];
        foreach ($list as $value) {
            if (!SQLStatement::IsBindingValueSafe($value)) throw new Exception("List element with incorrect binding value");
            $bindingKey = SQLStatement::FormatBindingKey("L_".Spark::Hash($value)."_".$idx);
            $keysList[] = $bindingKey;
            $this->externalBindings[$bindingKey] = $value;
            $idx++;
        }

        return implode(",", $keysList);
    }
}