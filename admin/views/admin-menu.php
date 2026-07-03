<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'qbp_register_admin_menus' );
function qbp_register_admin_menus() {
	// Top-level Menu
	add_menu_page(
		__( 'QuizBank Pro', 'quizbank-pro' ),
		__( 'QuizBank Pro', 'quizbank-pro' ),
		'manage_options',
		'quizbank-pro',
		'qbp_render_quizzes_page',
		'dashicons-welcome-learn-with-us', // Core declaration
		25
	);

	// Sub-menu 1: Manage Quizzes
	add_submenu_page(
		'quizbank-pro',
		__( 'Manage Quizzes', 'quizbank-pro' ),
		__( 'Manage Quizzes', 'quizbank-pro' ),
		'manage_options',
		'quizbank-pro',
		'qbp_render_quizzes_page'
	);

	// Sub-menu 2: Quiz Topics
	add_submenu_page(
		'quizbank-pro',
		__( 'Quiz Topics', 'quizbank-pro' ),
		__( 'Quiz Topics', 'quizbank-pro' ),
		'manage_options',
		'qbp-quiz-topics',
		'qbp_render_quiz_topics_page'
	);

	// Sub-menu 3: Manage Questions
	add_submenu_page(
		'quizbank-pro',
		__( 'Manage Questions', 'quizbank-pro' ),
		__( 'Manage Questions', 'quizbank-pro' ),
		'manage_options',
		'qbp-questions',
		'qbp_render_questions_page'
	);

	// Sub-menu 4: Question Topics
	add_submenu_page(
		'quizbank-pro',
		__( 'Question Topics', 'quizbank-pro' ),
		__( 'Question Topics', 'quizbank-pro' ),
		'manage_options',
		'qbp-question-topics',
		'qbp_render_topics_page'
	);
}
