<?php
   /**
   * Template Name: Atlanta 2015 Registration Template
   * The template for displaying the Atlanta 2015 event registration
   *
   * This is the template that displays all pages by default.
   * Please note that this is the WordPress construct of pages
   * and that other 'pages' on your WordPress site will use a
   * different template.
   *
   * @package WordPress
   * @subpackage Twenty_Ten
   * @since Twenty Ten 1.0
   */
   require_once('wp-form-helper/form_helper.php');

global $TZ;
$TZ = new DateTimeZone('America/New_York');

function beforeDate($datestring){
  global $TZ;
  $d = new DateTime("now", $TZ);
  $target = new DateTime($datestring, $TZ);

  $diff = $d->diff($target);
  //echo $d->format('Y-m-d H:i:s ').$d->getTimezone()->getName()."-".$target->format('Y-m-d H:i:s ').$target->getTimezone()->getName();
  return ! $diff->invert;
}

function signup_email_note(){
  $o =<<<EOT

  <p>Thank you for your registration.</p>

EOT;
  return $o;
}

require_once('signups.php');

// Builds our form model, handles validation and custom classes
// Allows us to validate before rendering
wp_preprocess(get_stylesheet_directory().'/atlanta2015.wp');

   // if this is a valid form submission
   if (is_postback() && valid_page()){
     $payment_key = randstring();
     insert_signup(); // insert the signup into the database
     signup_email(); // Send out any emails associated with this

     // if we didnt have any errors to report, move on to the next step
     // (either thank you or payments)
     if(valid_page()) redirect_to_next_step($payment_key);
   }
   get_header();

// next is standard content display
?>
<div id="container">
  <div id="content" role="main">

    <?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
      <?php if ( is_front_page() ) { ?>
      <h2 class="entry-title"><?php the_title(); ?></h2>
      <?php } else { ?>
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php } ?>

      <div class="entry-content">
         
	<?php the_content(); ?>
	<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
	<?php edit_post_link( __( 'Edit', 'twentyten' ), '<span class="edit-link">', '</span>' ); ?>
      </div><!-- .entry-content -->
    </div><!-- #post-## -->

    <?php comments_template( '', true ); ?>

    <?php endwhile; ?>

    <?php
       // print any validation errors        
       print_validation_errors();
       // render the form content
       wp_include(get_stylesheet_directory().'/form.wp');
       ?>


  </div><!-- #content -->
</div><!-- #container -->



<?php get_sidebar(); ?>
<?php get_footer(); ?>
