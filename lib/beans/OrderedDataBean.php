<?php
include_once("beans/DBTableBean.php");
include_once("sql/SQLUpdate.php");

abstract class OrderedDataBean extends DBTableBean
{

    public function __construct(string $table_name, DBDriver $dbdriver = NULL)
    {
        parent::__construct($table_name, $dbdriver);

        if (!$this->haveField("position")) {
            throw new Exception("Incorrect table fields for OrderedDataBean");
        }
    }

    public function delete(int $id, DBDriver $db = NULL)
    {

        $code = function (DBDriver $db) use ($id) {
            $pos = $this->fieldValue($id, "position");

            debug("Deleting item with position: $pos");

            parent::delete($id, $db);

            $update = new SQLUpdate($this->select);
            $update->set("position", "position-1");
            $update->where()->append("position>$pos");

            $res = $db->query($update->getSQL());
            if (!$res) throw new Exception("Delete reposition DBError: " . $db->getError());

        };

        $this->handleTransaction($code, $db);

    }

    public function insert(array &$row, DBDriver $db = NULL): int
    {
        $pos = $this->getMaxPosition();
        $row["position"] = ($pos + 1);
        return parent::insert($row, $db);
    }

    public function reorderFixed(int $id, int $new_pos, DBDriver $db = NULL)
    {

        $pos = $this->fieldValue($id, "position");

        $maxp = (int)$this->getMaxPosition();

        debug("ID: $id position - current: $pos max: $maxp new: $new_pos");

        if ($new_pos > $maxp) $new_pos = $maxp;
        if ($new_pos < 1) $new_pos = 1;

        debug("Using pos: $new_pos");

        $code = function (DBDriver $db) use ($id, $pos, $new_pos) {

            $update = new SQLUpdate($this->select);
            $update->set("position", "position - 1");
            $update->where()->append("position>$pos");
            if (!$db->query($update->getSQL())) throw new Exception("Set position error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set("position", "position + 1");
            $update->where()->append("position>=$new_pos");
            if (!$db->query($update->getSQL())) throw new Exception("Set position error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set("position", $new_pos);
            $update->where()->add($this->prkey, $id);

            if (!$db->query($update->getSQL())) throw new Exception("Set position error: " . $db->getError());
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderTop(int $id, DBDriver $db = NULL)
    {

        $pos = (int)$this->fieldValue($id, "position");

        if ($pos == 1) {
            throw new Exception("Already at top position");
        }

        debug("ID: $id position - current: $pos new: 1");

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", "position + 1");
            $update->where()->append("position<$pos");

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Top Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set("position", " 1 ");
            $update->where()->add($this->prkey, $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Top Error: " . $db->getError());
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderBottom(int $id, DBDriver $db = NULL)
    {

        $pos = (int)$this->fieldValue($id, "position");

        $max_pos = (int)$this->getMaxPosition();

        if ($pos == $max_pos) {
            throw new Exception("Already at bottom position");
        }

        debug("ID: $id position - current: $pos new: $max_pos");

        $code = function (DBDriver $db) use ($id, $pos, $max_pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", "position-1");
            $update->where()->append("position>$pos");

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Bottom(1) Error: " . $db->getError() . "<HR>" . $update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", $max_pos);
            $update->where()->add($this->prkey , $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Bottom(2) Error: " . $db->getError() . "<HR>" . $update->getSQL());
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderUp(int $id, DBDriver $db = NULL)
    {

        $pos = (int)$this->fieldValue($id, "position");

        if ($pos - 1 < 1) {
            //already at top
            throw new Exception("Already at top position");
        }

        debug("ID: $id position - current: $pos new: " . ($pos - 1));

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", " -1 ");
            $update->where()->add($this->prkey, $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Up Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set("position", " position + 1 ");
            $update->where()->add("position", ($pos - 1));

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Up Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set("position", $pos - 1);
            $update->where()->add($this->prkey, $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Up Error: " . $db->getError());
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderDown(int $id, DBDriver $db = NULL)
    {
        $pos = (int)$this->fieldValue($id, "position");

        $max_pos = (int)$this->getMaxPosition();
        if ($pos + 1 > $max_pos) {
            throw new Exception("Already at bottom position");
        }

        debug("ID: $id position - current: $pos new: " . ($pos + 1));

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", " -1 ");
            $update->where()->add($this->prkey, $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Down(1) Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set("position", " position - 1 ");
            $update->where()->add("position", ($pos + 1));

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Down(2) Error: " . $db->getError());

            $update = new SQLUpdate($this->select);
            $update->set("position", $pos + 1);
            $update->where()->add($this->prkey, $id);

            if (!$db->query($update->getSQL())) throw new Exception("Reorder Down(3) Error: " . $db->getError());
        };

        $this->handleTransaction($code, $db);

    }

    public function getMaxPosition(): int
    {
        $db = $this->db;
        $sql = "";

        $selectMax = clone $this->select;
        $selectMax->fields()->setExpression(" MAX(position) ", "max_position");

        $res = $db->query($selectMax->getSQL());

        if (!$res) throw new Exception ("Error getting max position");

        $row = $db->fetch($res);

        return (int)$row["max_position"];

    }
}

?>
