<?php
get_header();
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
get_footer();
