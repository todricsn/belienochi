<?php
/**
 * Template Name: Номера
 * Template Post Type: page
 */

get_header( 'rooms' );
?>
<main class="site-main" id="main-content">
<?php
while ( have_posts() ) {
	the_post();
	the_content();
}
?>
</main>
<?php
get_footer( 'rooms' );
