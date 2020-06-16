<?php

class CartItem
{
    protected $line_total;
    protected $price;
    protected $qty;
    protected $id;

    public function __construct()
    {
        $this->id = -1;
        $this->qty = 0;
        $this->price = 0.0;
        $this->line_total = 0.0;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function setID(int $id)
    {
        $this->id = $id;
    }

    public function getQuantity(): int
    {
        return $this->qty;
    }

    public function setQuantity(int $qty)
    {
        $this->qty = $qty;
        $this->calculate();
    }

    public function increment()
    {
        $this->qty = $this->qty + 1;
        $this->calculate();
    }
    public function decrement()
    {
        $this->qty = $this->qty - 1;
        $this->calculate();
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price)
    {
        $converter = CurrencyConverter::Instance();
        $this->price = $converter->getValue($price);
        $this->calculate();
    }
    public function getLineTotal(): float
    {
        return $this->line_total;
    }
    protected function calculate()
    {
        $this->line_total = $this->qty * $this->price;
    }

}

class Cart
{
    protected $items;

    protected $delivery_price;

    protected static $instance = NULL;

    static public function Instance(): Cart
    {
        if (isset($_SESSION["cart"])) {
            self::$instance = unserialize($_SESSION["cart"]);
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
        $_SESSION["cart"] = serialize($this);
    }

    public function set(CartItem $item)
    {
        $itemID = $item->getID();
        if ($item->getQuantity()>0) {
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

    public function remove(int $itemID)
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

    }

    public function setDeliveryPrice(float $price)
    {
        $converter = CurrencyConverter::Instance();

        $this->delivery_price = $converter->getValue($price);
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