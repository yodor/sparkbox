<?php
trait CanSetColumnAliasExpression {
    /**
     * Add direct sql expression to the column collection using '$alias_name'
     *
     * Existing column named '$alias_name' would be replaced with the newly created column.
     *
     * No automatic binding is created.
     *
     * * Direct expression
     *
     * \$select->fields()->setAliasExpression("count(pvl.logID)", "cnt") -> count(pvl.logID) AS cnt
     *
     * * Expression with manual binding parameter
     *
     * \$select->fields()->setAliasExpression("COALESCE(tp.langID, :langID)", "langID");
     * \$select->bind(":langID", \$langID);
     *  -> after binding COALESCE(tp.langID, \$langID) AS langID
     *
     * @param string $expression SQL select expression string
     * @param string $alias_name Alias name
     * @throws Exception
     */
    public function setAliasExpression(string $expression, string $alias_name) : void
    {
        // Using trim to ensure we don't accept strings with only whitespace
        $expression = trim($expression);
        $alias_name = trim($alias_name);

        if ($expression === "" || $alias_name === "") {
            throw new Exception("SQL expression and alias_name must be non-empty strings.");
        }

        //no binding
        $column = new SQLColumn($alias_name);
        $column->setExpression($expression, $alias_name);
        $this->fieldset->setColumn($column);
    }
}