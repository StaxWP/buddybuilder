<h2 class="ste-my-0 ste-leading-none ste-text-2xl ste-text-gray-900 ste-font-bold ste-tracking-wide">
	<?php esc_html_e( 'Templates', 'stax-buddy-builder' ); ?>
</h2>

<div class="ste-text-sm ste-text-gray-7700 ste-mt-2">
	<?php esc_html_e( 'Get our ready made templates for an awesome community website.', 'stax-buddy-builder' ); ?>
</div>

<div class="ste-mt-5">
	<div class="ste-grid ste-grid-cols-1 md:ste-grid-cols-2 lg:ste-grid-cols-3 ste-gap-4">
		<?php foreach ( $templates as $template ) : ?>
			<div class="ste-rounded ste-shadow hover:ste-shadow-lg ste-overflow-hidden">
				<div class="ste-aspect-w-16 ste-aspect-h-9">
					<img class="ste-w-full ste-object-cover" src="<?php echo esc_url( $template['image'] ); ?>">
				</div>
				<div class="ste-bg-ash-100 ste-p-4">
					<div class="ste-text-center ste-mb-8 ste-mt-2">
						<h3 class="ste-m-0 ste-text-gray-800">
							<?php echo esc_html( $template['name'] ); ?>
						</h3>
					</div>

					<?php if ( ! buddy_builder()->has_pro_license() && isset( $template['pro'] ) && $template['pro'] ) : ?>
						<div class="ste-text-center ste-text-xs ste-my-4 ste-px-3 ste-text-neutral-500">
							<?php
                            esc_html_e( 'Please add your license key in order to be able to import this template.',
                                'stax-buddy-builder'
                            ); ?>
						</div>
					<?php endif; ?>

					<div class="ste-text-center">
						<?php
						if ( $template['is_imported'] ) {
							$text = esc_html__( 'Re-Import', 'stax-buddy-builder' );
						} else {
							$text = esc_html__( 'Import', 'stax-buddy-builder' );
						}
						?>

						<div class="ste-grid ste-grid-cols-2 ste-gap-2">
							<a href="<?php echo esc_url( $template['url'] ); ?>"
								class="bpb-import-starter ste-flex ste-items-center ste-justify-center ste-no-underline ste-bg-gradient-to-r ste-from-green-500 ste-to-green-400 ste-text-md ste-text-white ste-text-xs hover:ste-text-white ste-font-bold ste-py-3 ste-px-4 ste-rounded">
								<i class="eicon-download-bold ste-mr-2"></i>
								<?php echo $text; ?>
							</a>

							<?php if ( isset( $template['demo'] ) && $template['demo'] ) : ?>
								<a href="<?php echo esc_url( $template['demo'] ); ?>"
									target="_blank"             
									class="ste-flex ste-items-center ste-justify-center ste-no-underline ste-bg-gradient-to-r ste-from-neutral-300 ste-to-neutral-200 ste-text-md ste-text-neutral-700 ste-text-xs hover:ste-text-neutral-700 ste-font-bold ste-py-3 ste-px-4 ste-rounded">
									<i class="eicon-preview-medium ste-mr-2"></i>
									<?php esc_html_e( 'Demo', 'stax-buddy-builder' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
