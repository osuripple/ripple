<?php echo $header; ?>

<hgroup class="wrap">
	<h1><?php echo __('users.users'); ?></h1>
</hgroup>

<section class="wrap">
	<?php echo $messages; ?>

	<ul class="list">
		<?php foreach ($users->results as $user): ?>
		<li>
			<a href="#">
				<strong><?php echo $user->username; ?></strong>

				<em class="highlight"><?php echo rank_to_str($user->rank); ?></em>
			</a>
		</li>
		<?php
endforeach; ?>
	</ul>

	<aside class="paging"><?php echo $users->links(); ?></aside>
</section>

<?php echo $footer; ?>
