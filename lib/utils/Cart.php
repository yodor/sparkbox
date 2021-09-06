<?php
include_once("beans/ConfigBean.php");
include_once("utils/CartItem.php");

class DeliveryOption {

    const NONE = 0;
    const USER_ADDRESS = 1;
    const COURIER_OFFICE = 2;

    protected $id = -1;
    protected $title = "";
    protected $price = 0.0;

    public function __construct(int $id, string $title, float $price)
    {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
    }

    public function getID() : int {
        return $this->id;
    }

    public function getTitle() : string {
        return $this->title;
    }

    public function getPrice() : float  {
        return $this->price;
    }

    public function setPrice(float $price) {
        $this->price = $price;
    }

    /**
     * return all the supported delivery options
     * @return int[]
     */
    public static function Supported() : array
    {
        return array(DeliveryOption::USER_ADDRESS, DeliveryOption::COURIER_OFFICE);
    }
}

class DeliveryCourier {

    const NONE = 0;
    const COURIER_EKONT = 1;
    const COURIER_SPEEDY = 2;

    protected $id = -1;
    protected $title = "";

    protected $options = array();

    protected $selected_option = NULL;


    public function __construct(int $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;

        $option1 = new DeliveryOption(DeliveryOption::USER_ADDRESS,"Доставка до адрес", 0.0);
        $this->options[$option1->getID()] = $option1;

        $option2 = new DeliveryOption(DeliveryOption::COURIER_OFFICE, "Доставка до офис на куриер", 0.0);
        $this->options[$option2->getID()] = $option2;

    }

    public function initialize()
    {
        $config = ConfigBean::Factory();
        $config->setSection("delivery_options");

        foreach ($this->options as $optionID=>$option) {
            if ($option instanceof DeliveryOption) {
                $price = $config->get($this->configPrefix($optionID, "price"), 0.0);
                $option->setPrice($price);
                //checkbox field
                $enabled = $config->get($this->configPrefix($optionID, "enabled"), false);
                if (!$enabled) {
                    unset($this->options[$optionID]);
                }
            }
        }
    }

    public function configPrefix(int $optionID, string $name) : string
    {
        return "courier_{$this->id}_option_{$optionID}_{$name}";
    }

    public function getID() : int {
        return $this->id;
    }

    public function getTitle() : string {
        return $this->title;
    }

    /**
     * return all the supported delivery couriers
     * @return int[]
     */
    public static function Supported() : array
    {
        return array(DeliveryCourier::COURIER_EKONT, DeliveryCourier::COURIER_SPEEDY);
    }

    public function setSelectedOption(int $id)
    {
        if (isset($this->options[$id])) {
            $this->selected_option = $this->options[$id];
        }
        else {
            $this->selected_option = null;
        }
    }

    public function getSelectedOption() : ?DeliveryOption
    {
        return $this->selected_option;
    }

    public function getOptions() : array
    {
        return $this->options;
    }

    public function getOption(int $id) : DeliveryOption
    {
        if (isset($this->options[$id])) {
            return $this->options[$id];
        }
        throw new Exception("Delivery option ID:$id is not initialized");
    }
}


class Delivery {


    protected $couriers = array();

    protected $selected_courier = NULL;

    public function __construct()
    {
        $courier1 = new DeliveryCourier(DeliveryCourier::COURIER_EKONT, "Еконт");
        $this->couriers[$courier1->getID()] = $courier1;

        $courier2 = new DeliveryCourier(DeliveryCourier::COURIER_SPEEDY, "Спиди");
        $this->couriers[$courier2->getID()] = $courier2;
    }

    public function initialize()
    {

        $config = ConfigBean::Factory();
        $config->setSection("delivery_options");

        foreach ($this->couriers as $id=>$courier) {
            if ($courier instanceof DeliveryCourier) {

                $config_name = $this->configPrefix($id, "enabled");
                //radiofield
                $enabled = $config->get($config_name, array(0,0));

                if (!$enabled[0]) {
                    unset($this->couriers[$id]);
                }
                else {
                    $courier->initialize();
                }

            }
        }

    }

    public function configPrefix(int $id, string $name) : string
    {
        return "courier_{$id}_$name";
    }

    public function setSelectedCourier(int $id)
    {
        if (isset($this->couriers[$id])) {
            $this->selected_courier = $this->couriers[$id];
        }
        else {
            $this->selected_courier = null;
        }
    }

    public function getSelectedCourier() : ?DeliveryCourier
    {
        return $this->selected_courier;
    }

    public function getCouriers() : array
    {
        return $this->couriers;
    }

    public function getCourier(int $id) : DeliveryCourier
    {
        if (isset($this->couriers[$id])) {
            return $this->couriers[$id];
        }
        throw new Exception("Delivery courier ID:$id is not initialized");
    }

}

class Cart
{
    protected $items = array();

    protected $delivery = null;

    protected $note = "";

    protected $require_invoice = FALSE;

    protected $data = array();

    protected static $instance = NULL;

    const SESSION_KEY = "spark_cart";

    static public function Instance(): Cart
    {
        if (self::$instance instanceof Cart) {
            return self::$instance;
        }
        if (Session::Contains(Cart::SESSION_KEY)) {
            self::$instance = unserialize(Session::Get(Cart::SESSION_KEY));
        }

        if (! (self::$instance instanceof Cart)) {
            self::$instance = new Cart();
            self::$instance->store();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->items = array();
        $this->delivery = new Delivery();
        $this->delivery->initialize();

        $this->note = "";
        $this->require_invoice = false;
        $this->data = array();
    }

    public function store()
    {
        Session::Set(Cart::SESSION_KEY, serialize($this));
    }

    public function addItem(CartItem $item)
    {
        $itemID = $item->getID();
        if ($this->contains($itemID)) {
            $exist_item = $this->get($itemID);
            $exist_item->increment($item->getQuantity());
        }
        else {
            $this->items[$itemID] = $item;
        }
    }

    public function get(int $itemID): CartItem
    {
        if (isset($this->items[$itemID])) {
            return $this->items[$itemID];
        }
        throw new Exception("'$itemID' not found");
    }

    public function contains(int $itemID) : bool
    {
        return isset($this->items[$itemID]);
    }

    public function remove(int $itemID)
    {
        if (isset($this->items[$itemID])) {
            unset($this->items[$itemID]);
        }
    }

    public function removeItem(CartItem $item)
    {
        $itemID = $item->getID();
        if (isset($this->items[$itemID])) {
            unset($this->items[$itemID]);
        }
    }

    public function items(): array
    {
        return $this->items;
    }

    public function itemsCount(): int
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

    public function getItemsTotal(): float
    {
        $items_total = 0.0;
        foreach ($this->items as $itemID => $item) {
            if (!($item instanceof CartItem)) continue;
            $items_total += $item->getLineTotal();
        }
        return $items_total;
    }

    public function setData(string $name, string $value)
    {
        $this->data[$name] = $value;
    }

    public function getData(string $name) : string
    {
        return $this->data[$name];
    }

    public function haveData(string $name) : bool
    {
        return isset($this->data[$name]);
    }

    public function setNote(string $text)
    {
        $this->note = mb_substr($text, 0, 255);
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function setRequireInvoice(bool $mode)
    {
        $this->require_invoice = $mode;
    }

    public function getRequireInvoice() : bool
    {
        return $this->require_invoice;
    }

    public function getDelivery() : Delivery
    {
        return $this->delivery;
    }

}

?>