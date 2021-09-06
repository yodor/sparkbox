<?php

class CartItem
{
    protected $line_total = 0.0;
    protected $price = 0.0;
    protected $qty = 0;
    protected $id = -1;

    public function __construct(int $id, float $sell_price, int $qty=1)
    {
        $this->id = $id;
        $this->qty = $qty;
        $this->price = $sell_price;
        $this->calculate();
    }

    public function getID(): int
    {
        return $this->id;
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

    public function increment(int $number=1)
    {
        $this->qty = $this->qty + $number;
        $this->calculate();
    }

    public function decrement(int $number=1)
    {
        if ( ($this->qty - $number) > -1 ) {
            $this->qty-=$number;
            $this->calculate();
        }
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getLineTotal(): float
    {
        return $this->line_total;
    }

    /**
     * Calculate line total
     */
    protected function calculate()
    {
        $this->line_total = $this->qty * $this->price;
    }

}
?>