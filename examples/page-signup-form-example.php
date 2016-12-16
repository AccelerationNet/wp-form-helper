<?php
   /**
   * Template Name: Registration Template
   *
   * Add page meta-data:  
       signup-include to use a separate form file 
       signup-note to include special text in an email

    */
require_once('wp-form-helper/form_helper.php');
require_once('signups.php');
wp_enqueue_script('signups.js', get_stylesheet_directory_uri().'/signups.js',
                  Array('jquery'));


global $TZ;
$TZ = new DateTimeZone('America/New_York');

function golf_number_check(){
  $val = rval('two-or-four');
  $valid = true;
  if(!$val) $val=2;
  for($i=1 ; $i <=$val ; $i+=1){
    $n = 'golfer'.$i;
    if(!rval($n)){
      mark_invalid($n, 'Please fill in a name for Golfer '.$i);
      $valid=false;
    } 
  }
  return $valid;
}

function beforeDate($datestring){
  global $TZ;
  $d = new DateTime("now", $TZ);
  $target = new DateTime($datestring, $TZ);

  $diff = $d->diff($target);
  //echo $d->format('Y-m-d H:i:s ').$d->getTimezone()->getName()."-".$target->format('Y-m-d H:i:s ').$target->getTimezone()->getName();
  return ! $diff->invert;
}

function signup_email_note(){
  $signup_note = get_post_meta(get_the_ID(), 'signup-note', true);
  if(!$signup_note){
    $signup_note  = <<<EOT

  <p>Thank you for your registration.</p>

EOT;
  }
  return $signup_note;
}



if ( have_posts() ) while ( have_posts() ) : the_post();
$content = get_the_content();
$include = get_post_meta(get_the_ID(), 'signup-include', true);
$signup_note = get_post_meta(get_the_ID(), 'signup-note', true);
if($include){
  $pthcontent = file_get_contents(get_stylesheet_directory().'/'.$include,
                    FILE_USE_INCLUDE_PATH);
  $content .= $pthcontent;
}

// Builds our form model, handles validation and custom classes
// Allows us to validate before rendering
$json_data = load_signup_data();
wp_preprocess_content($content, 'content');

   // if this is a valid form submission
   if (is_postback() && valid_page()){
     $payment_key = randstring();

     $inserted = rval('signup_id') && isSignupAdmin() ? 0 : 1;

     if(valid_page())
       $data = insert_signup($payment_key); // insert the signup into the database

     if(valid_page() && $inserted) // dont send out emails for edits
       signup_email($data); // Send out any emails associated with this

     // if we didnt have any errors to report, move on to the next step
     // (either thank you or payments)
     if(valid_page()){
       // go back to admin if needed
       if(isSignupAdmin() && !$inserted)
         header('Location: /wp-admin/admin.php?page=dbte_signups');
       else redirect_to_next_step($payment_key);
       die();
     }

   }
   get_header();
   echo $json_data;
// next is standard content display
?>
<div id="container">
  <div id="content" role="main">
    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
      <?php if ( is_front_page() ) { ?>
      <h2 class="entry-title"><?php the_title(); ?></h2>
      <?php } else { ?>
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php } ?>

      <div class="entry-content">
        <form method="post" class="ejcba-form" action="">
        <?php
        // print any validation errors
        print_validation_errors();
        // render the form content
        wp_include();
        ?>
	<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
	  <?php edit_post_link( __( 'Edit', 'twentyten' ), '<span class="edit-link">', '</span>' ); ?>
          <textarea name="signup_data" style="position:absolute; left:-500px; width:200px;"></textarea>
        </form>
      </div><!-- .entry-content -->
    </div><!-- #post-## -->
  </div><!-- #content -->
</div><!-- #container -->

<?php endwhile; ?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
