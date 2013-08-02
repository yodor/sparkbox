<?php
include_once("lib/renderers/InputRenderer.php");

class StyledSelectInputRenderer extends InputRenderer {

  public $list_key=false;
  public $list_label=false;

  private $sel_label = "";
  private $sel_key = "";

  private $rootParent = 0;

  public function renderInput($attr="",$style_add="")
  {
	$field_value = $this->field->getValue();
	$field_name = $this->field->getName();

	
	

	if (!$this->data_bean instanceof IDataBean) {
		throw new Exception("No suitable data bean input source defined");
	}


$data_fields = $this->data_bean->getFields();
// var_dump($data_fields);
// echo "<hr>";
// echo $this->list_key;
// echo "<hr>";
// echo $this->list_label;
// echo "<hr>";
if (!in_array($this->list_key,$data_fields)) throw new Exception("List Key {$this->list_key} not found in datasource fields");
if (!in_array($this->list_label,$data_fields)) throw new Exception("List Label {$this->list_label} not found in datasource fields");

	$all_attrs = $this->prepareAttributes($attr, $style_add);

	

	if (strlen($this->data_bean->na_str)>0) 
	{
	  $val = $this->data_bean->na_str;
	}



	echo "<div id=custom_select_$field_name class=form_input_custom_select $all_attrs style='cursor:pointer;' >";


	  $b = StyledButton::DefaultButton();
	  $b->image="<img style='margin-top:5px;' src='".SITE_ROOT."images/arrow_down.png"."'>";
	  $b->drawButton("","javascript:showDropDown(\"$field_name\");", "float:right;");

	  echo "<div id=custom_select_options_holder_$field_name class=form_input_custom_select_options_holder style='display:none;'>";

	  $this->sel_label = $this->data_bean->na_str;
	  $this->sel_key = $this->data_bean->na_val;

	  if (strlen($this->data_bean->na_str)>0) 
	  {
	      $this->renderOptionRow($this->data_bean->na_val,$this->data_bean->na_str);
	  }


	  if ($this->data_bean instanceof NestedSetBean) {
	      $this->listChildsSelect($this->rootParent, 0, $field_value, "");
	  }
	  else {
	      $this->data_bean->startIterator($this->filter);

	      while ($this->data_bean->fetchNext($row))
	      {
		
		$key = $row[$this->list_key];
		$label = $row[$this->list_label];

		if (strcmp($field_value,$key)==0) {
		  $this->sel_label = $label;
		  $this->sel_key = $key;
		}

		$this->renderOptionRow($key, $label);
	      }  
		    
	  }
	  echo "</div>";


	  echo "<div  id=custom_select_value_$field_name class=form_input_custom_select_label  >".$this->sel_label."</div>";

	  echo "<input id=custom_select_input_$field_name type=hidden name='$field_name' value='{$this->sel_key}'>\n";



	  

	echo "</div>";

  }

  private function renderOptionRow($key, $label)
  {
      $field_name = $this->field->getName();

      echo "<a class=form_input_custom_select_option key={$field_name}_{$key} onClick='javascript:dropOptionClicked(this, \"$field_name\");' >$label</a>\n";
  }

    private function listChildsSelect($parentID, $level, $def, $filter)
    {
	    $clevel = $level;
	    $clevel++;


	    //$total = $this->data_bean->startIterator("WHERE parentID=$parentID ORDER BY {$this->list_label} ASC");

	    $table_name = $this->data_bean->getTableName();
	    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM $table_name WHERE parentID=$parentID ORDER BY {$this->list_label} ";
	    $total = -1;
	    $itr = $this->data_bean->createIterator($sql, $total);
// 
// 	    
	    if ($total<1)return;

	    $margin = (10 * ($level)) + 1 ;
	    while ($this->data_bean->fetchNext($row, $itr)){

		    $sel = "";
		    $key = $row[$this->list_key];
		    $label = $row[$this->list_label];
		    $label = "<span style='margin-left:{$margin}px'>".tr($label)."</span>";

		    if (strcmp($def,$key)==0) {
		      $this->sel_label = $label;
		      $this->sel_key = $key;
		    }

		    $id = (int)$row[$this->data_bean->getPrKey()];
		    

		    if (strlen($filter)>0){
			    if ((int)$filter == (int)$id) {
				$this->renderOptionRow($key, $label);
				    
			    }
		    } 
		    else {
		

			$this->renderOptionRow($key, $label);
		    }

		
		    $this->listChildsSelect($id, $clevel, $def, $filter);
	    }
    }
}
?>