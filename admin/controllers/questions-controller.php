<?php
if ( ! defined( "ABSPATH" ) ) exit;

add_action( 'admin_init', 'qbp_process_question_form' );
add_action( 'wp_ajax_qbp_sort_questions', 'qbp_ajax_sort_questions' );

function qbp_get_questions_sort_args( $orderby = 'id', $order = 'desc' ) {
	$allowed_orderby = array(
		'id'             => 'q.id',
		'topic'          => 't.question_topic_name',
		'is_multiselect' => 'q.is_multiselect',
	);

	$orderby         = sanitize_key( $orderby );
	$order           = sanitize_key( $order );
	$is_valid_sort   = isset( $allowed_orderby[ $orderby ] );
	$is_valid_order  = in_array( $order, array( 'asc', 'desc' ), true );

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

function qbp_get_questions_for_display( $orderby = 'id', $order = 'desc' ) {
	global $wpdb;

	$sort                  = qbp_get_questions_sort_args( $orderby, $order );
	$table_question_topics = $wpdb->prefix . 'qbp_question_topics';
	$table_questions       = $wpdb->prefix . 'qbp_questions';
	$order_sql             = strtoupper( $sort['order'] );
	$secondary_order_sql   = 'q.id' === $sort['column'] ? '' : ', q.id DESC';

	return $wpdb->get_results(
		"SELECT q.id, q.question_text, q.question_hint, q.question_image, q.is_multiselect, t.question_topic_name
		FROM {$table_questions} q
		JOIN {$table_question_topics} t ON q.question_topic_id = t.id
		ORDER BY {$sort['column']} {$order_sql}{$secondary_order_sql}"
	);
}

function qbp_render_questions_table_rows( $questions ) {
	ob_start();
	?>
	<?php if ( empty( $questions ) ) : ?>
		<tr><td colspan="5"><?php esc_html_e( 'No questions found in master bank archive.', 'quizbank-pro' ); ?></td></tr>
	<?php else : ?>
		<?php foreach ( $questions as $quest ) : ?>
			<?php
			$question_id  = absint( $quest->id );
			$delete_nonce = wp_create_nonce( 'qbp_delete_question_' . $question_id );
			$edit_url     = admin_url( 'admin.php?page=qbp-questions&action=edit&question_id=' . $question_id );
			$delete_url   = admin_url( 'admin.php?page=qbp-questions&action=delete&question_id=' . $question_id . '&_wpnonce=' . $delete_nonce );
			?>
			<tr class="qbp-row-hover">
				<td><strong><?php echo esc_html( $question_id ); ?></strong></td>
				<td style="text-align:center; vertical-align:middle;">
					<?php if ( ! empty( $quest->question_image ) ) : ?>
						<img src="<?php echo esc_url( $quest->question_image ); ?>" class="qbp-table-thumbnail" alt="<?php esc_attr_e( 'Question image', 'quizbank-pro' ); ?>">
					<?php else : ?>
						<span class="dashicons dashicons-image-rotate qbp-thumbnail-placeholder" title="<?php esc_attr_e( 'No Image', 'quizbank-pro' ); ?>"></span>
					<?php endif; ?>
				</td>
				<td>
					<div class="qbp-question-summary"><?php echo esc_html( wp_trim_words( $quest->question_text, 20, '...' ) ); ?></div>
					<?php if ( ! empty( $quest->question_hint ) ) : ?>
						<div class="qbp-question-hint"><?php echo esc_html( sprintf( __( 'Hint: %s', 'quizbank-pro' ), wp_trim_words( $quest->question_hint, 18, '...' ) ) ); ?></div>
					<?php endif; ?>
					<div class="row-actions" style="font-size:12px; padding:2px 0 0 0;">
						<span class="edit">
							<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'quizbank-pro' ); ?></a> |
						</span>
						<span class="trash">
							<a href="<?php echo esc_url( $delete_url ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php echo esc_js( __( 'Are you absolutely sure you want to delete this question?', 'quizbank-pro' ) ); ?>');"><?php esc_html_e( 'Delete', 'quizbank-pro' ); ?></a>
						</span>
					</div>
				</td>
				<td><span class="badge qbp-inline-badge"><?php echo esc_html( $quest->question_topic_name ); ?></span></td>
				<td>
					<small><code class="qbp-table-code"><?php echo $quest->is_multiselect ? esc_html__( 'Yes', 'quizbank-pro' ) : esc_html__( 'No', 'quizbank-pro' ); ?></code></small>
				</td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}

function qbp_ajax_sort_questions() {
	check_ajax_referer( 'qbp_sort_questions', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Unauthorized access.', 'quizbank-pro' ) ),
			403
		);
	}

	$orderby   = isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( (string) $_POST['orderby'] ) ) : 'id';
	$order     = isset( $_POST['order'] ) ? sanitize_key( wp_unslash( (string) $_POST['order'] ) ) : 'desc';
	$sort      = qbp_get_questions_sort_args( $orderby, $order );
	$questions = qbp_get_questions_for_display( $sort['orderby'], $sort['order'] );

	wp_send_json_success(
		array(
			'rows'    => qbp_render_questions_table_rows( $questions ),
			'orderby' => $sort['orderby'],
			'order'   => $sort['order'],
		)
	);
}

function qbp_process_question_form() {
	if (
		isset( $_GET['page'], $_GET['action'] ) &&
		'qbp-questions' === sanitize_key( wp_unslash( $_GET['page'] ) ) &&
		'delete' === sanitize_key( wp_unslash( $_GET['action'] ) )
	) {
		qbp_handle_question_delete();
		return;
	}

	if ( ! isset( $_POST['qbp_question_action'] ) ) {
		return;
	}

	$question_action = sanitize_key( wp_unslash( $_POST['qbp_question_action'] ) );
	if ( ! in_array( $question_action, array( 'add_question', 'edit_question' ), true ) ) {
		return;
	}

	if ( ! isset( $_POST['qbp_question_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qbp_question_nonce'] ) ), 'qbp_question_form_verify' ) ) {
		wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
	}

	global $wpdb;
	$table_questions = $wpdb->prefix . 'qbp_questions';
	$table_answers   = $wpdb->prefix . 'qbp_answers';
	$current_time    = current_time( 'mysql', 1 );
	$question_id     = 'edit_question' === $question_action && isset( $_POST['target_question_id'] ) ? absint( $_POST['target_question_id'] ) : 0;

	$question_topic_id = isset( $_POST['question_topic_id'] ) ? absint( $_POST['question_topic_id'] ) : 0;
	$question_text     = isset( $_POST['question_text'] ) ? wp_kses_post( wp_unslash( $_POST['question_text'] ) ) : '';
	$question_hint     = isset( $_POST['question_hint'] ) ? wp_kses_post( wp_unslash( $_POST['question_hint'] ) ) : '';
	$is_multiselect    = isset( $_POST['is_multiselect'] ) ? 1 : 0;

	if ( empty( $question_topic_id ) || empty( $question_text ) ) {
		add_settings_error( 'qbp_messages', 'qbp_quest_empty', __( 'Topic and Question Text fields are required.', 'quizbank-pro' ), 'error' );
		return;
	}

	if ( 'edit_question' === $question_action && empty( $question_id ) ) {
		add_settings_error( 'qbp_messages', 'qbp_quest_missing', __( 'A valid question is required for editing.', 'quizbank-pro' ), 'error' );
		return;
	}

	$answers_txt      = isset( $_POST['answers'] ) ? (array) $_POST['answers'] : array();
	$correct_array    = isset( $_POST['correct_answers'] ) ? array_map( 'strval', (array) $_POST['correct_answers'] ) : array();
	$prepared_answers = array();
	$correct_count    = 0;

	foreach ( $answers_txt as $index => $answer_string ) {
		$clean_answer = sanitize_text_field( wp_unslash( $answer_string ) );
		if ( '' === $clean_answer ) {
			continue;
		}

		$is_correct_flag   = in_array( (string) $index, $correct_array, true ) ? 1 : 0;
		$prepared_answers[] = array(
			'answer_text' => $clean_answer,
			'is_correct'  => $is_correct_flag,
		);

		if ( $is_correct_flag ) {
			$correct_count++;
		}
	}

	if ( count( $prepared_answers ) < 2 ) {
		add_settings_error( 'qbp_messages', 'qbp_quest_answers_missing', __( 'Please add at least two answer choices.', 'quizbank-pro' ), 'error' );
		return;
	}

	if ( ! $is_multiselect ) {
		if ( 1 !== $correct_count ) {
			add_settings_error( 'qbp_messages', 'qbp_quest_single_correct_err', __( 'You must select exactly one correct option when Multi-Select is disabled.', 'quizbank-pro' ), 'error' );
			return;
		}
	} elseif ( $correct_count < 2 ) {
		add_settings_error( 'qbp_messages', 'qbp_quest_multi_correct_err', __( 'You must select two or more correct options when Multi-Select is enabled.', 'quizbank-pro' ), 'error' );
		return;
	}

	$question_image_url   = null;
	$current_question_row = null;

	if ( 'edit_question' === $question_action ) {
		$current_question_row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_questions} WHERE id = %d", $question_id )
		);

		if ( ! $current_question_row ) {
			add_settings_error( 'qbp_messages', 'qbp_quest_not_found', __( 'The selected question could not be found.', 'quizbank-pro' ), 'error' );
			return;
		}

		$question_image_url = $current_question_row->question_image;
		if ( isset( $_POST['remove_question_image'] ) ) {
			$question_image_url = null;
		}
	}

	if ( ! empty( $_FILES['question_image']['name'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'question_image', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			add_settings_error( 'qbp_messages', 'qbp_upload_fail', __( 'Image Upload Error: ', 'quizbank-pro' ) . $attachment_id->get_error_message(), 'error' );
			return;
		}

		$question_image_url = wp_get_attachment_url( $attachment_id );
	}

	if ( 'add_question' === $question_action ) {
		$inserted_question = $wpdb->insert(
			$table_questions,
			array(
				'question_topic_id' => $question_topic_id,
				'question_text'     => $question_text,
				'question_hint'     => $question_hint,
				'question_image'    => $question_image_url,
				'is_active'         => 1,
				'is_multiselect'    => $is_multiselect,
				'created_at'        => $current_time,
				'modified_at'       => $current_time,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( ! $inserted_question ) {
			add_settings_error( 'qbp_messages', 'qbp_quest_fail', __( 'Failed to save question instance record.', 'quizbank-pro' ), 'error' );
			return;
		}

		$question_id = (int) $wpdb->insert_id;
	} else {
		$updated_question = $wpdb->update(
			$table_questions,
			array(
				'question_topic_id' => $question_topic_id,
				'question_text'     => $question_text,
				'question_hint'     => $question_hint,
				'question_image'    => $question_image_url,
				'is_multiselect'    => $is_multiselect,
				'modified_at'       => $current_time,
			),
			array( 'id' => $question_id ),
			array( '%d', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $updated_question ) {
			add_settings_error( 'qbp_messages', 'qbp_quest_update_fail', __( 'Failed to update the selected question.', 'quizbank-pro' ), 'error' );
			return;
		}

		$wpdb->delete( $table_answers, array( 'question_id' => $question_id ), array( '%d' ) );
	}

	$sort_counter = 0;
	foreach ( $prepared_answers as $answer_row ) {
		$wpdb->insert(
			$table_answers,
			array(
				'question_id' => $question_id,
				'sort_order'  => $sort_counter,
				'answer_text' => $answer_row['answer_text'],
				'is_correct'  => $answer_row['is_correct'],
				'is_active'   => 1,
				'created_at'  => $current_time,
				'modified_at' => $current_time,
			),
			array( '%d', '%d', '%s', '%d', '%d', '%s', '%s' )
		);
		$sort_counter++;
	}

	if ( 'edit_question' === $question_action ) {
		add_settings_error( 'qbp_messages', 'qbp_quest_updated', __( 'Question updated successfully!', 'quizbank-pro' ), 'updated' );
	} else {
		add_settings_error( 'qbp_messages', 'qbp_quest_success', __( 'Question and answers stored successfully!', 'quizbank-pro' ), 'updated' );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=qbp-questions' ) );
	exit;
}

function qbp_handle_question_delete() {
	$question_id = isset( $_GET['question_id'] ) ? absint( $_GET['question_id'] ) : 0;
	if ( empty( $question_id ) ) {
		add_settings_error( 'qbp_messages', 'qbp_quest_delete_missing', __( 'A valid question is required for deletion.', 'quizbank-pro' ), 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=qbp-questions' ) );
		exit;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'qbp_delete_question_' . $question_id ) ) {
		wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
	}

	global $wpdb;
	$table_questions = $wpdb->prefix . 'qbp_questions';
	$table_answers   = $wpdb->prefix . 'qbp_answers';

	$wpdb->delete( $table_answers, array( 'question_id' => $question_id ), array( '%d' ) );
	$deleted_question = $wpdb->delete( $table_questions, array( 'id' => $question_id ), array( '%d' ) );

	if ( false === $deleted_question || 0 === $deleted_question ) {
		add_settings_error( 'qbp_messages', 'qbp_quest_delete_fail', __( 'Failed to delete the selected question.', 'quizbank-pro' ), 'error' );
	} else {
		add_settings_error( 'qbp_messages', 'qbp_quest_delete_success', __( 'Question deleted successfully!', 'quizbank-pro' ), 'updated' );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=qbp-questions' ) );
	exit;
}

function qbp_get_question_form_state() {
	$state = array(
		'is_edit_mode'       => false,
		'target_question_id' => 0,
		'question_topic_id'  => 0,
		'question_text'      => '',
		'question_hint'      => '',
		'is_multiselect'     => 0,
		'question_image'     => '',
		'answers'            => array(),
		'correct_answers'    => array(),
	);

	if ( isset( $_POST['qbp_question_action'] ) ) {
		$question_action = sanitize_key( wp_unslash( $_POST['qbp_question_action'] ) );

		if ( in_array( $question_action, array( 'add_question', 'edit_question' ), true ) ) {
			$state['is_edit_mode']       = 'edit_question' === $question_action;
			$state['target_question_id'] = isset( $_POST['target_question_id'] ) ? absint( $_POST['target_question_id'] ) : 0;
			$state['question_topic_id']  = isset( $_POST['question_topic_id'] ) ? absint( $_POST['question_topic_id'] ) : 0;
			$state['question_text']      = isset( $_POST['question_text'] ) ? wp_unslash( $_POST['question_text'] ) : '';
			$state['question_hint']      = isset( $_POST['question_hint'] ) ? wp_unslash( $_POST['question_hint'] ) : '';
			$state['is_multiselect']     = isset( $_POST['is_multiselect'] ) ? 1 : 0;
			$state['question_image']     = isset( $_POST['current_question_image'] ) ? esc_url_raw( wp_unslash( $_POST['current_question_image'] ) ) : '';
			$state['answers']            = isset( $_POST['answers'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) $_POST['answers'] ) ) : array();
			$state['correct_answers']    = isset( $_POST['correct_answers'] ) ? array_map( 'strval', (array) $_POST['correct_answers'] ) : array();

			if ( isset( $_POST['remove_question_image'] ) ) {
				$state['question_image'] = '';
			}

			return $state;
		}
	}

	if (
		! isset( $_GET['page'], $_GET['action'], $_GET['question_id'] ) ||
		'qbp-questions' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ||
		'edit' !== sanitize_key( wp_unslash( $_GET['action'] ) )
	) {
		return $state;
	}

	$question_id = absint( $_GET['question_id'] );
	if ( empty( $question_id ) ) {
		return $state;
	}

	global $wpdb;
	$table_questions = $wpdb->prefix . 'qbp_questions';
	$table_answers   = $wpdb->prefix . 'qbp_answers';

	$question = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table_questions} WHERE id = %d", $question_id )
	);

	if ( ! $question ) {
		return $state;
	}

	$answers = $wpdb->get_results(
		$wpdb->prepare( "SELECT answer_text, is_correct FROM {$table_answers} WHERE question_id = %d ORDER BY sort_order ASC, id ASC", $question_id )
	);

	$state['is_edit_mode']       = true;
	$state['target_question_id'] = (int) $question->id;
	$state['question_topic_id']  = (int) $question->question_topic_id;
	$state['question_text']      = $question->question_text;
	$state['question_hint']      = $question->question_hint;
	$state['is_multiselect']     = (int) $question->is_multiselect;
	$state['question_image']     = (string) $question->question_image;

	foreach ( (array) $answers as $index => $answer ) {
		$state['answers'][] = (string) $answer->answer_text;
		if ( ! empty( $answer->is_correct ) ) {
			$state['correct_answers'][] = (string) $index;
		}
	}

	return $state;
}
