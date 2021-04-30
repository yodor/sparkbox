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

        $this->price = $price;
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
?>