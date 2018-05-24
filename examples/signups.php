<?php
/*
This file has all the functions responsible for processing our form posts
It includes code to insert the registration into a database table as well 
as to send emails
 */
global $IS_DEV, $TO_EMAILS, $FROM_EMAIL, $ACCOUNTING_EMAIL;
$IS_DEV = false;
if ($IS_DEV){
  $TO_EMAILS = Array("");
  $FROM_EMAIL = "";
}
else{
  $TO_EMAILS = Array("");
  $FROM_EMAIL = "";
}

if ( !function_exists('starts_with') ) {
  function starts_with($haystack, $needle){
    return !strncmp($haystack, $needle, strlen($needle));
  }
}

function randstring($n=12){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ubound = strlen($characters)-1;
    $randstring = '';
    for ($i = 0; $i < $n; $i++) 
      $randstring .= $characters[rand(0, $ubound)];
    return $randstring;
}

function posted_registration_amount (){
  $rt = rval('Registration Type');
  $rt = preg_split('/:|,/',$rt);
  $rt = @$rt[0];
  return floatval($rt);

  // commented out !!
  $matches=Array();
  $amount=0;
  $match = preg_match('/^\s*\$\s*(\d+\.?\d*)(\s|-)*/', $rt, $matches);
  if($match) $amount = $matches[1];
  return floatval($amount);
}

function load_signup_data(){
  global $wpdb;
  $id = rval('signup_id');
  $o="";
  if(!is_postback() && $id && isSignupAdmin()){
    $res = $wpdb->get_results('SELECT * FROM signups WHERE id='.intval($id));
    if($res){
      $row = $res[0];
      $data =$row->signup_data;
      $data = json_decode($data, ARRAY_A);
      $data = $data[0];
      //var_dump($data);
      foreach($data as $key=>$val){
        if($key == 'signup_id') continue; // dont overwrite with nothing
        else if(is_array($val)) $_REQUEST[$key] = $val['value'];
        else $_REQUEST[$key] = $val;
      }
      //var_dump($row->signup_data);
      // TODO: JSON ENCODE/DECODE
      $o='<script type="text/javascript">if(typeof(X8)=="undefined")X8={};X8._signupJson='.$row->signup_data.'</script>';
    }
  }
  return $o;
}


function insert_signup(){
  global $wpdb;
    $data="";
    $amount="";
    $columns = ARRAY("registering-for",
                     "company", "your-first-name",  "your-email",
                     "your-last-name",
                     "address",  "address2",  "city", 
                     "state",  "zip",  "phone", 
                     "Registration_Type" , "Payment_Type");
    try{
      // put anything not a form variable into $data
      foreach($_POST as $key=>$value){
        $d = value_label($key);
        if(in_array($key, $columns)) continue;
        if(starts_with($key, 'recaptcha')) continue;
        $data.="$key: $d\r\n";
      }
    }catch(Exception $e){
      $data.="Error inserting signup: $e";
    }
    // Insert all the data we collected into the signups table
    $wpdb->insert('signups',array(
      "event"=>rval('registering-for'),
      "firstname"=>ucfirst(rval('your-first-name')),
      "lastname"=>ucfirst(rval('your-last-name')),
      "company"=>rval('company'),
      "amount"=>posted_registration_amount(),
      "email"=>rval('your-email'),
      "address"=>rval('address'),
      "address2"=>rval('address2'),
      "city"=>rval('city'),
      "state"=>rval('state'),
      "zip"=>rval('zip'),
      "phone"=>rval('phone'),
      "arrival_date"=>r_mysqldate('arrival-date'),
      "registration_type"=>value_label('Registration Type'),
      "payment_type"=>value_label('Payment Type'),
      "other_event_data"=>$data
    ));
}

function get_paypal_href($payment_key){
  $thanks = 'http://yoursite.com/thank-you-for-registering/';
  if(rval('Payment Type') != 'paypal')
    return $thanks;

  $amount = posted_registration_amount();
  if($amount==0) return $thanks;

  $siteurl = site_url();
  $url = 'https://www.paypal.com/cgi-bin/webscr?'
    .'cmd=_xclick&business=MYBUSINESS'
    .'&lc=US&item_name=Site Signup: '.rval('registering-for')
    .'&button_subtype=services'
    .'&no_note=0'
    .'&amount='.$amount
    .'&cn=Add special instructions to the seller&no_shipping=1'
    .'&email='.urlencode(rval('your-email'))
    .'&on0=registrants&os0='.urlencode(ucfirst(rval('your-first-name')).' '.ucfirst(rval('your-last-name')))
    .'&on1=payment_key&os1='.urlencode($payment_key)
    .'&rm=1'
    .'&return='.urlencode($thanks)
    .'&cancel_return=http://yoursite.com/cancelled-registration/'
    .'$notify='.urlencode($siteurl.'/?paypal_ipn=1')
    .'&bn=PP-BuyNowBF:btn_paynowCC_LG.gif:NonHosted'
    .'&currency_code=USD';

  return $url;
};

function redirect_to_next_step($payment_key){
  header('Location: '.get_paypal_href($payment_key));
  die();
}

function signup_email(){
  global $TO_EMAILS, $FROM_EMAIL, $VALID_POST, $INVALIDS, $ACCOUNTING_EMAIL;
  $for = rval('registering-for'). ' - '
    .ucfirst(rval('your-first-name')).' '
    .ucfirst(rval('your-last-name'));

  $headers  = 'MIME-Version: 1.0' . "\n";
  $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
  $headers .= "From: $FROM_EMAIL\n";

  $email_subject = "[Event Registration] ".$for;
  $values_table = "";
  $note = "";
  $email_head  = <<<EOT
<html><head><style>
    table {width:800px;}
  table .name{text-align:right; width:250px; font-weight:bold; font-size:9pt;}
  table td {border-bottom:1px solid #ddd; margin-bottom:.25em; font-size:9pt;}
  h1{font-size:12pt;}
</style>
</head>
<body>
  <div class="mail"><h1>Event Registration for $for</h1>
EOT;

  if ( function_exists('signup_email_note') ) {
    $note .= "\n".signup_email_note()."\n";
  } 
  $values_table .= '<table border="0" cellpadding="3" cellspacing="0">'."\n";
  foreach($_POST as $key=>$value){
    if(starts_with($key, 'recaptcha')) continue;
    $value = value_label($key);
    if($key == 'your-first-name' || $key == 'your-last-name' )
      $value = ucfirst($value);
    $key = input_label($key);
    $values_table .= "<tr><td class=\"name\">$key:</td><td>$value</td></tr>\n";
  }
  $v = max(0, posted_registration_amount()-posted_discount_amount());
  $values_table .= "<tr><td class=\"name\">Amount:</td><td> $ ".posted_registration_amount()."</td></tr>\n";
  $values_table .= "<tr><td class=\"name\">Total Due:</td><td> $ $v</td></tr>\n</table>\n";
  $email_foot = "\n</div>\n</body></html>";
  $main_email =  $email_head.$note.$values_table.$email_foot;
  $accounting_email =  $email_head.'<div class="smaller">'
    .$values_table."</div>".$email_foot;


  $mail_success = true;
  $tos = array_merge(array(rval('your-email')),$TO_EMAILS);
  foreach($tos as $to){
    $mail_success &= mail($to, $email_subject, $main_email, $headers);
  }

  if(!$mail_success){
    $VALID_POST = False;
    $INVALIDS['mail'] = "<div class=\"messages\"> There was an error sending your confirmation email, please contact us. </div>";
  }
}
