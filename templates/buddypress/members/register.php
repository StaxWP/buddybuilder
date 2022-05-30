<?php
/**
 * BuddyPress - Members/Blogs Registration forms
 *
 * @since 3.0.0
 * @version 4.0.0
 */

$register_page = bpb_get_shortcode_str( 'register-page' );
$render        = bpb_is_template_populated( 'register-page' );

?>
	<?php bp_nouveau_signup_hook( 'before', 'page' ); ?>
	
		<div id="register-page"class="page register-page">

				<?php if ( ! $render ) : ?>
					<?php bp_nouveau_user_feedback( bp_get_current_signup_step() ); ?>
				<?php endif; ?>

				<form action="" name="signup_form" id="signup-form" class="standard-form signup-form clearfix" method="post" enctype="multipart/form-data">

					<?php if ( $render ) : ?>
						<?php echo do_shortcode( $register_page ); ?>
					<?php else : ?>

							<div class="layout-wrap">

								<?php if ( 'request-details' === bp_get_current_signup_step() ) : ?>

									<?php bp_nouveau_signup_hook( 'before', 'account_details' ); ?>

								<div class="register-section default-profile" id="basic-details-section">

									<?php /***** Basic Account Details ******/ ?>

									<h2 class="bp-heading"><?php esc_html_e( 'Account Details', 'buddypress' ); ?></h2>

									<?php bp_nouveau_signup_form(); ?>

								</div>

									<?php bp_nouveau_signup_hook( 'after', 'account_details' ); ?>

									<?php /***** Extra Profile Details ******/ ?>

									<?php
									if ( version_compare( buddypress()->version, '8.0.0', '<' ) ) {
										$has_xprofile_check = bp_nouveau_base_account_has_xprofile();
									} else {
										$has_xprofile_check = bp_nouveau_has_signup_xprofile_fields( true );
									}

									if ( bp_is_active( 'xprofile' ) && $has_xprofile_check ) :
										?>

										<?php bp_nouveau_signup_hook( 'before', 'signup_profile' ); ?>

									<div class="register-section extended-profile" id="profile-details-section">

										<h2 class="bp-heading"><?php esc_html_e( 'Profile Details', 'buddypress' ); ?></h2>

										<?php /* Use the profile field loop to render input fields for the 'base' profile field group */ ?>
										<?php
										while ( bp_profile_groups() ) :
											bp_the_profile_group();
											?>

											<?php
											while ( bp_profile_fields() ) :
												bp_the_profile_field();
												?>

												<div<?php bp_field_css_class( 'editfield' ); ?>>
													<fieldset>

													<?php
													$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
													$field_type->edit_field_html();

													bp_nouveau_xprofile_edit_visibilty();
													?>

													</fieldset>
												</div>

											<?php endwhile; ?>

										<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

										<?php endwhile; ?>

										<?php bp_nouveau_signup_hook( '', 'signup_profile' ); ?>

									</div>

										<?php bp_nouveau_signup_hook( 'after', 'signup_profile' ); ?>

								<?php endif; ?>

									<?php if ( bp_get_blog_signup_allowed() ) : ?>

										<?php bp_nouveau_signup_hook( 'before', 'blog_details' ); ?>

										<?php /***** Blog Creation Details ******/ ?>

									<div class="register-section blog-details" id="blog-details-section">

										<h2><?php esc_html_e( 'Site Details', 'buddypress' ); ?></h2>

										<p><label for="signup_with_blog"><input type="checkbox" name="signup_with_blog" id="signup_with_blog" value="1" <?php checked( (int) bp_get_signup_with_blog_value(), 1 ); ?>/> <?php esc_html_e( "Yes, i'd like to create a new site", 'buddypress' ); ?></label></p>

										<div id="blog-details"
										<?php
										if ( (int) bp_get_signup_with_blog_value() ) :
											?>
											class="show"<?php endif; ?>>

											<?php bp_nouveau_signup_form( 'blog_details' ); ?>

										</div>

									</div>

										<?php bp_nouveau_signup_hook( 'after', 'blog_details' ); ?>

								<?php endif; ?>

							<?php endif; // request-details signup step ?>

						</div>

						<?php bp_nouveau_signup_hook( 'custom', 'steps' ); ?>

						<?php if ( 'request-details' === bp_get_current_signup_step() ) : ?>

							<?php if ( bp_signup_requires_privacy_policy_acceptance() ) : ?>
								<?php bp_nouveau_signup_privacy_policy_acceptance_section(); ?>
							<?php endif; ?>

							<?php bp_nouveau_submit_button( 'register' ); ?>

						<?php endif; ?>
					<?php endif; ?>

				</form>

		</div>
	
	<?php bp_nouveau_signup_hook( 'after', 'page' ); ?>
