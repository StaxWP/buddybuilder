<h2 class="ste-my-0 ste-leading-none ste-text-2xl ste-text-gray-900 ste-font-bold ste-tracking-wide">
	<?php esc_html_e( 'Templates Settings', 'stax-buddy-builder' ); ?>
</h2>

<div class="ste-text-sm ste-text-gray-7700 ste-mt-2">
	<?php esc_html_e( 'You can override the BuddyPress\' default templates with the ones you\'ve created using Elementor using the form below.', 'stax-buddy-builder' ); ?>
</div>

<div class="ste-mt-5">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" enctype="multipart/form-data">
		<div class="ste-grid ste-grid-cols-1 md:ste-grid-cols-2 ste-gap-8">

			<?php foreach ( $data as $slug => $setting ) : ?>
				<div>
					<?php if ( isset( $setting['inner'] ) ) : ?>
						<?php foreach ( $setting['inner'] as $key => $item ) : ?>
							<?php
							$main_class    = 'ste-rounded ste-p-4 ste-bg-gradient-to-r ste-from-ash-300 ste-to-ash-200 ste-shadow';
							$inner_1_class = '';
							$inner_2_class = '';

							if ( isset( $item['pro'] ) && $item['pro'] ) {
								$main_class    = '';
								$inner_1_class = 'ste-flex';
								$inner_2_class = 'ste-rounded ste-w-11/12 ste-p-4 ste-bg-gradient-to-r ste-from-ash-300 ste-to-ash-200 ste-mt-2 ste-shadow';
							}
							?>
							<div class="<?php echo esc_attr( $main_class ); ?>">
								<div class="ste-mb-4 last:ste-mb-0 <?php echo esc_attr( $inner_1_class ); ?>">
									<?php if ( isset( $item['pro'] ) && $item['pro'] ) : ?>
										<div class="ste-w-1/12 ste-text-ash-400 ste-p-5 ">
											<svg class="ste-w-full ste-fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 126.92 81.36"><title>214651_arrow_enter_icon</title><polygon points="0 0 0 67.51 15 67.51 15 67.5 95.97 67.5 95.97 81.36 111.45 70.68 126.92 60 111.45 49.31 95.97 38.63 95.97 52.49 15 52.49 15 0 0 0"/></svg>
										</div>
									<?php endif; ?>
									<div class="<?php echo esc_attr( $inner_2_class ); ?>">
										<div class="ste-mb-3 ste-flex ste-items-center">
											<?php if ( isset( $item['pro'] ) && $item['pro'] ) : ?>
												<i class="eicon-pro-icon ste-pro-icon ste-mr-2"></i>
											<?php endif; ?>
											<label class="ste-inline-block ste-text-ash-900 ste-text-md ste-font-bold">
												<?php echo $item['title']; ?>
											</label>
										</div>
										<?php include 'parts/settings-box.php'; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="ste-p-4 ste-rounded ste-bg-gradient-to-r ste-from-ash-300 ste-to-ash-200 ste-shadow">
							<div class="ste-mb-3 ste-flex ste-items-center">
								<?php if ( isset( $setting['pro'] ) && $setting['pro'] ) : ?>
									<i class="eicon-pro-icon ste-pro-icon ste-mr-2"></i>
								<?php endif; ?>
								<label for="<?php echo $slug; ?>"
										class="ste-inline-block ste-w-full ste-text-ash-900 ste-text-md ste-font-bold">
									<?php echo $setting['title']; ?>
								</label>
							</div>

							<?php
							$item = $setting;
							$key  = $slug;
							include 'parts/settings-box.php';
							?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			
		</div>

		<input type="hidden" name="action" value="bpb_settings">
        <?php wp_nonce_field( 'bpb_settings' ); ?>


        <div class="ste-mt-5">
			<button type="submit"
					class="ste-bg-gradient-to-r ste-from-green-500 ste-to-green-400 ste-text-md ste-text-white ste-py-3 ste-px-6 ste-rounded ste-border-0 ste-shadow-xl hover:ste-shadow-lg ste-cursor-pointer">
				<span class="ste-flex ste-items-center">
					<svg class="ste-fill-current" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z"/></svg>
					<span class="ste-leading-none ste-font-bold ste-ml-2 ste-uppercase"><?php esc_html_e( 'Save', 'stax-buddy-builder' ); ?></span>
				</span>
			</button>
		</div>
	</form>
</div>
