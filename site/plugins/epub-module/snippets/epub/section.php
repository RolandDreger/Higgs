<section epub:type="bodymatter chapter" role="doc-chapter">
	<header>
		<h1 epub:type="title"><?= $section->title(); ?></h1>
	</header>
	<p><?= esc($section->text(), 'html'); ?></p>
</section>