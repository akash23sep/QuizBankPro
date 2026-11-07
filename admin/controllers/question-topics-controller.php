<?php
if ( ! defined( "ABSPATH" ) ) exit;

add_action( 'admin_init', 'qbp_process_question_topic_form' );

function qbp_process_question_topic_form() {
	global $wpdb;
	$table_name          = $wpdb->prefix . 'qbp_question_topics';
	$question_topics_url = add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=qbp-question-topics' ) );

	// 1. HANDLE DELETE ACTION (Via GET request for security/simplicity)
	if ( isset( $_GET['page'], $_GET['action'], $_GET['topic_id'] ) && $_GET['page'] === 'qbp-question-topics' && $_GET['action'] === 'delete' ) {
		$topic_id = absint( $_GET['topic_id'] );

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'qbp_delete_topic_' . $topic_id ) ) {
			wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
		}

		// Pre-check for a friendly message before the FK restriction would reject the delete.
		$table_questions = $wpdb->prefix . 'qbp_questions';
		$has_questions   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_questions} WHERE question_topic_id = %d", $topic_id ) );

		if ( $has_questions > 0 ) {
			add_settings_error( 'qbp_messages', 'qbp_topic_del_restricted', __( 'Cannot delete topic. It contains active questions inside the master bank.', 'quizbank-pro' ), 'error' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $question_topics_url );
			exit;
		}

		$deleted = $wpdb->delete( $table_name, array( 'id' => $topic_id ), array( '%d' ) );

		if ( $deleted ) {
			add_settings_error( 'qbp_messages', 'qbp_topic_del_success', __( 'Topic deleted successfully!', 'quizbank-pro' ), 'updated' );
		} else {
			add_settings_error( 'qbp_messages', 'qbp_topic_del_failed', __( 'Topic could not be deleted.', 'quizbank-pro' ), 'error' );
		}

		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( $question_topics_url );
		exit;
	}

	// 2. HANDLE POST SUBMISSIONS (Add & Edit Form Submissions)
	if ( ! isset( $_POST['qbp_question_topic_action'] ) || ! in_array( $_POST['qbp_question_topic_action'], array( 'add_topic', 'edit_topic' ), true ) ) {
		return;
	}

	if ( ! isset( $_POST['qbp_question_topic_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qbp_question_topic_nonce'] ) ), 'qbp_question_topic_form_verify' ) ) {
		wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
	}

	$question_topic_name = isset( $_POST['question_topic_name'] ) ? sanitize_text_field( wp_unslash( $_POST['question_topic_name'] ) ) : '';

	if ( empty( $question_topic_name ) ) {
		add_settings_error( 'qbp_messages', 'qbp_topic_empty', __( 'Topic name cannot be empty.', 'quizbank-pro' ), 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( $question_topics_url );
		exit;
	}

	if ( mb_strlen( $question_topic_name, 'UTF-8' ) > 100 ) {
		add_settings_error( 'qbp_messages', 'qbp_topic_too_long', __( 'Topic name cannot exceed 100 characters.', 'quizbank-pro' ), 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( $question_topics_url );
		exit;
	}

	$current_time = current_time( 'mysql', 1 );

	if ( $_POST['qbp_question_topic_action'] === 'edit_topic' ) {
		$topic_id = isset( $_POST['target_topic_id'] ) ? absint( $_POST['target_topic_id'] ) : 0;

		if ( empty( $topic_id ) ) {
			add_settings_error( 'qbp_messages', 'qbp_topic_invalid', __( 'Invalid topic selected for editing.', 'quizbank-pro' ), 'error' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $question_topics_url );
			exit;
		}

		$updated = $wpdb->update(
			$table_name,
			array( 'question_topic_name' => $question_topic_name, 'modified_at' => $current_time ),
			array( 'id' => $topic_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $updated !== false ) {
			add_settings_error( 'qbp_messages', 'qbp_topic_success', __( 'Topic updated successfully!', 'quizbank-pro' ), 'updated' );
		} else {
			add_settings_error( 'qbp_messages', 'qbp_topic_failed', __( 'Topic update failed.', 'quizbank-pro' ), 'error' );
		}

	} elseif ( $_POST['qbp_question_topic_action'] === 'add_topic' ) {
		$inserted = $wpdb->insert(
			$table_name,
			array( 'question_topic_name' => $question_topic_name, 'is_active' => 1, 'created_at' => $current_time, 'modified_at' => $current_time ),
			array( '%s', '%d', '%s', '%s' )
		);

		if ( $inserted ) {
			add_settings_error( 'qbp_messages', 'qbp_topic_success', __( 'Topic added successfully!', 'quizbank-pro' ), 'updated' );
		} else {
			add_settings_error( 'qbp_messages', 'qbp_topic_failed', __( 'Topic insertion failed.', 'quizbank-pro' ), 'error' );
		}
	}

	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_safe_redirect( $question_topics_url );
	exit;
}
