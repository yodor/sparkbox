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
        $this->select->order_by = " position ASC ";
    }

    public function delete(int $id, ?DBDriver $db = NULL) : int
    {

        $code = function (DBDriver $db) use ($id) {
            $pos = (int)$this->getValue($id, "position");

            Debug::ErrorLog("Deleting item with position: $pos");

            parent::delete($id, $db);

            $update = new SQLUpdate($this->select);
            $update->set("position", "position-1");
            $update->where()->addExpression("position>$pos");

            $db->query($update->getSQL());

        };

        return $this->handleTransaction($code, $db);

    }

    public function insert(array $row, ?DBDriver $db = NULL): int
    {
        if (!isset($row["position"])) {
            Debug::ErrorLog("Position field is missing - using max(position) + 1");
            $pos = $this->getMaxPosition();
            $row["position"] = ($pos + 1);
        }
        return parent::insert($row, $db);
    }

    public function reorderFixed(int $id, int $new_pos, ?DBDriver $db = NULL) : void
    {

        $pos = (int)$this->getValue($id, "position");

        $maxp = $this->getMaxPosition();

        Debug::ErrorLog("ID: $id position - current: $pos max: $maxp new: $new_pos");

        //if ($new_pos > $maxp) $new_pos = $maxp;
        if ($new_pos < 1) $new_pos = 1;

        Debug::ErrorLog("Using pos: $new_pos");

        $code = function (DBDriver $db) use ($id, $pos, $new_pos) {

            $update = new SQLUpdate($this->select);
            $update->set("position", "position - 1");
            $update->where()->addExpression("position>$pos");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", "position + 1");
            $update->where()->addExpression("position>=$new_pos");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", $new_pos);
            $update->where()->add($this->prkey, $id);
            $db->query($update->getSQL());
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderTop(int $id, ?DBDriver $db = NULL) : void
    {

        $pos = (int)$this->getValue($id, "position");

        if ($pos == 1) {
            throw new Exception("Already at top position");
        }

        Debug::ErrorLog("ID: $id position - current: $pos new: 1");

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", "position + 1");
            $update->where()->addExpression("position<$pos");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", " 1 ");
            $update->where()->add($this->prkey, $id);
            $db->query($update->getSQL());
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderBottom(int $id, ?DBDriver $db = NULL) : void
    {

        $pos = (int)$this->getValue($id, "position");

        $max_pos = $this->getMaxPosition();

        if ($pos == $max_pos) {
            throw new Exception("Already at bottom position");
        }

        Debug::ErrorLog("ID: $id position - current: $pos new: $max_pos");

        $code = function (DBDriver $db) use ($id, $pos, $max_pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", "position-1");
            $update->where()->addExpression("position>$pos");
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", $max_pos);
            $update->where()->add($this->prkey , $id);
            $db->query($update->getSQL());

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

        Debug::ErrorLog("ID: $id position - current: $pos new: " . ($pos - 1));

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", " -1 ");
            $update->where()->add($this->prkey, $id);
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", " position + 1 ");
            $update->where()->add("position", ($pos - 1));
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", $pos - 1);
            $update->where()->add($this->prkey, $id);
            $db->query($update->getSQL());
        };

        $this->handleTransaction($code, $db);

    }

    public function reorderDown(int $id, ?DBDriver $db = NULL) : void
    {
        $pos = (int)$this->getValue($id, "position");

        $max_pos = $this->getMaxPosition();
        if ($pos + 1 > $max_pos) {
            throw new Exception("Already at bottom position");
        }

        Debug::ErrorLog("ID: $id position - current: $pos new: " . ($pos + 1));

        $code = function (DBDriver $db) use ($id, $pos) {
            $update = new SQLUpdate($this->select);
            $update->set("position", " -1 ");
            $update->where()->add($this->prkey, $id);
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", " position - 1 ");
            $update->where()->add("position", ($pos + 1));
            $db->query($update->getSQL());

            $update = new SQLUpdate($this->select);
            $update->set("position", $pos + 1);
            $update->where()->add($this->prkey, $id);
            $db->query($update->getSQL());

        };

        $this->handleTransaction($code, $db);

    }

    public function getMaxPosition(): int
    {
        $db = $this->db;
        $sql = "";

        $selectMax = clone $this->select;
        $selectMax->fields()->setExpression(" MAX(position) ", "max_position");

        $result = $db->query($selectMax->getSQL());

        if (!($result instanceof DBResult)) throw new Exception ("Error getting max position");

        $row = $result->fetch();

        return (int)$row["max_position"];

    }

    public function rebuildReferentialOrdering(string $ref_key, string $ref_val) : void
    {

        $query = $this->query("position", "$ref_key", $this->prkey);

        $query->select->where()->add($ref_key, $ref_val);
        $num = $query->exec();

        $positions = array();
        $position = 0;
        while ($result = $query->nextResult()) {
            $id = $result->get($this->prkey);
            $position++;
            $positions[$id] = $position;
        }

        Debug::ErrorLog("Using positions: ", $positions);

        try {
            $this->db->transaction();

            foreach ($positions as $id=>$pos)
            {

                $update = new SQLUpdate($query->select);
                $update->set("position", $pos);
                $update->where()->add($this->prkey, $id);

                $this->db->query($update->getSQL());

            }
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

    }

}