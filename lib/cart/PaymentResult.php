<?php

class PaymentResult
{
    private $orderID = -1;
    private $reference = "";
    private $transactionTime = -1;
    private $gateway = "";

    public function __construct($orderID, $reference, $transactionTime, $gateway)
    {
        $this->orderID = $orderID;
        $this->reference = $reference;
        $this->transactionTime = $transactionTime;
        $this->gateway = $gateway;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function getTransactionTime()
    {
        return $this->transactionTime;
    }

    public function getOrderID()
    {
        return $this->orderID;
    }

    public function getGateway()
    {
        return $this->gateway;
    }
}

?>
