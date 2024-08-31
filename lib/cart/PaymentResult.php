<?php

class PaymentResult
{
    private int $orderID;
    private string $reference;
    private int $transactionTime;
    private string $gateway;

    public function __construct(int $orderID, string $reference, int $transactionTime, string $gateway)
    {
        $this->orderID = $orderID;
        $this->reference = $reference;
        $this->transactionTime = $transactionTime;
        $this->gateway = $gateway;
    }

    public function getReference() : string
    {
        return $this->reference;
    }

    public function getTransactionTime() : int
    {
        return $this->transactionTime;
    }

    public function getOrderID() : int
    {
        return $this->orderID;
    }

    public function getGateway() : string
    {
        return $this->gateway;
    }
}

?>
