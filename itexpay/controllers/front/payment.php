<?php

/*
 * Copyright (c) 2023 ItexPay
 *
 * Author: Marc Donald AHOURE
 * Email: dmcorporation2014@gmail.com
 *
 * Released under the GNU General Public License
 */

class ItexpayPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        

        parent::initContent();

        // Buyer details
        $customer     = new Customer((int)($this->context->cart->id_customer));
        $user_address = new Address(intval($this->context->cart->id_address_invoice));

        $total  = $this->context->cart->getOrderTotal();
        $amount = filter_var(
            $total,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_FRACTION
        );

        $currency = new Currency((int)$this->context->cart->id_currency);

        if ($this->context->cart->id_currency != $currency->id) {
           
            $this->context->cart->id_currency = (int)$currency->id;
            $cookie->id_currency              = (int)$this->context->cart->id_currency;
            $cart->update();
        }

        $dateTime                          = new DateTime();
        $time                              = $dateTime->format('YmdHis');
        $this->context->cookie->order_time = $time;
        $this->context->cookie->cart_id    = $this->context->cart->id;
        $reference                         = $this->context->cart->id . '_' . $time;
        $this->context->cookie->reference  = $reference;
        $currency                          = $currency->iso_code;

        $country                           = new Country();
        $country_code                      = $country->getCountries($user_address->id_country);


      
          //getting environment...
    $environment = Tools::getValue('ITEXPAY_ENVIRONMENT', Configuration::get('ITEXPAY_ENVIRONMENT'));

    if($environment == 0)
    { 
       $api_base_url = "https://api.itexpay.com/api/pay";
    }

    else
    { 
      $api_base_url = "https://staging.itexpay.com/api/pay";
    }




 //ApI KEy 
 $apikey = Tools::getValue('ITEXPAY_PUBLIC_KEY', Configuration::get('ITEXPAY_PUBLIC_KEY'));
 


$transaction_id = $reference;


$firstname = $customer->firstname;
 //Remove space between firstname...
$firstname = preg_replace('/\s+/', '', $firstname);


    //Customer International number...
    $phonenumber = "23470022554839"; 

  
$callback_url =  filter_var(
            $this->context->link->getModuleLink(
                $this->module->name,
                'confirmation',
                ['key' => $this->context->cart->secure_key,
                'transactionid' => $transaction_id],
                true
            ),
            FILTER_SANITIZE_URL
        );



        //itexpay Checkout Api Payload...
    $data = array(
    "amount"  => $amount,
    "currency" => $currency, 
     "redirecturl" => $callback_url,
     "customer" =>  array('email' => $customer->email,
                        'first_name' =>  $firstname, 
                        'last_name' => $customer->lastname,
                        'phone_number' => $phonenumber ),
          "reference" => $transaction_id,
     
);


//Previous Url 
$back_url =  Tools::getHttpHost() . __PS_BASE_URI__ . 'order';

//Encoding playload...
$json_data = json_encode($data);

//Api base URL...
 $url = $api_base_url;                                                                                                            
// Initialization of the request
$curl = curl_init();

// Definition of request's headers
curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYHOST => false,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_ENCODING => "json",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer ".$apikey."",
    "cache-control: no-cache",
    "content-type: application/json; charset=UTF-8",
    
  ),
   CURLOPT_POSTFIELDS => $json_data,
));

// Send request and show response
$response = curl_exec($curl);
$err = curl_error($curl);


curl_close($curl);

if ($err) {

    //Api error if any...
    //return  $err;
    Tools::redirect('index.php?controller=order&step=1');

} else {

  
    $response_data = json_decode($response, true);
        


if (!isset($response_data['amount'])) {
    $amount = null;
}

else
{
    $amount = $response_data['amount'];
}

if (!isset($response_data['currency'])) {
   $currency  = null;
}

else
{
    $currency = $response_data['currency'];
}


if (!isset($response_data['paid'])) {
    $paid = null;
}

else
{
    $paid = $response_data['paid'];
}

if (!isset($response_data['status'])) {
     $status  = null;
}

else
{
    $status = $response_data['status'];
}



if (!isset($response_data['env'])) {
    $env = null;
}



else
{
    $env = $response_data['env'];
}

if (!isset($response_data['reference'])) {
   $reference  = null;
}

else
{
    $reference = $response_data['reference'];
}


if (!isset($response_data['paymentid'])) {
     $paymentid = null;
}

else
{
    $paymentid = $response_data['paymentid'];
}

if (!isset($response_data['authorization_url'])) {
    $authorization_url  = null;
}

else
{
    $authorization_url = $response_data['authorization_url'];
}

if (!isset($response_data['failure_message'])) {
    $failure_message  = null;
}

else
{
    $failure_message = $response_data['failure_message'];
}



if($status == "successful" && $paid == false)
{ 

    //redirect to checkout page...
    Tools::redirect($authorization_url);

      

}

   
    else
    {   
    
    //redirecting to order page or cart...
    Tools::redirect('index.php?controller=order&step=1');
      
    }


}// end of main else..

      


    }
}
    