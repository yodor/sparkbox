<?php
include_once("utils/URLBuilder.php");
include_once("utils/DataParameter.php");
include_once("components/renderers/items/DataIteratorItem.php");

class Action extends DataIteratorItem
{

    /**
     * Generic class for handling action and parametrization of its href
     */

    protected $tagName = "A";

    protected $data_parameters;

    protected $check_code = NULL;

    /**
     * @var URLBuilder
     */
    protected $urlbuilder;

    //set action attribute equal to the contents
    public $action_from_label = TRUE;

    /**
     * Action constructor.
     *
     * @param string $contents
     * @param string $href
     * @param array $parameters
     * @param string $check_code this will be eval'ed before rendering
     */
    public function __construct(string $contents = "", string $href = "", array $parameters = array(), Closure $check_code = NULL)
    {
        parent::__construct();

        $this->urlbuilder = new URLBuilder();
        $this->urlbuilder->setHref($href);

        $this->contents = $contents;

        $this->data_parameters = array();

        foreach ($parameters as $idx => $parameter) {
            //data row parameter
            if ($parameter instanceof DataParameter) {
                $this->data_parameters[$parameter->name()] = $parameter;
            }
            //static value parameter
            else if ($parameter instanceof URLParameter) {
                $this->urlbuilder->addParameter($parameter);
            }
            else {
                debug("Passing non URLParameter in the parameters array");
            }
        }

        $this->check_code = $check_code;

        $this->translation_enabled = TRUE;

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function getURL(): URLBuilder
    {
        return $this->urlbuilder;
    }

    public function getDataParameter(string $name): DataParameter
    {
        return $this->data_parameters[$name];
    }

    public function addDataParameter(DataParameter $param)
    {
        $this->data_parameters[$param->name()] = $param;
    }

    public function setData(array $row)
    {

        if ($this->check_code) {
            debug("Action has check_code anonymous function set");
            $check_code = $this->check_code;
            if (!$check_code($this, $row)) {
                debug("check_code disabled rendering of this action");
                $this->render_enabled = FALSE;
                return;
            }
        }

        $script_name = $this->urlbuilder->getScriptName();

        if (stripos($script_name, "javascript:") !== FALSE) {

            $names = array_keys($this->data_parameters);
            foreach ($names as $idx => $name) {
                $param = $this->getDataParameter($name);

                $param->setData($row);
                //replace "%field%" with $data[$field]
                $script_name = str_replace("%" . $param->field() . "%", $param->value(), $script_name);

            }

            $this->urlbuilder->setScriptName($script_name);
            return;
        }

        $names = array_keys($this->data_parameters);
        foreach ($names as $idx => $name) {
            $param = $this->getDataParameter($name);
            $param->setData($row);
            $this->urlbuilder->addParameter($param);
        }

        //        $ret = $script_name . queryString($params);
        //        if (is_array($row)) {
        //            foreach ($row as $param_name => $value) {
        //                $ret = str_replace("%" . $param_name . "%", $value, $ret);
        //            }
        //        }
        //
        //        return $ret;

    }

    protected function processAttributes()
    {
        parent::processAttributes();

        if ($this->action_from_label) {
            if (!$this->getAttribute("action")) {
                $this->setAttribute("action", $this->contents);
            }
        }

        $url = $this->urlbuilder->url();
        if ($url) {
            $this->setAttribute("href", $url);
        }

    }

    public function getCheckCode()
    {
        return $this->check_code;
    }

    public function setCheckCode($check_code)
    {
        $this->check_code = $check_code;
    }

    public static function RenderActions(array $actions, bool $separator = FALSE)
    {
        foreach ($actions as $idx => $item) {
            if ($item instanceof MenuItem) {
                $action = new Action($item->getTitle(), $item->getHref(), array());
                $action->render();
            }
            else if ($item instanceof Action) {
                $item->render();
            }

            if ($separator) {
                echo "<span class='separator'> | </span>";
            }
        }
    }

}

class PipeSeparator extends Action
{
    public function __construct()
    {
        parent::__construct();
        $this->contents = " | ";
        $this->tagName = "SPAN";
        $this->translation_enabled = FALSE;
    }
}

class RowSeparator extends Action
{
    public function __construct()
    {
        parent::__construct();
        $this->tagName = "SPAN";
        $this->translation_enabled = FALSE;
    }
}

class EmptyAction extends Action
{
    public function __construct()
    {
        parent::__construct("", "");
        $this->setAttribute("action", "Empty");
        $this->translation_enabled = FALSE;
    }
}

?>
