<?php
get_header();
?>
<main class="site-main" id="main-content">
<?php
while ( have_posts() ) {
	the_post();
	?>
	<section class="section">
		<h1 class="display-title"><?php the_title(); ?></h1>
		<?php the_content(); ?>
	</section>
	<?php
}
?>
</main>
<?php
get_footer();
