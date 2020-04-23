<?php
include_once("session.php");
include_once("class/pages/CheckoutPage.php");
include_once("class/beans/ProductsBean.php");

include_once("class/Cart.php");

$page = new CheckoutPage();
$page->modify_enabled = true;


$cart = new Cart();

global $products;

if (isset($_GET["addItem"])) {
    $prodID = -1;
    if (isset($_GET["prodID"])) {
        $prodID = (int)$_GET["prodID"];
    }
    try {
        $item = $products->getByID($prodID);
        if ($item["stock_amount"] < 1) {
            Session::SetAlert(tr("Съжаляваме в момента няма наличност от този продукт"));
            header("Location: " . SITE_ROOT . "details.php?prodID=" . $prodID);
            exit;
        }

        $cart->addItem($prodID);
        $_SESSION["last_added"] = $prodID;
    }
    catch (Exception $e) {

        //incorrect product id
    }
    header("Location:cart.php");
    exit;
}
if (isset($_GET["removeItem"])) {
    $prodID = -1;
    if (isset($_GET["prodID"])) {
        $prodID = $_GET["prodID"];
    }
    try {
        $products->getByID($prodID);
        $cart->removeItem($prodID);
    }
    catch (Exception $e) {

    }
    header("Location:cart.php");
    exit;
}
if (isset($_GET["deleteAll"])) {
    $cart->clearCart();
    if (isset($_SESSION["order_address"])) {
        unset($_SESSION["order_address"]);
    }
    header("Location:cart.php");
    exit;
}
else if (isset($_GET["continue_shopping"])) {
    if (isset($_SESSION["last_added"])) {
        $prodID = (int)$_SESSION["last_added"];
        header("Location: " . SITE_ROOT . "details.php?prodID=" . $prodID);
        exit;
    }
}

$page->startRender();

$page->setPreferredTitle(tr("Shopping Cart Contents"));


echo "<div class='caption'>";
echo "Съдържание на кошницата";
echo "</div>";

$page->drawCartItems();


// При доставка с наложен платеж заплащате своята поръчка на куриера при получаването й.
// 
// Поръчки, изпратени до 13:00 ч. на текущия ден ще получите за 1 работен ден. Поръчки, изпратени след 13:00 ч. на текущия ще получите за 2 работни дни.
// 
// Вашият телефон се изисква от куриерската фирма за връзка.
// 
// След заявяване на Вашата поръчката ще получите e-mail съдържащ хипер-връзка и код за потвърждение на Вашата поръчка. Проследете тази хипер-връзка за да финализирате и потвърдите Вашата поръчка за изпращане. Непотвърдени поръчки до 3 часа след заявяването им биват считани за невалидни.

// echo "<div class=hr></div>";


if ($page->total) {


    $page->showShippingInfo();

    echo "<div class='navigation'>";

    echo "<div class='slot right'>";
    echo "<a href='customer.php'>";
    echo "<img src='" . SITE_ROOT . "images/cart_checkout.png'>";
    echo "<div class='checkout_button'  >" . tr("Checkout") . "</div>";
    echo "</a>";
    echo "</div>";


    echo "<div class='slot left'>";
    echo "<a href='cart.php?deleteAll'>";
    echo "<img src='" . SITE_ROOT . "images/cart_clear.png'>";
    echo "<div class='checkout_button'  >" . tr("Empty Cart") . "</div>";
    echo "</a>";
    echo "</div>";

    echo "<div class='slot center'>";
    echo "<a class='DefaultButton' href='" . SITE_ROOT . "home.php'>";
    echo tr("Продължи пазаруването");
    echo "</a>";
    echo "</div>";

    echo "</div>";
}


$page->finishRender();
?>
