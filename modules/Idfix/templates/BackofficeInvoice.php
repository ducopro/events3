<style>

#wrapper {
    border:2px outset black;
    border-radius:10px;
    width:40em;
    padding:1em;
}

#calc {
    width:100%;
    clear:both;
    padding:2em 0em;
}

#wrap-right{
    float:right;      
}
#wrap-left {
    float:left;
}
#pay {
    text-align:center;
    font-style: italic;
}
#international {
    padding-bottom:2em; 
    text-align: justify;
}

</style>

<div id="wrapper">
 
    <div id="wrap-left">
        <h1>Invoice</h1>
        <em>Invoice Number:</em> <?php print $invoice_id?><br />
        <em>Invoice Date:</em> <?php print date('Y-m-d'); ?><br />
        <br />
        <strong>Idfix Platform as a Service</strong><br />
        Mullensstrook 13<br />
        2726VP Zoetermeer<br />
        The Netherlands<br />
        <em>Tel. (+31)(6)37294115</em><br />
        <em>info@idfixplatform.com</em><br />
        <br />
        ABN-AMRO NL45ABNA0810384795<br />

    </div>

    <div id="wrap-right">
        <h1>Invoiced to:</h1>
        <?php print $address; ?>
    </div>



    <div id="calc" >
        <?php print $calculation; ?>
    </div>

    <div id="international">
    <h3>Note for our International Customers:</h3>
    Customers based in The Netherlands should pay the total price including VAT. International customers should pay the above mentioned price excluding VAT.
    We prefer payment in EURO, but we accept payments in DOLLAR for international customers. The total price for this invoice excluding VAT in dollars is: <strong><?php print $amount_dollar; ?></strong> 
    </div>


    <div id="pay">
    Payments should be made within 30 days after invoice date and by international bank transfer to <strong>NL45ABNA0810384795 WJJTOL</strong><br />
    Please specify on your banktransfer: <strong>idfixplatform.com <?php print $invoice_id?></strong>   
    </div>

</div>