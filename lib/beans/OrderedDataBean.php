<?php
include_once("beans/DBTableBean.php");
include_once("utils/SQLUpdate.php");

abstract class OrderedDataBean extends DBTableBean
{

    public function deleteID(int $id, DBDriver $db = NULL)
    {

        $doCommit = FALSE;

        $res = FALSE;

        if (!$db) {
            $db = $this->db;
            $db->transaction();
            $doCommit = TRUE;
        }

        try {
            $pos = $this->fieldValue($id, "position");

            $update = new SQLUpdate($this->select);
            $update->set["position"] = "position-1";
            $update->appendWhere("position>$pos");

            $res = $db->query($update->getSQL());
            if ($res) throw new Exception("DeleteID Error: " . $db->getError());

            //$res = $db->query("DELETE FROM {$this->table} WHERE {$this->prkey}=$id");
            $res = parent::deleteID($id, $db);

            if ($doCommit == TRUE) $db->commit();
        }
        catch (Exception $ex) {
            if ($doCommit == TRUE) $db->rollback();
        }

        return $res;
    }

    public function insert(array &$row, DBDriver $db = NULL): int
    {
        $pos = $this->getMaxPosition();
        $row["position"] = ($pos + 1);
        return parent::insert($row, $db);
    }

    public function reorderFixed(int $id, int $new_pos)
    {

        $pos = $this->fieldValue($id, "position");

        $maxp = (int)$this->getMaxPosition();

        debug("ID: $id position - current: $pos max: $maxp new: $new_pos");

        if ($new_pos > $maxp) $new_pos = $maxp;
        if ($new_pos < 1) $new_pos = 1;

        debug("Using pos: $new_pos");

        $db = $this->db;
        try {

            $db->transaction();

            $update = new SQLUpdate($this->select);
            $update->set["position"] = "position + 1";
            $update->appendWhere("position>=$new_pos");

            if (!$db->query($update->getSQL())) throw new Exception("Set position error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set["position"] = $new_pos;
            $update->appendWhere($this->prkey . "=" . $id);

            if (!$db->query($update->getSQL())) throw new Exception("Set position error: " . $db->getError());

            $db->commit();
        }
        catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }

    }

    public function reorderTop(int $id)
    {

        $pos = (int)$this->fieldValue($id, "position");

        if ($pos == 1) {
            throw new Exception("Already at top position");
        }

        debug("ID: $id position - current: $pos new: 1");

        $db = $this->db;
        try {

            $db->transaction();

            $update = new SQLUpdate($this->select);
            $update->set["position"] = "position + 1";
            $update->appendWhere("position<$pos");

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Top Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set["position"] = " 1 ";
            $update->appendWhere($this->prkey . "=" . $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Top Error: " . $db->getError());

            $db->commit();
        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }

    public function reorderBottom(int $id)
    {

        $pos = (int)$this->fieldValue($id, "position");

        $maxp = (int)$this->getMaxPosition();

        if ($pos == $maxp) {
            throw new Exception("Already at bottom position");
        }

        debug("ID: $id position - current: $pos new: $maxp");

        $db = $this->db;
        try {
            $db->transaction();

            $update = new SQLUpdate($this->select);
            $update->set["position"] = "position-1";
            $update->appendWhere("position>$pos");

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Bottom(1) Error: " . $db->getError() . "<HR>" . $update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set["position"] = $maxp;
            $update->appendWhere($this->prkey . "=" . $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Bottom(2) Error: " . $db->getError() . "<HR>" . $update->getSQL());

            $db->commit();
        }
        catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }

    }

    public function reorderUp(int $id)
    {

        $pos = (int)$this->fieldValue($id, "position");

        if ($pos - 1 < 1) {
            //already at top
            throw new Exception("Already at top position");
        }

        debug("ID: $id position - current: $pos new: ".($pos - 1));

        $db = $this->db;
        try {
            $db->transaction();

            $update = new SQLUpdate($this->select);
            $update->set["position"] = " -1 ";
            $update->appendWhere($this->prkey . "=" . $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Up Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set["position"] = " position + 1 ";
            $update->appendWhere("position = " . ($pos - 1));

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Up Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set["position"] = $pos - 1;
            $update->appendWhere($this->prkey . "=" . $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Up Error: " . $db->getError());

            $db->commit();
        }
        catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    public function reorderDown(int $id)
    {
        $pos = (int)$this->fieldValue($id, "position");

        $maxp = (int)$this->getMaxPosition();
        if ($pos + 1 > $maxp) {
            throw new Exception("Already at bottom position");
        }

        debug("ID: $id position - current: $pos new: ".($pos + 1));


        $db = $this->db;
        try {
            $db->transaction();

            $update = new SQLUpdate($this->select);
            $update->set["position"] = " -1 ";
            $update->appendWhere($this->prkey . "=" . $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Down(1) Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set["position"] = " position - 1 ";
            $update->appendWhere("position = " . ($pos + 1));

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Down(2) Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set["position"] = $pos + 1;
            $update->appendWhere($this->prkey . "=" . $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Down(3) Error: " . $db->getError());

            $db->commit();

        }
        catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }

    }

    public function getMaxPosition(): int
    {
        $db = $this->db;
        $sql = "";

        $selectMax = clone $this->select;
        $selectMax->fields = " max(position) as max_position ";

        $res = $db->query($selectMax->getSQL());

        if (!$res) throw new Exception ("Get Max Position DBError: " . $db->getError());

        $row = $db->fetch($res);

        return (int)$row["max_position"];

    }
}

?>
