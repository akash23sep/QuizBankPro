<?php
if ( ! defined( "ABSPATH" ) ) exit;

add_action( 'admin_init', 'qbp_process_quiz_topic_form' );

function qbp_process_quiz_topic_form() {
	global $wpdb;
	$table_quiz_topics = $wpdb->prefix . 'qbp_quiz_topics';
	$quiz_topics_url   = add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=qbp-quiz-topics' ) );

	// 1. HANDLE DELETE ACTION (Via GET request with nonce verification)
	if ( isset( $_GET['page'], $_GET['action'], $_GET['quiz_topic_id'] ) && $_GET['page'] === 'qbp-quiz-topics' && $_GET['action'] === 'delete' ) {
		$quiz_topic_id = absint( $_GET['quiz_topic_id'] );

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'qbp_delete_quiz_topic_' . $quiz_topic_id ) ) {
			wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
		}

		$table_quizzes = $wpdb->prefix . 'qbp_quizzes';
		$has_quizzes   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_quizzes} WHERE quiz_topic_id = %d", $quiz_topic_id ) );

		if ( $has_quizzes > 0 ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_del_restricted', __( 'Cannot delete quiz topic. It has active quizzes mapped to it.', 'quizbank-pro' ), 'warning' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $quiz_topics_url );
			exit;
		}

		$deleted = $wpdb->delete( $table_quiz_topics, array( 'id' => $quiz_topic_id ), array( '%d' ) );
		if ( $deleted ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_del_success', __( 'Quiz topic deleted successfully!', 'quizbank-pro' ), 'updated' );
		} else {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_del_failed', __( 'Quiz topic could not be deleted.', 'quizbank-pro' ), 'error' );
		}

		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( $quiz_topics_url );
		exit;
	}

	// 2. HANDLE POST SUBMISSIONS (Add & Edit Form Submissions)
	if ( ! isset( $_POST['qbp_quiz_topic_action'] ) || ! in_array( $_POST['qbp_quiz_topic_action'], array( 'add_quiz_topic', 'edit_quiz_topic' ), true ) ) {
		return;
	}

	if ( ! isset( $_POST['qbp_quiz_topic_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qbp_quiz_topic_nonce'] ) ), 'qbp_quiz_topic_form_verify' ) ) {
		wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
	}

	$quiz_topic_name = isset( $_POST['quiz_topic_name'] ) ? sanitize_text_field( wp_unslash( $_POST['quiz_topic_name'] ) ) : '';

	if ( empty( $quiz_topic_name ) ) {
		add_settings_error( 'qbp_messages', 'qbp_quiz_topic_empty', __( 'Quiz topic name cannot be empty.', 'quizbank-pro' ), 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( $quiz_topics_url );
		exit;
	}

	$current_time = current_time( 'mysql', 1 );

	if ( $_POST['qbp_quiz_topic_action'] === 'edit_quiz_topic' ) {
		$quiz_topic_id = isset( $_POST['target_quiz_topic_id'] ) ? absint( $_POST['target_quiz_topic_id'] ) : 0;

		if ( empty( $quiz_topic_id ) ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_invalid', __( 'Invalid quiz topic selected for editing.', 'quizbank-pro' ), 'error' );
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( $quiz_topics_url );
			exit;
		}

		$updated = $wpdb->update(
			$table_quiz_topics,
			array( 'quiz_topic_name' => $quiz_topic_name, 'modified_at' => $current_time ),
			array( 'id' => $quiz_topic_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $updated !== false ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_success', __( 'Quiz topic updated successfully!', 'quizbank-pro' ), 'updated' );
		} else {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_failed', __( 'Quiz topic update failed.', 'quizbank-pro' ), 'error' );
		}

	} elseif ( $_POST['qbp_quiz_topic_action'] === 'add_quiz_topic' ) {
		$inserted = $wpdb->insert(
			$table_quiz_topics,
			array( 'quiz_topic_name' => $quiz_topic_name, 'is_active' => 1, 'created_at' => $current_time, 'modified_at' => $current_time ),
			array( '%s', '%d', '%s', '%s' )
		);

		if ( $inserted ) {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_success', __( 'Quiz topic added successfully!', 'quizbank-pro' ), 'updated' );
		} else {
			add_settings_error( 'qbp_messages', 'qbp_quiz_topic_failed', __( 'Quiz topic insertion failed.', 'quizbank-pro' ), 'error' );
		}
	}

	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_safe_redirect( $quiz_topics_url );
	exit;
}