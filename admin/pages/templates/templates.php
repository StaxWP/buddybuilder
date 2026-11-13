<?php
/**
 * Starter kits: sets of Elementor templates for every community page, imported in one click.
 * Variables: $templates (name, is_imported, url, image, demo, pro?).
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_licensed = buddy_builder()->has_pro_license();
?>
<p class="bpb-lead"><?php esc_html_e( 'Kits are ready-made Elementor templates for all your community pages. Importing a kit adds its templates; pick them for each page on Community pages.', 'stax-buddy-builder' ); ?></p>

<ul class="bpb-kits">
	<?php foreach ( $templates as $template ) : ?>
		<?php $bpb_is_pro = ! empty( $template['pro'] ); ?>
		<li class="bpb-kit">
			<img class="bpb-kit__image" src="<?php echo esc_url( $template['image'] ); ?>" alt="" loading="lazy">
			<div class="bpb-kit__body">
				<h2 class="bpb-kit__name">
					<?php echo esc_html( $template['name'] ); ?>
					<?php if ( $bpb_is_pro ) : ?>
						<span class="bpb-label"><?php esc_html_e( 'Pro', 'stax-buddy-builder' ); ?></span>
					<?php endif; ?>
					<?php if ( $template['is_imported'] ) : ?>
						<span class="bpb-status bpb-status--live"><?php esc_html_e( 'Imported', 'stax-buddy-builder' ); ?></span>
					<?php endif; ?>
				</h2>

				<?php if ( $bpb_is_pro && ! $bpb_licensed ) : ?>
					<p class="description"><?php esc_html_e( 'Needs an active BuddyBuilder Pro license.', 'stax-buddy-builder' ); ?></p>
				<?php endif; ?>

				<p class="bpb-kit__actions">
					<?php if ( ! $bpb_is_pro || $bpb_licensed ) : ?>
						<a href="<?php echo esc_url( $template['url'] ); ?>" class="button button-primary bpb-import-starter">
							<?php
							/* translators: %s: kit name */
							$bpb_aria = sprintf( $template['is_imported'] ? __( 'Import %s again', 'stax-buddy-builder' ) : __( 'Import %s', 'stax-buddy-builder' ), $template['name'] );
							?>
							<span aria-hidden="true"><?php echo esc_html( $template['is_imported'] ? __( 'Import again', 'stax-buddy-builder' ) : __( 'Import', 'stax-buddy-builder' ) ); ?></span>
							<span class="screen-reader-text"><?php echo esc_html( $bpb_aria ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $template['demo'] ) ) : ?>
						<a href="<?php echo esc_url( $template['demo'] ); ?>" class="button" target="_blank" rel="noopener">
							<?php esc_html_e( 'Demo', 'stax-buddy-builder' ); ?>
							<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: kit name */ __( 'of %s (opens in a new tab)', 'stax-buddy-builder' ), $template['name'] ) ); ?></span>
						</a>
					<?php endif; ?>
				</p>
			</div>
		</li>
	<?php endforeach; ?>
</ul>
