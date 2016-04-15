<?php echo $header; ?>

<section class="content">
	<article>
		<h1>Your database details</h1>

		<p>Firstly, we’ll need a database. Anchor needs them to store all of your blog’s information, so it’s vital you fill these in correctly. If you don’t know what these are, you’ll need to contact your webhost.</p>
	</article>

	<form method="post" action="<?php echo uri_to('database'); ?>" autocomplete="off">
		<?php echo $messages; ?>

		<fieldset>
			<p>
				<label for="prefix">Table Prefix</label>
				<input id="prefix" name="prefix" value="<?php echo Input::previous('prefix', 'anchor_'); ?>">

				<i>Database table name prefix.</i>
			</p>

			<p>
				<label for="collation">Collation</label>
				<select id="collation" class="chosen-select" name="collation">
					<?php foreach ($collations as $code => $collation): ?>
					<?php $selected = ($code == Input::previous('collation', 'utf8_unicode_ci')) ? ' selected' : ''; ?>
					<option value="<?php echo $code; ?>" <?php echo $selected; ?>>
						<?php echo $code; ?>
					</option>
					<?php
endforeach; ?>
				</select>

				<i>Change if <b>utf8_general_ci</b> doesn’t work.</i>
			</p>
		</fieldset>

		<section class="options">
			<a href="<?php echo uri_to('start'); ?>" class="btn quiet">&laquo; Back</a>
			<button type="submit" class="btn">Next Step &raquo;</button>
		</section>
	</form>
</section>

<?php echo $footer; ?>
