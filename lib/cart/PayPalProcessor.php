<?php
include_once("utils/Session.php");

// 1.setup is needed to access the processor.
// $proc = new PayPalProcessor(PayPalProcessor::MODE_SANDBOX);
// $proc->setCredentials(PayPal_ApiUsername, PayPal_ApiPassword, PayPal_ApiSignature);

// 2.get payment token from paypal
// $sale_item = new PayPalSale("My Sale Order", $orderID, "Sale Name", $price_total, 1);
// $token = $proc->getToken($sale_item, "<confirm_url>","<cancel_url>", 1);
// $sale_item->setPaymentToken(urldecode($token));
// $paypalURL = $proc->getRedirectURL($token);
// Session::set("sale_item", serialize($sale_item));
// echo "<a class='DefaultButton next' href='$paypalURL'>Pay using Paypal</a>";

// 3. finalize payment happening on <confirm_url> using the paypal token and the payerID
// if (!isset($_GET["token"]) || !isset($_GET["PayerID"])) {
// 		throw new Exception("Paypal payment token was not found.");
// }
// $sale_item = $proc->loadSaleItem(urldecode($_GET["token"]));
// $sale_item->setPayerID(urldecode($_GET["PayerID"]));
// 
// //PaypalSaleDetails()
// $sale_details = $proc->confirmPayment($sale_item);

class PayPalSale
{

    protected $ItemName = NULL;
    protected $ItemNumber = NULL;
    protected $ItemDesc = NULL;
    protected $ItemPrice = NULL;
    protected $ItemQty = NULL;

    protected $ShippingCost = 0.0;
    protected $HandlingCost = 0.0;
    protected $InsuranceCost = 0.0;
    protected $TaxAmount = 0.0;

    protected $PaymentToken = "";

    public function __construct($ItemName, $ItemNumber, $ItemDesc, $ItemPrice, $ItemQty)
    {
        $this->ItemName = $ItemName; //Course Booking
        $this->ItemNumber = $ItemNumber; // course ID
        $this->ItemDesc = $ItemDesc; // course title
        $this->ItemPrice = $ItemPrice; // course title
        $this->ItemQty = $ItemQty; // 1

    }

    public function setPayerID($payerID)
    {
        $this->payerID = $payerID;
    }

    public function getPayerID()
    {
        return $this->payerID;
    }

    public function setPaymentToken($token)
    {
        $this->PaymentToken = $token;
    }

    public function getPaymentToken()
    {
        return $this->PaymentToken;
    }

    public function setShippingCost($ShippingCost)
    {
        $this->ShippingCost = $ShippingCost;
    }

    public function getShippingCost()
    {
        return $this->ShippingCost;
    }

    public function setHandlingCost($HandlingCost)
    {
        $this->HandlingCost = $HandlingCost;
    }

    public function getHandlingCost()
    {
        return $this->HandlingCost;
    }

    public function setInsuranceCost($InsuranceCost)
    {
        $this->InsuranceCost = $InsuranceCost;
    }

    public function getInsuranceCost()
    {
        return $this->InsuranceCost;
    }

    //tax amount as percent
    public function setTaxAmount($TaxAmount)
    {
        $this->TaxAmount = $TaxAmount;
    }

    public function getTaxAmount()
    {
        return $this->TaxAmount;
    }

    public function getTotalPrice()
    {
        $line_total = $this->ItemQty * $this->ItemPrice;
        $line_total = $line_total + $this->HandlingCost + $this->ShippingCost + $this->InsuranceCost;
        return $line_total;
    }

    public function getGrandTotal()
    {

        $line_total = $this->getTotalPrice();
        $grand_total = $line_total + (($line_total * $this->TaxAmount) / 100.0);

        return $grand_total;
    }

    public function getItemName()
    {
        return $this->ItemName;
    }

    public function getItemNumber()
    {
        return $this->ItemNumber;
    }

    public function getItemDesc()
    {
        return $this->ItemDesc;
    }

    public function getItemPrice()
    {
        return $this->ItemPrice;
    }

    public function getItemQty()
    {
        return $this->ItemQty;
    }

}

class PayPalSaleDetails
{
    protected $transactionID = "";
    protected $status = "";
    protected $response = "";
    protected $itemID = -1;
    protected $correlationID = "";
    protected $timestamp = "";
    protected $payerID = "";

    public function __construct($status, $transactionID, $payerID, $response)
    {
        $this->status = $status;
        $this->transactionID = $transactionID;
        $this->payerID = $payerID;

        $this->itemID = (int)urldecode($response["L_NUMBER0"]);
        $this->correlationID = urldecode($response["CORRELATIONID"]);
        $this->timestamp = urldecode($response["TIMESTAMP"]);

        $this->response = array();
        foreach ($response as $key => $val) {
            $this->response[$key] = urldecode($val);
        }

    }

    public function getPayerID()
    {
        return $this->payerID;
    }

    public function getCorrelationID()
    {
        return $this->correlationID;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getTransactionID()
    {
        return $this->transactionID;
    }

    public function getItemID()
    {
        return $this->itemID;
    }

    public function getResponse()
    {
        return $this->response;
    }
}

class PayPalProcessor
{

    const MODE_LIVE = "live";
    const MODE_SANDBOX = "sandbox";

    protected $api_endpoint = NULL;

    protected $api_mode = NULL;

    protected $redirectURL = NULL;

    protected $api_username = NULL;
    protected $api_password = NULL;
    protected $api_signature = NULL;

    protected $currency_code = "USD";
    protected $logo_image;

    public function __construct($api_mode)
    {
        if (strcmp($api_mode, PayPalProcessor::MODE_LIVE) == 0) {

            $this->api_endpoint = "https://api-3t.paypal.com/nvp";
            $this->api_mode = PayPalProcessor::MODE_LIVE;
            $this->redirectURL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
        }
        else {
            $this->api_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->api_mode = PayPalProcessor::MODE_SANDBOX;
            $this->redirectURL = "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
        }
    }

    public function getRedirectURL($token)
    {
        return $this->redirectURL . $token;
    }

    public function setLogoImage($logo_url)
    {
        $this->logo_image = $logo_url;
    }

    public function setCredentials($api_username, $api_password, $api_signature)
    {
        $this->api_username = $api_username;
        $this->api_password = $api_password;
        $this->api_signature = $api_signature;
    }

    public function setCurrencyCode($currency_code)
    {
        $this->currency_code = $currency_code;
    }

    public function getToken(PayPalSale $sale, $PayPalReturnURL, $PayPalCancelURL, $no_shipping = 0)
    {

        $padata = "&METHOD=SetExpressCheckout" . "&RETURNURL=" . urlencode($PayPalReturnURL) . "&CANCELURL=" . urlencode($PayPalCancelURL) . "&PAYMENTREQUEST_0_PAYMENTACTION=" . urlencode("SALE") .

            "&L_PAYMENTREQUEST_0_NAME0=" . urlencode($sale->getItemName()) . "&L_PAYMENTREQUEST_0_NUMBER0=" . urlencode($sale->getItemNumber()) . "&L_PAYMENTREQUEST_0_DESC0=" . urlencode($sale->getItemDesc()) . "&L_PAYMENTREQUEST_0_AMT0=" . urlencode($sale->getItemPrice()) . "&L_PAYMENTREQUEST_0_QTY0=" . urlencode($sale->getItemQty()) .

            "&NOSHIPPING=$no_shipping" . //set 1 to hide buyer"s shipping address, in-case products that does not require shipping

            "&PAYMENTREQUEST_0_ITEMAMT=" . urlencode($sale->getTotalPrice()) . "&PAYMENTREQUEST_0_TAXAMT=" . urlencode($sale->getTaxAmount()) . "&PAYMENTREQUEST_0_SHIPPINGAMT=" . urlencode($sale->getShippingCost()) . "&PAYMENTREQUEST_0_HANDLINGAMT=" . urlencode($sale->getHandlingCost()) . "&PAYMENTREQUEST_0_INSURANCEAMT=" . urlencode($sale->getInsuranceCost()) . "&PAYMENTREQUEST_0_AMT=" . urlencode($sale->getGrandTotal()) . "&PAYMENTREQUEST_0_CURRENCYCODE=" . urlencode($this->currency_code) . "&LOCALECODE=GB" . //PayPal pages to match the language on your website.
            "&CARTBORDERCOLOR=FFFFFF" . //border color of cart
            "&ALLOWNOTE=1";
        if ($this->logo_image) {
            $padata .= "&LOGOIMG=$this->logo_image";
        }

        $httpParsedResponseAr = $this->PPHttpPost("SetExpressCheckout", $padata);

        //Respond according to message we receive from Paypal
        if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {

            return $httpParsedResponseAr["TOKEN"];

        }
        else {
            throw new Exception(urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]));
        }
    }

    public function confirmPayment(PayPalSale $sale)
    {

        $padata = "&TOKEN=" . urlencode($sale->getPaymentToken()) . "&PAYERID=" . urlencode($sale->getPayerID()) . "&PAYMENTREQUEST_0_PAYMENTACTION=" . urlencode("SALE") .

            //set item info here, otherwise we won"t see product details later
            "&L_PAYMENTREQUEST_0_NAME0=" . urlencode($sale->getItemName()) . "&L_PAYMENTREQUEST_0_NUMBER0=" . urlencode($sale->getItemNumber()) . "&L_PAYMENTREQUEST_0_DESC0=" . urlencode($sale->getItemDesc()) . "&L_PAYMENTREQUEST_0_AMT0=" . urlencode($sale->getItemPrice()) . "&L_PAYMENTREQUEST_0_QTY0=" . urlencode($sale->getItemQty()) .

            "&PAYMENTREQUEST_0_ITEMAMT=" . urlencode($sale->getTotalPrice()) . "&PAYMENTREQUEST_0_TAXAMT=" . urlencode($sale->getTaxAmount()) . "&PAYMENTREQUEST_0_SHIPPINGAMT=" . urlencode($sale->getShippingCost()) . "&PAYMENTREQUEST_0_HANDLINGAMT=" . urlencode($sale->getHandlingCost()) . "&PAYMENTREQUEST_0_INSURANCEAMT=" . urlencode($sale->getInsuranceCost()) . "&PAYMENTREQUEST_0_AMT=" . urlencode($sale->getGrandTotal()) . "&PAYMENTREQUEST_0_CURRENCYCODE=" . urlencode($this->currency_code);

        $httpParsedResponseAr = $this->PPHttpPost("DoExpressCheckoutPayment", $padata);

        //Check if everything went ok..
        if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {

            //echo "<h2>Success</h2>";
            //echo "Your Transaction ID : ".urldecode($httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"]);

            $status = urldecode($httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"]);
            $transactionID = urldecode($httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"]);

            if (strcmp($status, "Completed") != 0 && strcmp($status, "Pending") != 0) {
                $error = "Payment status error.";
                if (isset($httpParsedResponseAr["L_LONGMESSAGE0"])) {
                    $error .= " Error Details: " . urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]);
                }
                throw new Exception($error);
            }

            $padata = "&TOKEN=" . urlencode($sale->getPaymentToken());

            $httpParsedResponseAr = $this->PPHttpPost("GetExpressCheckoutDetails", $padata);

            if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
                return new PayPalSaleDetails($status, $transactionID, $sale->getPayerID(), $httpParsedResponseAr);
            }
            else {
                throw new Exception("GetTransactionDetails failed: " . urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]));
            }
        }
        else {
            throw new Exception("GetTransactionDetails failed: " . urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]));
        }
    }

    protected function PPHttpPost($methodName_, $nvpStr_)
    {

        // 			global $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $API_Endpoint;

        // Set up your API credentials, PayPal end point, and API version.
        $API_UserName = urlencode($this->api_username);
        $API_Password = urlencode($this->api_password);
        $API_Signature = urlencode($this->api_signature);

        $version = urlencode("109.0");

        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set the API operation, version, and API signature in the request.
        $nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";

        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        // Get response from the server.
        $httpResponse = curl_exec($ch);

        if (!$httpResponse) {
            throw new Exception("$methodName_ failed: " . curl_error($ch) . "(" . curl_errno($ch) . ")");
        }

        // Extract the response details.
        $httpResponseAr = explode("&", $httpResponse);

        $httpParsedResponseAr = array();
        foreach ($httpResponseAr as $i => $value) {
            $tmpAr = explode("=", $value);
            if (sizeof($tmpAr) > 1) {
                $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
            }
        }

        if ((0 == sizeof($httpParsedResponseAr)) || !array_key_exists("ACK", $httpParsedResponseAr)) {
            throw new Exception("Invalid HTTP Response for POST request($nvpreq) to $this->api_endpoint.");
        }

        return $httpParsedResponseAr;
    }

    public function loadSaleItem($token)
    {
        if (!Session::Contains("sale_item")) {
            throw new Exception("Incorrect payment state. Sale item not found.");
        }
        $sale_item = Session::Get("sale_item");
        $sale_item = unserialize($sale_item);
        $sale_token = $sale_item->getPaymentToken();
        if (strcmp($sale_token, $token) != 0) {
            throw new Exception("Sale token missmatch: Session: " . $sale_token . " | Requested: " . $token);
        }
        return $sale_item;
    }
}

?>
