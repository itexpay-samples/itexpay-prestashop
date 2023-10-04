<?php

/*
 * Copyright (c) 2023 ItexPay
 *
 * Author: Marc Donald AHOURE
 * Email: dmcorporation2014@gmail.com
 *
 * Released under the GNU General Public License
 */

class ItexpayConfirmationModuleFrontController extends ModuleFrontController
{

   
    
    public function initContent()
    {
        if (isset($_GET['key'])) {

        //Getting Validation key form return URL...
        $validation_key = $_GET['key']; 

       
        //Getting reference ID from cookie...
        $reference_id = $this->context->cookie->reference;

        //Getting cart currecy...
        $cart = $this->context->cart;
        $cart_currecny = $cart->id_currency;
        $cart_id = $cart->id;

        if($validation_key == $this->context->cart->secure_key)
        {
             
             //Verify transaction from ItexPay...
            $this->verify_transaction($reference_id,$validation_key,$cart_currecny,$cart_id);
        }

       else
       {
           
             Tools::redirect('404');
       }

    }

    else
    {
        Tools::redirect('404');

    }
            
        } // end of initContent...
    

    public function verify_transaction($reference_id,$validation_key,$cart_currecny,$cart_id)
    {   

        //getting environment...
    $environment = Tools::getValue('ITEXPAY_ENVIRONMENT', Configuration::get('ITEXPAY_ENVIRONMENT'));

        if($environment == 0)
    { 
        $status_check_base_url = 'https://api.itexpay.com/api/v1/transaction/status?merchantreference='.$reference_id;
    }

    else
    { 
      $status_check_base_url = 'https://staging.itexpay.com/api/v1/transaction/status?merchantreference='.$reference_id;
    }
 

 //ApI KEy 
 $api_key = Tools::getValue('ITEXPAY_PUBLIC_KEY', Configuration::get('ITEXPAY_PUBLIC_KEY'));


// Initialize cURL session
$ch = curl_init();

 

// Set the cURL options
curl_setopt($ch, CURLOPT_URL, $status_check_base_url); // URL to send the request to
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout in seconds
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Overall timeout in seconds

// Set custom headers
$headers = array(
    'Authorization: Bearer '.$api_key.'', 
);

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set the custom headers

// Execute the cURL request and get the response
$response = curl_exec($ch);

$response_data = json_decode($response, true );


// Check for cURL errors
if (curl_errno($ch)) {
   // echo 'cURL error: ' . curl_error($ch);
     Tools::redirect('index.php?controller=order&step=1&message='.curl_error($ch));


} else {



 if (!isset($response_data['code'])) {
     $transaction_code = null;
 }

 else
 {
     $transaction_code = $response_data['code'];
 }

 if (!isset($response_data['message'])) {
     $transaction_message = null;
 }

 else
 {
     $transaction_message = $response_data['message'];
 }


 if (!isset($response_data['order'])) {
     $order_details = null;
 }

 else
 {
     $order_details = $response_data['order'];
   

    if (!isset($order_details['amount'])) {
     $transaction_amount = 0;
 }
 else
 {
    
     $transaction_amount = $order_details['amount'];
 }

 }

 if (!isset($response_data['transaction'])) {
     $transaction_details = null;
 }

 else
 {
     $transaction_details = $response_data['transaction'];

      if (!isset($transaction_details['reference'])) {
     $transaction_reference = null;
 }
 else
 {

     $transaction_reference = $transaction_details['reference'];
 }

 }





  //checking if transaction is successful
    if($transaction_code == "00")
    { 

        //Order validation... 
        $this->module->validateOrder((int)$cart_id, Configuration::get('PS_OS_PAYMENT'), $transaction_amount, $this->module->displayName, null, array(), (int)$cart_currecny, false, $validation_key);

        //redirect to confirmation page...
        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart_id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$validation_key);

     

      
    }

   

else
{
   //die("we could not process payment");

               Tools::redirect('index.php?controller=order&step=1&message='.$transaction_message);
         $this->module->validateOrder((int)$cart_id, Configuration::get('PS_OS_ERROR'),0, $this->module->displayName, null, array(), (int)$cart_currecny, false, $validation_key);
            
}



}

// Close the cURL session
curl_close($ch);



    } // end of verify_transaction...
    
} // end of ItexpayConfirmationModuleFrontController...
