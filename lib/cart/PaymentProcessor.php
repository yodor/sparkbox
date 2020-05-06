<?php
include_once("beans/OrdersBean.php");
include_once("cart/PaymentResult.php");

abstract class PaymentProcessor
{
    protected $gateway_used;
    protected $userID;

    const STATUS_WAITING_PAYMENT = 1;
    const STATUS_PAYMENT_SUCCESS = 2;
    const STATUS_PROCESSING_SHIPMENT = 3;
    const STATUS_SHIPPED = 3;

    public function __construct($userID)
    {
        $this->userID = $userID;
    }

    protected abstract function processOrderImpl($orderID, $order_row);

    protected abstract function processTokenImpl($token);

    protected abstract function cancelTokenImpl($token);

    public function processOrder($orderID)
    {
        $order_row = PaymentProcessor::checkOrder($orderID, $this->userID);
        $result = $this->processOrderImpl($orderID, $order_row);
        $this->paymentFinal($result);

    }

    public function processToken($token)
    {
        $result = $this->processTokenImpl($token);
        $chk = get_class($result);
        if ($chk && strcmp($chk, "PaymentResult") == 0) {
            $this->paymentFinal($result);
        }
        else {
            throw new Exception("Undefined Error Processing payment");
        }

    }

    public function cancelToken($token)
    {
        $orderID = $this->cancelTokenImpl($token);
        header("Location: payment.php?orderID=$orderID");
        exit;
    }

    protected function paymentFinal(PaymentResult $payment_result)
    {

        $orderID = $payment_result->getOrderID();

        $ob = new OrdersBean();
        $ob->finalizePayment($payment_result);

        header("Location: confirmation.php?orderID=$orderID");
        exit;
    }



    //process payment using datacash
    //status = 1 awaiting payment, 2 payment processed fine, 3 shipment processing, 4 shipped
    public static function checkOrder($orderID, $userID)
    {
        $ob = new OrdersBean();

        $c = $ob->checkOwner($orderID, $userID);

        if ($c !== true) {
            throw new Exception($c);
        }
        $order_row = $ob->getByID($orderID);

        $status = (int)$order_row["status"];
        if ($status !== OrdersBean::STATUS_AWAITING_PAYMENT) {
            throw new Exception("Incorrect order status.");
        }
        return $order_row;
    }
}

?>