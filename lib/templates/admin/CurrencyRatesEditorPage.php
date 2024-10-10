<?php
include_once("templates/admin/AdminPageTemplate.php");

include_once("beans/CurrenciesBean.php");
include_once("beans/CurrencyRatesBean.php");
include_once("responders/json/CurrencyRateResponder.php");
include_once("dialogs/InputMessageDialog.php");

class CurrencyRatesEditorPage extends AdminPageTemplate
{

    protected $curr;
    protected $rates;

    public function __construct()
    {
        parent::__construct();

        $dialog = new InputMessageDialog();

        $this->getPage()->head()->addCSS(SPARK_LOCAL . "/css/InputField.css");

        $responder = new CurrencyRateResponder();

        $cb = new CurrenciesBean();
        $cb_qry = $cb->queryFull();
        $num = $cb_qry->exec();
        $this->curr = array();
        while ($row = $cb_qry->next()) {
            $this->curr[] = $row;
        }

        $cr = new CurrencyRatesBean();
        $cr_qry = $cr->queryFull();
        $cr_qry->exec();
        $this->rates = array();
        while ($row = $cr_qry->next()) {
            $this->rates[$row["srcID"]][$row["dstID"]] = array("crID" => $row["crID"], "rate" => $row["rate"]);
        }
    }

    protected function initPageActions()
    {

    }

    public function initView()
    {

    }

    public function renderImpl()
    {
        echo "<div class='CurrencyRateEditor'>";

        echo "<div class='Line'>"; //top line
        echo "<div class='Item space'></div>";
        foreach ($this->curr as $row) {
            $currencyID = (int)$row["currencyID"];
            $code = $row["currency_code"];
            echo "<div class='Item code' dstID='$currencyID'>$code</div>";
        }
        echo "</div>";//Line - top line

        //
        foreach ($this->curr as $pos_y => $datay) {
            $srcID = (int)$datay["currencyID"];
            $code = $datay["currency_code"];

            echo "<div class='Line'>"; //for each currency
            echo "<div class='Item code' srcID='$srcID'>" . $code . "</div>";

            foreach ($this->curr as $pos_x => $datax) {
                $dstID = (int)$datax["currencyID"];
                if ($pos_x == $pos_y) {
                    echo "<div class='Item space'></div>";
                    continue;
                }
                $rate = 1;

                $src = $srcID;
                $dst = $dstID;

                if (isset($this->rates[$srcID][$dstID])) {
                    $data = $this->rates[$srcID][$dstID];

                    $rate = $data["rate"];
                }
                echo "<div class='Item rate' srcID='$src' dstID='$dst' >$rate</div>";
            }
            echo "</div>"; //Line for each currency
        }

        echo "</div>"; //CurrencyRates

        ?>
        <script type="text/javascript">
            //TODO: use InputMessageDialog
            let input_dialog = new MessageDialog();

            let req = new JSONRequest();

            req.setResponder("currency_rates");
            req.setFunction("setrate");

            onPageLoad(function () {
                let items = document.querySelectorAll(".Item.rate");
                items.forEach(function (value, key, parent) {
                    value.addEventListener("click", function (event) {

                        let elm = event.target;

                        cellClicked(elm);

                    });
                });

            });

            function cellClicked(elm) {

                req.setParameter("srcID", elm.getAttribute("srcID"));
                req.setParameter("dstID", elm.getAttribute("dstID"));

                //console.log(elm.innerHTML);

                let old_value = elm.innerHTML;

                input_dialog.setID("input_dialog");
                input_dialog.show();

                let input = document.querySelector(input_dialog.selector() + " INPUT");
                input.value = old_value;

                input_dialog.buttonAction = function (action) {
                    if (action == "cancel") {
                        input_dialog.remove();
                    } else if (action == "confirm") {
                        let value = parseFloat(input.value);

                        //dialog.remove();

                        if (value <= 0 || isNaN(value)) {
                            showAlert("Incorrect value");
                        } else {
                            //proceed with request

                            req.setParameter("rate", value);


                            //console.log(url.href);
                            req.start();
                            req.onSuccess = function (result) {
                                    var response = result.response;

                                    let forward_item = document.querySelector(".rate[srcID='" + response.srcID + "'][dstID='" + response.dstID + "']");
                                    forward_item.innerHTML = parseFloat(response.forward_rate).toFixed(2);
                                    let backward_item = document.querySelector(".rate[srcID='" + response.dstID + "'][dstID='" + response.srcID + "']");
                                    backward_item.innerHTML = parseFloat(response.backward_rate).toFixed(2);

                                    input_dialog.remove();
                            };

                        }
                    }
                };
            }
        </script>
        <?php
    }
}
