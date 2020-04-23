<?php
include_once("lib/cart/PaymentProcessor.php");
include_once("lib/Authenticator.php");

class FreeOrderProcessor extends PaymentProcessor
{

    protected function processOrderImpl($orderID, $order_row)
    {
        $db = DBDriver::Factory();
        $transaction_time = $db->dateTime();
        //
        $reference = Authenticator::RandomToken(16);

        return new PaymentResult($orderID, $reference, $transaction_time, "free_order");
    }

    protected function processTokenImpl($token)
    {
        throw new Exception("Not implemented");
    }

    protected function cancelTokenImpl($token)
    {
        throw new Exception("Not implemented");
    }
}
