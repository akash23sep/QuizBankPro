<?php
/**
 * Plugin Name:       QuizBank Pro
 * Plugin URI:        https://example.com/quizbank-pro
 * Description:       A professional quiz management system.
 * Version:           1.1.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPLv2 or later
 * Text Domain:       quizbank-pro
 * Prefix:            qbp_
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *	Register the activation hook to initialize custom database tables.
 */
register_activation_hook( __FILE__, 'qbp_activate_plugin' );

function qbp_activate_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'schema.php';
	qbp_execute_database_migrations();
}

function qbp_enqueue_admin_assets( $hook_suffix ) {
	wp_enqueue_style( 'qbp-admin-css', plugin_dir_url( __FILE__ ) . 'admin/css/qbp-admin.css', array(), '1.1.0' );

	if ( in_array( $hook_suffix, array( 'toplevel_page_quizbank-pro', 'quizbank-pro_page_qbp-questions' ), true ) ) {
		wp_enqueue_script( 'qbp-admin-js', plugin_dir_url( __FILE__ ) . 'admin/js/qbp-admin.js', array(), '1.1.0', true );
		wp_localize_script(
			'qbp-admin-js',
			'qbpAdmin',
			array(
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'sortQuestionsNonce'      => wp_create_nonce( 'qbp_sort_questions' ),
				'sortQuestionsErrorText'  => __( 'Could not sort questions. Please try again.', 'quizbank-pro' ),
				'sortQuizzesNonce'        => wp_create_nonce( 'qbp_sort_quizzes' ),
				'sortQuizzesErrorText'    => __( 'Could not sort quizzes. Please try again.', 'quizbank-pro' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'qbp_enqueue_admin_assets' );

require_once plugin_dir_path( __FILE__ ) . 'admin/views/admin-menu.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/controllers/quiz-topics-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/controllers/quizzes-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/controllers/question-topics-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/controllers/questions-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-views-router.php';
