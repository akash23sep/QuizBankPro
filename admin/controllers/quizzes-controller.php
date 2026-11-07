<?php
if ( ! defined( "ABSPATH" ) ) exit;

add_action( 'admin_init', 'qbp_process_quiz_form' );
add_action( 'admin_init', 'qbp_process_quiz_delete' );
add_action( 'wp_ajax_qbp_sort_quizzes', 'qbp_ajax_sort_quizzes' );

function qbp_get_quizzes_sort_args( $orderby = 'id', $order = 'desc' ) {
	$allowed_orderby = array(
		'id'           => 'q.id',
		'topic'        => 'qt.quiz_topic_name',
		'layout_style' => 'q.layout_style',
		'questions'    => 'COUNT(m.question_id)',
	);

	$orderby        = sanitize_key( $orderby );
	$order          = sanitize_key( $order );
	$is_valid_sort  = isset( $allowed_orderby[ $orderby ] );
	$is_valid_order = in_array( $order, array( 'asc', 'desc' ), true );

	if ( ! $is_valid_sort || ! $is_valid_order ) {
		$orderby = 'id';
		$order   = 'desc';
	}

	return array(
		'orderby' => $orderby,
		'order'   => $order,
		'column'  => $allowed_orderby[ $orderby ],
	);
}

function qbp_get_quizzes_for_display( $orderby = 'id', $order = 'desc' ) {
	global $wpdb;

	$sort              = qbp_get_quizzes_sort_args( $orderby, $order );
	$quizzes_table     = $wpdb->prefix . 'qbp_quizzes';
	$quiz_topics_table = $wpdb->prefix . 'qbp_quiz_topics';
	$mapping_table     = $wpdb->prefix . 'qbp_quiz_question_mapping';
	$order_sql         = strtoupper( $sort['order'] );
	$secondary_order   = 'q.id' === $sort['column'] ? '' : ', q.id DESC';

	return $wpdb->get_results(
		"SELECT q.id, q.title, q.layout_style, q.quiz_image, q.created_at, qt.quiz_topic_name, COUNT(m.question_id) AS total_questions
		FROM {$quizzes_table} q
		LEFT JOIN {$quiz_topics_table} qt ON q.quiz_topic_id = qt.id
		LEFT JOIN {$mapping_table} m ON q.id = m.quiz_id
		GROUP BY q.id, q.title, q.layout_style, q.quiz_image, q.created_at, qt.quiz_topic_name
		ORDER BY {$sort['column']} {$order_sql}{$secondary_order}"
	);
}

function qbp_render_quizzes_table_rows( $quizzes ) {
	ob_start();
	?>
	<?php if ( empty( $quizzes ) ) : ?>
		<tr>
			<td colspan="6" class="colspanchange"><?php esc_html_e( 'No quizzes defined yet. Create your first quiz on the left.', 'quizbank-pro' ); ?></td>
		</tr>
	<?php else : ?>
		<?php foreach ( $quizzes as $quiz ) : ?>
			<?php
			$quiz_id      = absint( $quiz->id );
			$delete_nonce = wp_create_nonce( 'qbp_delete_quiz_' . $quiz_id );
			$edit_url     = admin_url( 'admin.php?page=quizbank-pro&action=edit&quiz_id=' . $quiz_id );
			$mapping_url  = admin_url( 'admin.php?page=quizbank-pro&manage_mapping=' . $quiz_id );
			$delete_url   = admin_url( 'admin.php?page=quizbank-pro&action=delete&quiz_id=' . $quiz_id . '&_wpnonce=' . $delete_nonce );
			?>
			<tr class="qbp-row-hover">
				<td><strong><?php echo esc_html( $quiz_id ); ?></strong></td>
				<td style="text-align:center; vertical-align:middle;">
					<?php if ( ! empty( $quiz->quiz_image ) ) : ?>
						<img src="<?php echo esc_url( $quiz->quiz_image ); ?>" class="qbp-table-thumbnail" alt="<?php echo esc_attr( $quiz->title ); ?>">
					<?php else : ?>
						<span class="qbp-thumbnail-placeholder dashicons dashicons-format-image" title="<?php esc_attr_e( 'No Image', 'quizbank-pro' ); ?>"></span>
					<?php endif; ?>
				</td>
				<td>
					<div class="row-title" style="font-weight: 600; color: #2271b1; font-size:14px;"><?php echo esc_html( $quiz->title ); ?></div>
					<div class="row-actions" style="font-size: 12px; padding: 2px 0 0 0;">
						<span class="edit">
							<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'quizbank-pro' ); ?></a> |
						</span>
						<span class="edit">
							<a href="<?php echo esc_url( $mapping_url ); ?>" title="<?php esc_attr_e( 'Assign questions to this quiz', 'quizbank-pro' ); ?>">
								<?php esc_html_e( 'Manage Questions Assignment', 'quizbank-pro' ); ?>
							</a> |
						</span>
						<span class="trash">
							<a href="<?php echo esc_url( $delete_url ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php echo esc_js( __( 'Are you absolutely sure you want to delete this quiz?', 'quizbank-pro' ) ); ?>');">
								<?php esc_html_e( 'Delete', 'quizbank-pro' ); ?>
							</a>
						</span>
					</div>
				</td>
				<td>
					<?php if ( ! empty( $quiz->quiz_topic_name ) ) : ?>
						<span class="qbp-topic-badge" style="display:inline-block; background:#f0f6fc; color:#135e96; border:1px solid #c5d9ed; border-radius:999px; padding:3px 9px; font-size:11px; font-weight:600; line-height:1.4;">
							<?php echo esc_html( $quiz->quiz_topic_name ); ?>
						</span>
					<?php else : ?>
						<span class="qbp-topic-badge qbp-topic-badge-unassigned" style="display:inline-block; background:#f6f7f7; color:#646970; border:1px solid #dcdcde; border-radius:999px; padding:3px 9px; font-size:11px; font-weight:600; line-height:1.4;">
							<?php esc_html_e( 'Unassigned', 'quizbank-pro' ); ?>
						</span>
					<?php endif; ?>
				</td>
				<td>
					<code><?php echo esc_html( $quiz->layout_style ); ?></code>
				</td>
				<td style="text-align: center;">
					<span class="count-badge" style="background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 10px; font-weight: 600; font-size: 11px;">
						<?php echo esc_html( absint( $quiz->total_questions ) ); ?>
					</span>
				</td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}

function qbp_ajax_sort_quizzes() {
	check_ajax_referer( 'qbp_sort_quizzes', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Unauthorized access.', 'quizbank-pro' ) ),
			403
		);
	}

	$orderby = isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( (string) $_POST['orderby'] ) ) : 'id';
	$order   = isset( $_POST['order'] ) ? sanitize_key( wp_unslash( (string) $_POST['order'] ) ) : 'desc';
	$sort    = qbp_get_quizzes_sort_args( $orderby, $order );
	$quizzes = qbp_get_quizzes_for_display( $sort['orderby'], $sort['order'] );

	wp_send_json_success(
		array(
			'rows'    => qbp_render_quizzes_table_rows( $quizzes ),
			'orderby' => $sort['orderby'],
			'order'   => $sort['order'],
		)
	);
}

function qbp_process_quiz_form() {
	if ( ! isset( $_POST['qbp_quiz_action'] ) || ! in_array( $_POST['qbp_quiz_action'], array( 'add_quiz', 'edit_quiz' ), true ) ) {
		return;
	}

	$quiz_action = sanitize_key( wp_unslash( $_POST['qbp_quiz_action'] ) );
	$nonce_action = 'edit_quiz' === $quiz_action ? 'qbp_edit_quiz_verify' : 'qbp_add_quiz_verify';

	if ( ! isset( $_POST['qbp_quiz_nonce'] ) || ! wp_verify_nonce( $_POST['qbp_quiz_nonce'], $nonce_action ) ) {
		wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
	}

	$target_quiz_id = isset( $_POST['target_quiz_id'] ) ? absint( $_POST['target_quiz_id'] ) : 0;
	$title         = isset( $_POST['quiz_title'] ) ? sanitize_text_field( wp_unslash( $_POST['quiz_title'] ) ) : '';
	$quiz_topic_id = isset( $_POST['quiz_topic_id'] ) ? absint( $_POST['quiz_topic_id'] ) : 0;
	$layout_style = isset( $_POST['layout_style'] ) ? sanitize_key( wp_unslash( $_POST['layout_style'] ) ) : 'paged';
	$allowed_layout_styles = array( 'paged', 'single_scroll' );

	if ( ! in_array( $layout_style, $allowed_layout_styles, true ) ) {
		$layout_style = 'paged';
	}

	if ( empty( $title ) || empty( $quiz_topic_id ) ) {
		add_settings_error( 'qbp_messages', 'qbp_quiz_empty', __( 'Quiz title and topic are required.', 'quizbank-pro' ), 'error' );
		return;
	}

	if ( mb_strlen( $title, 'UTF-8' ) > 255 ) {
		add_settings_error( 'qbp_messages', 'qbp_quiz_title_too_long', __( 'Quiz title cannot exceed 255 characters.', 'quizbank-pro' ), 'error' );
		return;
	}

	global $wpdb;
	$table_name        = $wpdb->prefix . 'qbp_quizzes';
	$table_quiz_topics = $wpdb->prefix . 'qbp_quiz_topics';
	$topic_exists      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_quiz_topics} WHERE id = %d AND is_active = 1", $quiz_topic_id ) );

	if ( ! $topic_exists ) {
		add_settings_error( 'qbp_messages', 'qbp_quiz_topic_invalid', __( 'Please select a valid active quiz topic.', 'quizbank-pro' ), 'error' );
		return;
	}

	$existing_quiz = null;
	if ( 'edit_quiz' === $quiz_action ) {
		if ( empty( $target_quiz_id ) ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_missing', __( 'Unable to identify the quiz to update.', 'quizbank-pro' ), 'error' );
			return;
		}

		$existing_quiz = $wpdb->get_row( $wpdb->prepare( "SELECT id, quiz_image FROM {$table_name} WHERE id = %d", $target_quiz_id ) );
		if ( ! $existing_quiz ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_not_found', __( 'Quiz not found.', 'quizbank-pro' ), 'error' );
			return;
		}
	}

	// Safe WordPress Media Upload Handling for quiz_image
	$quiz_image_url = $existing_quiz ? $existing_quiz->quiz_image : null;
	$remove_quiz_image = ! empty( $_POST['remove_quiz_image'] );

	if ( $remove_quiz_image ) {
		$quiz_image_url = null;
	}

	if ( ! empty( $_FILES['quiz_image']['name'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// Upload file to the standard WordPress uploads folder and add it to media attachments library
		$attachment_id = media_handle_upload( 'quiz_image', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_upload_fail', __( 'Quiz Image Upload Error: ', 'quizbank-pro' ) . $attachment_id->get_error_message(), 'error' );
			return;
		} else {
			// Extract full original storage path URL string to persist inside custom schema string entry
			$quiz_image_url = wp_get_attachment_url( $attachment_id );
		}
	}

	$current_time = current_time( 'mysql', 1 ); // Explicit UTC standard timestamping

	if ( 'edit_quiz' === $quiz_action ) {
		$updated = $wpdb->update(
			$table_name,
			array(
				'quiz_topic_id' => $quiz_topic_id,
				'title'        => $title,
				'layout_style' => $layout_style,
				'quiz_image'   => $quiz_image_url,
				'modified_at'  => $current_time,
			),
			array( 'id' => $target_quiz_id ),
			array( '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $updated ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_updated', __( 'Quiz updated successfully!', 'quizbank-pro' ), 'updated' );
			wp_safe_redirect( admin_url( 'admin.php?page=quizbank-pro' ) );
			exit;
		}

		add_settings_error( 'qbp_messages', 'qbp_quiz_update_failed', __( 'Database update failed.', 'quizbank-pro' ), 'error' );
		return;
	}

	$inserted = $wpdb->insert(
		$table_name,
		array(
			'quiz_topic_id' => $quiz_topic_id,
			'title'        => $title,
			'layout_style' => $layout_style,
			'quiz_image'   => $quiz_image_url,
			'created_at'   => $current_time,
			'modified_at'  => $current_time,
		),
		array( '%d', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( $inserted ) {
		add_settings_error( 'qbp_messages', 'qbp_quiz_success', __( 'Quiz created successfully!', 'quizbank-pro' ), 'updated' );
		wp_safe_redirect( admin_url( 'admin.php?page=quizbank-pro' ) );
		exit;
	} else {
		add_settings_error( 'qbp_messages', 'qbp_quiz_failed', __( 'Database insertion failed.', 'quizbank-pro' ), 'error' );
	}
}

function qbp_process_quiz_delete() {
	$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

	if ( 'quizbank-pro' !== $page || 'delete' !== $action || ! isset( $_GET['quiz_id'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
	}

	$quiz_id = absint( $_GET['quiz_id'] );
	if ( empty( $quiz_id ) ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'qbp_delete_quiz_' . $quiz_id ) ) {
		wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qbp_quizzes';

	$deleted = $wpdb->delete(
		$table_name,
		array( 'id' => $quiz_id ),
		array( '%d' )
	);

	if ( false !== $deleted && $deleted > 0 ) {
		add_settings_error( 'qbp_messages', 'qbp_quiz_deleted', __( 'Quiz deleted successfully.', 'quizbank-pro' ), 'updated' );
	} else {
		add_settings_error( 'qbp_messages', 'qbp_quiz_delete_failed', __( 'Unable to delete quiz.', 'quizbank-pro' ), 'error' );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=quizbank-pro' ) );
	exit;
}
