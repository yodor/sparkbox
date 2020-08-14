<?php
include_once("beans/ConfigBean.php");
include_once("utils/CartItem.php");

class Cart
{
    protected $items;

    protected $delivery_price;

    protected static $instance = NULL;

    static public function Instance(): Cart
    {
        if (Session::Contains("cart")) {
            self::$instance = unserialize(Session::Get("cart"));
        }
        if (self::$instance instanceof Cart) {
            return self::$instance;
        }
        self::$instance = new Cart();
        self::$instance->store();

        return self::$instance;
    }

    private function __construct()
    {
        $this->items = array();

        $this->delivery_price = 0;

        $config = ConfigBean::Factory();
        $config->setSection("global");

        $this->delivery_price = $config->get("delivery_price", 0.0);
    }

    public function store()
    {
        Session::Set("cart", serialize($this));
    }

    public function set(CartItem $item)
    {
        $itemID = $item->getID();
        if ($item->getQuantity() > 0) {
            $this->items[$itemID] = $item;
        }
        else {
            $this->remove($itemID);
        }
        $this->store();
    }

    public function get(int $itemID): ?CartItem
    {
        if (!isset($this->items[$itemID])) return NULL;
        return $this->items[$itemID];
    }

    protected function remove(int $itemID)
    {
        if (isset($this->items[$itemID])) {
            unset($this->items[$itemID]);
        }
    }

    public function items(): array
    {
        return $this->items;
    }

    public function getItemsCount(): int
    {
        $num_items = 0;
        foreach ($this->items as $itemID => $item) {
            if (!($item instanceof CartItem)) continue;
            $num_items += $item->getQuantity();
        }
        return $num_items;
    }

    public function clear()
    {
        $this->items = array();
        $this->store();
    }

    public function setDeliveryPrice(float $price)
    {
        $this->delivery_price = $price;
        $this->store();
    }

    public function getDeliveryPrice(): float
    {
        return $this->delivery_price;
    }

    public function getItemsTotal(): float
    {
        $items_total = 0.0;
        foreach ($this->items as $itemID => $item) {
            if (!($item instanceof CartItem)) continue;
            $items_total += $item->getLineTotal();
        }
        return $items_total;
    }

    public function getTotal(): float
    {
        return $this->delivery_price + $this->getItemsTotal();
    }
}

?>