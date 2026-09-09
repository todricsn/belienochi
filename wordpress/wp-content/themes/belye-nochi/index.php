<?php
get_header();
?>
<main class="site-main" id="main-content">
<?php
while ( have_posts() ) {
	the_post();
	?>
	<article class="section">
		<h1 class="display-title"><?php the_title(); ?></h1>
		<?php the_content(); ?>
	</article>
	<?php
}
?>
</main>
<?php
get_footer();
