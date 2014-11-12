<?php
$VALID_POST = false;
$INVALIDS = Array();
$REQUIREDS = Array();
$WP_INCLUDES = Array();
$FORM_CONTROLS = Array();
$RECAPTCHAED = false;



function value_label($name, $idx=0, $no_default=false){
  $ctl = get_control($name, $idx);
  $it = @$ctl->value_label;
  if($it) return trim($it);
  if($no_default) return null;
  return rval($name);
}

function input_label($name, $idx=0, $no_default=false){
  $ctl = get_control($name, $idx);
  $it = @$ctl->text;
  $n = @$ctl->name;
  if($it) return trim($it);
  if($no_default) return $no_default;
  if($n) return $n;
  return $name;
}

/*
//require_once(dirname(__FILE__) . '/recaptchalib.php');
function validate_captcha(){
  global $RECAPTCHAED, $INVALIDS, $VALID_POST;
  if(!$RECAPTCHAED || !is_postback()) return true;
  $privatekey = "";
  $resp = recaptcha_check_answer ($privatekey,
                                  @$_SERVER["REMOTE_ADDR"],
                                  @$_POST["recaptcha_challenge_field"],
                                  @$_POST["recaptcha_response_field"]);
  if(!$resp->is_valid){
    @$INVALIDS['recaptcha'] = 'You entered the reCaptcha incorrectly, please try again!';
      //."(reCAPTCHA said: " . $resp->error . ")";
    return false;
  }
  return true;
}

function template_recaptcha(){
  global $RECAPTCHAED;
  $RECAPTCHAED = true;
  $publickey="";
  validate_captcha();
  return recaptcha_get_html($publickey);
}
add_shortcode('recaptcha', 'template_recaptcha');
*/
class FormControl{
  var $name=null, $atts=null, $type=null, $text=null, $value_text=null;
  var $fields=null;
  function FormControl($args=Array()){
    $this->fields=Array('name', 'atts', 'type', 'text', 'value_text');
    foreach($this->fields as $name) $this->{$name}=@$args[$name];
    if(!$this->type) $this->type = @$this->atts['type'];
  }
}

function get_control($name, $index=null){
  global $FORM_CONTROLS;
  $ctls = @$FORM_CONTROLS[prep_name($name)];
  if($index===null) return $ctls;
  return $ctls[$index];
}

function &add_control( $name, &$atts, $text=null, $type=null){
  global $FORM_CONTROLS;
  if(!@$FORM_CONTROLS[prep_name($name)]) $FORM_CONTROLS[prep_name($name)] = Array();
  $controls = &$FORM_CONTROLS[prep_name($name)];
  $entry = new FormControl(Array('name'=>$name, 'atts'=>&$atts, 'type'=>$type, 'text'=>$text));
  $controls[] = &$entry;
  return $entry;
}

// read a file, process its shortcodes and include them result here inline
function wp_include($pth){
  global $WP_INCLUDES;
  if(@$WP_INCLUDES[$pth]) echo @$WP_INCLUDES[$pth];
  else echo do_shortcode(file_get_contents($pth, FILE_USE_INCLUDE_PATH));
}

function wp_preprocess($pth){
  global $WP_INCLUDES;
  $WP_INCLUDES[$pth]= do_shortcode(file_get_contents($pth, FILE_USE_INCLUDE_PATH));
}

// Print the request parameters from the page
function debug_form_post(){
  echo "<hr />";
  foreach ($_REQUEST as $key => $value){
    $v = rval($key);
    echo "$key => $v <br />";
  }
}
// print all or the listed request parameters as new hidden input fields
function persist_request_params($list=null){
  if(is_string($list)) $list = preg_split('/\s*,\s*/', $list);
  foreach ($_REQUEST as $key => $value){
    if(is_null($list) || in_array($key, $list))
      echo "<input type=\"hidden\" value=\"$value\" name=\"$key\" />";
  }
}

if ( !function_exists('prep_name') ) {
  function prep_name($n){
    $n = str_replace(" ", "_", trim($n));
    return $n;
  }
}

if ( !function_exists('prepped_dict_value') ) {
  function prepped_dict_value($name, $dict, $implode=true){
    $v = isset($dict[$name]) ? $dict[$name] : null;
    if(!$v){
      $name = prep_name($name);
      $v = isset($dict[$name]) ? $dict[$name] : null;
    }
    
    if(is_array($v)){
      $res = Array();
      foreach($v as $d) $res[]=trim(strip_tags($d));
      if($implode) $res=implode(',', $res);
      return $res;
    }
    if ($v) return trim(strip_tags($v));
    return null;
  }
}

if ( !function_exists('rval') ) {
  // gets a value from the request collection
  // Duplicated from functions for completeness
  function rval( $name, $implode=true ){
    return prepped_dict_value($name, $_REQUEST, $implode);
  }
}

if ( !function_exists('rvals') ) {
  // same as rval, but does it for mulitple names
  // either returning the first result or all of them
  function rvals($names, $first=true, $implode=true){
    $res = Array();
    $it;
    if(is_string($names)) $names= explode(',',$names);
    foreach($names as $n){
      $it = rval($n, $implode);
      if($it){
        if($first) return $it;
        $res[] = $it;
      }
    }
    if($implode) $res = implode(',',$res);
    return $res;
  }
}

if ( !function_exists('sc_atts_for_env') ) {
// does shortcode atts and returns this value.  It also
// sets the values in $atts to null after processing
// This function is also defined in functions.php 
// it is copied here so that this file can be mostly
// self contained for easy moving to other projects
  function sc_atts_for_env($arr, &$atts){
    $rtn = shortcode_atts($arr, $atts);
    foreach($arr as $k=>$v) $atts[$k] = null;
    return $rtn;
  }
}

// converts an array of attributes to a string of html attribs
if ( !function_exists('atts_string') ) {
  function atts_string($atts){
    $c = " ";
    if($atts) 
      foreach( $atts as $k=>$v ){
	if(!is_null($v)){
	  $v = esc_attr($v);
	  $c .= "$k=\"$v\" ";
	}
      }
    return $c; 
  }
}

if( !function_exists('is_postback') ){
  function is_postback(){
    $url = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    return $_SERVER['REQUEST_METHOD'] == 'POST' &&
      strpos($_SERVER['HTTP_REFERER'], $url)!==false;
  }
}

// test that if we have an r and it is required that it 
// meets those requirements
function r_meets_requirements( $name ){
  $v = rval($name);
  if (is_null($v)) return false;
  $v = trim($v);
  if ( strlen($v) == 0) return false;
  return true;
}

// Converts a number of common strings to their boolean equivalent
// if passed a bool or null, simply returns it
function to_bool($val){
  if($val === true || $val === false || $val === null) return $val;
  $val = strtolower($val);
  $val = $val == 'on' || $val == 'yes' || $val == 'true' || $val == '1';
  return $val;
}

// Gets a value from the request and to_bools it
function r_bool( $name ){ return to_bool(rval($name)); }

// Gets a value from the request as a date and returns 
// it or null if something failed
function r_mysqldate($name){
  try{
    $d = new DateTime(rval($name));
    return $d->format('Y-m-d');
  }
  catch(Exception $e){}
  return null;
}

// prints all the messages in $INVALIDS (the value cell)
function print_validation_errors(){
  global $INVALIDS;
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if (count($INVALIDS) > 0){
      echo "<div class=\"error-messages\" >";
      foreach ($INVALIDS as $key => $value) if($value && is_string($value))
        echo "<div>$value</div>";
      echo "</div>";
    }
  }
}

// Returns any extra classes this form field should have
// based on the request (mostly for validation)
function programatic_classes($name){
  $out = "";
  if(is_invalid($name)) $out.= "error";
  return $out;
}

// Marks a field as invalid
function mark_invalid($name, $message=true){
  global $INVALIDS;
  $INVALIDS[prep_name($name)] = $message;
}

// checks to see if a field is invalid
function is_invalid($name, $message=true){
  global $INVALIDS;
  return @$INVALIDS[prep_name($name)];
}

//Are ther any invalid controls on the page
function valid_page(){ 
  global $INVALIDS; 
  return count($INVALIDS) == 0;
}

//Attempts to validate a single form element
function validate_field($name, &$atts, $id=null, $text=null){
  global $REQUIREDS;
  $v = rval($name);
  if(!$id) $id = $name;
  // This will pull the validation atts out of the array
  extract(sc_atts_for_env(array(
    'regex'=>null,
    'required'=>false,
    'validation'=>null,
    'error_message'=>null,
  ), $atts));
  if(!$text) $text = $name;
  if($required) $REQUIREDS[] = $name;
  if(is_postback()){
    $check = true;
    $v = rval($name);
    if($required) $check &= r_meets_requirements($name);
    if(!$check) return mark_invalid($id, "$text is required, please fill in a value");
    if($regex && $v) $check &= preg_match($regex, $v);
    if($validation && $v) $check &= $validation($v);
    // Validation functions may wish to put messages directly in
    if(!$check && !is_invalid($id))
      mark_invalid($id, ($error_message ? $error_message
			 : "Please correct $name and try again"));
  }
}

function template_input($atts, $text=null){
  extract(sc_atts_for_env(array(
    'name'=>null,
    'type'=>'text',
    'class'=>null,
    'note'=>null,
    'default'=>null,
  ), $atts));
  $vatt = "";
  if(!$name) $name = trim($text);
  validate_field($name, $atts, null, $text);
  if(rval($name)) $atts["value"] = rval($name);
  if($default && !@$atts["value"]) $atts["value"] = $default;
  $css = programatic_classes($name);
  add_control($name, $atts, $text, 'input');
  $atts = atts_string($atts);
  if($note)$note="<span class=\"note\">$note</span>";
  return "
   <label class=\"$css $class\"><span class=\"text\">$text</span>
     <input type=\"$type\" name=\"$name\" $vatt $atts />$note
   </label>";
}
add_shortcode('text', 'template_input');
add_shortcode('input', 'template_input');

function template_password($atts , $text=null){
  $atts['type'] = 'password';
  return template_input($atts, $text);
}
add_shortcode('password', 'template_password');

function template_checkbox($atts , $text=null){
  add_control($name, $atts, $text, 'checkbox');
  $atts['type'] = 'checkbox';
  $atts['__skip_value'] = true;
  $t = trim($text);
  if(!isset($atts['value'])) $atts['value'] = $t;
  $name = $atts['name'];
  $atts['name'].='[]';
  $rv = rval($name, false);
  if(isset($rv) && is_array($rv) && in_array($t, $rv)){
    $atts['checked'] = 'checked';
  }
  // echo 'CHECK: '.$atts['name'].' '; print_r(rval($name));
  return template_input($atts, $text);
}
add_shortcode('checkbox', 'template_checkbox');

function template_textarea($atts, $text=null){
  extract(sc_atts_for_env(array(
    'name'=>null,
    'class'=>null,
    'value'=>null,
  ), $atts));
  if(!$name) $name = trim($text);
  if(rval($name)) $value = rval($name);
  $css=programatic_classes($name);
  add_control($name, $atts, $text, 'textarea');
  $atts = atts_string($atts);
  return "
   <label class=\"$css $class\"><span class=\"text\">$text</span>
     <textarea name=\"$name\" $atts >$value</textarea>
   </label>";
}
add_shortcode('textarea', 'template_textarea');

function template_bool_radio($atts, $text=null){
  extract(sc_atts_for_env(array(
    'true_text'=>"Yes",
    'false_text'=>"No",
    'name'=>null,
    'value'=>null
  ), $atts));

  $yatt = "";
  $natt = "";
  if (!is_null(r_bool($name))) $value = r_bool($name);
  $value = to_bool($value);
  $ctl = &add_control($name, $atts, $text, 'bool_radio');
  if (!is_null($value) && $value){
    $yatt = " checked=\"checked\"";
    $ctl->value_label = $true_text;
  }
  else if (!is_null($value) && $value === false){
    $natt = " checked=\"checked\"";
    $ctl->value_label = $false_text;
  }
  $css=programatic_classes($name);
  if($text){
    $text = "<h5>$text</h5>";
  }
  return "<div class=\"bool holder $css\">$text
    <label><input type=\"radio\" value=\"1\" name=\"$name\" $yatt/>$true_text</label>
    <label><input type=\"radio\" value=\"0\" name=\"$name\" $natt/>$false_text</label>
    <div class=\"kill-float\"></div></div>";
}
add_shortcode('bool_radio', 'template_bool_radio');

function radio($str="", $text=null){
  echo template_radio(wp_parse_args( $str ), $text);
}

function template_radio($atts, $text=NULL){
  extract(sc_atts_for_env(array(
    'value'=>NULL,
    'name'=>null,
    'checked'=>false,
    'button_on_left'=> true,
    'class'=>null,
    'id'=>null,
  ), $atts));
  if(!$id) $id = $name;
  validate_field($name, $atts, $id);

  if (is_null($value) && $text )$value = trim($text);
  if (is_null($value) && $name )$value = trim($name);
  $checked = (!is_null($value) && rval($name) == $value) || (is_null(rval($name)) && $checked);
  if($checked){ $atts["checked"] = "checked"; }
  $atts["value"] = $value;
  if($id) $atts["id"] =  $id;
  if(!$text) $text = $value;
  $text = do_shortcode($text);
  $ctl = &add_control($name, $atts, null, 'radio');
  if($checked) $ctl->value_label = $text;
  $atts = atts_string($atts);
  $css = programatic_classes($id);
  $out ="<label class=\"radio-holder $class $css\">";
  if (!$button_on_left) $out.="<span class=\"text\">$text</span>";
  $out.="<input type=\"radio\" name=\"$name\" $atts/>";
  if ($button_on_left) $out.="<span class=\"text\">$text</span>";
  $out.="<div class=\"kill-float\"></div></label>";
  return $out;
}
add_shortcode('radio', 'template_radio');

function template_option($atts, $text=NULL){
  extract(sc_atts_for_env(array(
    'name'=>null,
    'value'=>NULL,
    'selected'=>false,
  ), $atts));
  $atts = atts_string($atts);
  if (is_null($value) && $text )$value = trim($text);
  $selected =(!is_null($value) && rval($name) == $value) || (is_null(rval($name)) && $selected);
  $atts .= " value=\"$value\"";
  if(!$text) $text = $value;
  if($selected) {
    add_control($name, $atts, $text, 'select');
    $atts .= " selected=\"selected\"";
  }
  $text = do_shortcode($text);
  $out ="<option $atts>$text</option>";
  return $out;
}
add_shortcode('option', 'template_option');


//This accepts a test which should be the name a single global variable,
// or a single function call
if ( !function_exists('template_if') ) {
  function template_if($atts , $text=null){
    $var = $atts['test'];
    $s = "\$val = $var;";
    // if this is a var ref declare it global;
    if (strpos($var, '$') !== false) $s = "global $var;$s";
    $val = null;
    eval($s);
    if($val) return do_shortcode($text);
    else return "";
  }
  add_shortcode('if', 'template_if');
}

// Is this a valid date?
function validDate($v){
  try{
    $d = new DateTime($v);
  } catch(Exception $e){return false;}
  return $d !== false;
}


// Validate an email address.
// Provide email address (raw input)
// Returns true if the email address has the email 
// address format and the domain exists.

function validEmail($email)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', 
	    str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}
/*  */

?>