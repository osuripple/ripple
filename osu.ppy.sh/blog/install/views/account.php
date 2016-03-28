<?php echo $header; ?>

<section class="content">

	<article>
		<h1>Checking Yer Privileges</h1>

		<p>Oh, we're so tantalisingly close! All we need now is to make sure you're actually a Ripple admin that can finish installing.</p>
	</article>

	<form method="post" action="<?php echo uri_to('account'); ?>" autocomplete="off">
		<?php echo $messages; ?>

		<fieldset>
			<p>
				<label for="username">Username</label>
				<i>Your ripple username.</i>
				<input tabindex="1" id="username" name="username" value="<?php echo Input::previous('username', 'admin'); ?>">
			</p>

			<p>
				<label>Password</label>
				<i>Guess what, it's your Ripple password!</i>
				<input tabindex="3" name="password" type="password" value="<?php echo Input::previous('password'); ?>">
			</p>
		</fieldset>

		<section class="options">
			<a href="<?php echo uri_to('metadata'); ?>" class="btn quiet">&laquo; Back</a>
			<button type="submit" class="btn">Complete</button>
		</section>
	</form>
</section>

<?php echo $footer; ?>