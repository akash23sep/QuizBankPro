<?php
if ( ! defined( "ABSPATH" ) ) exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'QuizBank Pro Dashboard', 'quizbank-pro' ); ?></h1>
	<?php
	qbp_render_admin_tabs( 'qbp-quiz-topics' );
	settings_errors( 'qbp_messages' );
	?>

	<div id="col-container" class="wp-clearfix" style="margin-top: 20px;">

		<!-- LEFT COLUMN: Dynamic Context Switch Form -->
		<div id="col-left" style="width: 35%; float: left;">
			<div class="form-wrap">
				<?php if ( $is_edit_mode ) : ?>
					<h2><?php esc_html_e( 'Edit Quiz Topic', 'quizbank-pro' ); ?></h2>
					<form method="POST" action="">
						<?php wp_nonce_field( 'qbp_quiz_topic_form_verify', 'qbp_quiz_topic_nonce' ); ?>
						<input type="hidden" name="qbp_quiz_topic_action" value="edit_quiz_topic">
						<input type="hidden" name="target_quiz_topic_id" value="<?php echo esc_attr( $target_quiz_topic_id ); ?>">

						<div class="form-field form-required">
							<label for="quiz_topic_name"><?php esc_html_e( 'Quiz Topic Name', 'quizbank-pro' ); ?></label>
							<input type="text" name="quiz_topic_name" id="quiz_topic_name" value="<?php echo esc_attr( $edit_quiz_topic_name ); ?>" required style="max-width: 100%;">
						</div>

						<div style="margin-top: 15px;">
							<?php submit_button( __( 'Update Quiz Topic', 'quizbank-pro' ), 'primary', 'submit', false ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=qbp-quiz-topics' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'quizbank-pro' ); ?></a>
						</div>
					</form>
				<?php else : ?>
					<h2><?php esc_html_e( 'Add New Quiz Topic', 'quizbank-pro' ); ?></h2>
					<form method="POST" action="">
						<?php wp_nonce_field( 'qbp_quiz_topic_form_verify', 'qbp_quiz_topic_nonce' ); ?>
						<input type="hidden" name="qbp_quiz_topic_action" value="add_quiz_topic">

						<div class="form-field form-required">
							<label for="quiz_topic_name"><?php esc_html_e( 'Quiz Topic Name', 'quizbank-pro' ); ?></label>
							<input type="text" name="quiz_topic_name" id="quiz_topic_name" required style="max-width: 100%;">
						</div>

						<?php submit_button( __( 'Save Quiz Topic', 'quizbank-pro' ) ); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<!-- RIGHT COLUMN: Inventory with Row Actions Buttons -->
		<div id="col-right" style="width: 62%; float: right;">
			<table class="wp-list-table widefat fixed striped table-view-list">
				<thead>
					<tr>
						<th scope="col" style="width: 15%;"><?php esc_html_e( 'ID', 'quizbank-pro' ); ?></th>
						<th scope="col" style="width: 55%;"><?php esc_html_e( 'Name', 'quizbank-pro' ); ?></th>
						<th scope="col" style="width: 30%;"><?php esc_html_e( 'Created (EST)', 'quizbank-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $quiz_topics ) ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'No quiz topics found.', 'quizbank-pro' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $quiz_topics as $quiz_topic ) :
							$delete_nonce = wp_create_nonce( 'qbp_delete_quiz_topic_' . $quiz_topic->id );
							$edit_url     = admin_url( 'admin.php?page=qbp-quiz-topics&action=edit&quiz_topic_id=' . $quiz_topic->id );
							$delete_url   = admin_url( 'admin.php?page=qbp-quiz-topics&action=delete&quiz_topic_id=' . $quiz_topic->id . '&_wpnonce=' . $delete_nonce );
							?>
							<tr class="qbp-row-hover">
								<td><strong><?php echo esc_html( $quiz_topic->id ); ?></strong></td>
								<td>
									<div style="font-weight: 600; color: #23282d; font-size:14px;"><?php echo esc_html( $quiz_topic->quiz_topic_name ); ?></div>
									<div class="row-actions" style="font-size:12px; padding:2px 0 0 0;">
										<span class="edit">
											<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'quizbank-pro' ); ?></a> |
										</span>
										<span class="trash">
											<a href="<?php echo esc_url( $delete_url ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php echo esc_js( __( 'Are you absolutely sure you want to delete this quiz topic?', 'quizbank-pro' ) ); ?>');">
												<?php esc_html_e( 'Delete', 'quizbank-pro' ); ?>
											</a>
										</span>
									</div>
								</td>
								<td>
									<?php
									try {
										$utc_time = new DateTimeImmutable( $quiz_topic->created_at, new DateTimeZone( 'UTC' ) );
										$est_time = $utc_time->setTimezone( new DateTimeZone( 'America/New_York' ) );
										echo esc_html( $est_time->format( 'F j, Y g:i a' ) );
									} catch ( Exception $e ) {
										echo esc_html( $quiz_topic->created_at );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>