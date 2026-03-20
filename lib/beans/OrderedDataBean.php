<?php
include_once("beans/DBTableBean.php");
include_once("sql/SQLUpdate.php");

abstract class OrderedDataBean extends DBTableBean
{

    public function __construct(string $table_name, ?DBDriver $dbdriver = NULL)
    {
        parent::__construct($table_name, $dbdriver);

        if (!$this->haveColumn("position")) {
            throw new Exception("Incorrect table fields for OrderedDataBean");
        }
        $this->select->order("position", OrderDirection::ASC);
    }

    public function delete(int $id, ?DBDriver $db = NULL) : int
    {

        $code = function (DBDriver $db) use ($id) {

            $pos = (int)$this->getValue($id, "position");

            Debug::ErrorLog("Deleting item with position: $pos");

            parent::delete($id, $db);

            $update = new SQLUpdate($this->select);
            $update->setExpression("position", "position - 1");
            $update->where()->addExpression("position > :pos");
            $update->bind(":pos", $pos);

            $db->query($update)->free();

        };

        return $this->handleTransaction($code, $db);

    }

    public function insert(array $row, ?DBDriver $db = NULL): int
    {
        if (!isset($row["position"])) {
            Debug::ErrorLog("Position field is missing - using max(position) + 1");
            $pos = $this->getMaxPosition($db);
            $row["position"] = ($pos + 1);
        }
        return parent::insert($row, $db);
    }

    public function reorderFixed(int $id, int $new_pos, ?DBDriver $db = NULL) : void
    {

        $pos = (int)$this->getValue($id, "position");

        $maxp = $this->getMaxPosition($db);

        if ($new_pos < 1) $new_pos = 1;

        Debug::ErrorLog("ID[$id] position $pos -> $new_pos | MAX: $maxp");

        $code = function (DBDriver $db) use ($id, $pos, $new_pos) {

            $update = new SQLUpdate($this->select);
            $update->setExpression("position", "position - 1");
            $update->where()->addExpression("position > :pos");
            $update->bind(":pos", $pos);
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("position", "position + 1");
            $update->where()->addExpression("position >= :new_pos");
            $update->bind(":new_pos", $new_pos);
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->set("position", $new_pos);
            $update->where()->add($this->prkey, $id);
            $db->query($update)->free();
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderTop(int $id, ?DBDriver $db = NULL) : void
    {

        $pos = (int)$this->getValue($id, "position");

        if ($pos == 1) {
            throw new Exception("Already at top position");
        }

        Debug::ErrorLog("ID[$id] reposition: $pos -> 1");

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->setExpression("position", "position + 1");
            $update->where()->addExpression("position < :pos");
            $update->bind(":pos", $pos);
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->set("position", 1);
            $update->where()->add($this->prkey, $id);
            $db->query($update)->free();
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderBottom(int $id, ?DBDriver $db = NULL) : void
    {

        $pos = (int)$this->getValue($id, "position");
        $max_pos = $this->getMaxPosition($db);

        if ($pos == $max_pos) {
            throw new Exception("Already at bottom position");
        }

        Debug::ErrorLog("ID[$id] reposition: $pos -> $max_pos");

        $code = function (DBDriver $db) use ($id, $pos, $max_pos) {
            $update = new SQLUpdate($this->select);
            $update->setExpression("position", "position - 1");
            $update->where()->addExpression("position > :pos");
            $update->bind(":pos", $pos);
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->set("position", $max_pos);
            $update->where()->add($this->prkey , $id);
            $db->query($update)->free();

        };

        $this->handleTransaction($code, $db);

    }

    public function reorderUp(int $id, ?DBDriver $db = NULL) : void
    {

        $pos = (int)$this->getValue($id, "position");

        if ($pos - 1 < 1) {
            //already at top
            throw new Exception("Already at top position");
        }

        Debug::ErrorLog("ID[$id] - reposition $pos -> " . ($pos - 1));

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", -1);
            $update->where()->add($this->prkey, $id);
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("position", " position + 1 ");
            $update->where()->add("position", ($pos - 1));
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->set("position", ($pos - 1));
            $update->where()->add($this->prkey, $id);
            $db->query($update)->free();
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderDown(int $id, ?DBDriver $db = NULL) : void
    {
        $pos = (int)$this->getValue($id, "position");

        $max_pos = $this->getMaxPosition($db);
        if ($pos + 1 > $max_pos) {
            throw new Exception("Already at bottom position");
        }

        Debug::ErrorLog("ID[$id] reposition $pos -> " . ($pos + 1));

        $code = function (DBDriver $db) use ($id, $pos) {

            $update = new SQLUpdate($this->select);
            $update->set("position", -1);
            $update->where()->add($this->prkey, $id);
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->setExpression("position", "position - 1");
            $update->where()->add("position", ($pos + 1));
            $db->query($update)->free();

            $update = new SQLUpdate($this->select);
            $update->set("position", ($pos + 1));
            $update->where()->add($this->prkey, $id);
            $db->query($update)->free();

        };

        $this->handleTransaction($code, $db);

    }

    public function getMaxPosition(?DBDriver $db): int
    {
        $db = $db ?? $this->db;

        $selectMax = clone $this->select;
        $selectMax->setAliasExpression(" MAX(position) ", "max_position");

        $query = new SelectQuery($selectMax);
        $query->exec();
        if ($result = $query->nextResult()) {
            $query->free();
            return (int)$result->get("max_position");
        }
        return -1;
    }

}