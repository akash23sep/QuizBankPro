<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Execute custom database migrations for QuizBank Pro.
 */
function qbp_execute_database_migrations() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Load native WordPress dbDelta upgrade library engine
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Table Name References
	$table_question_topics    = $wpdb->prefix . 'qbp_question_topics';
	$table_quiz_topics        = $wpdb->prefix . 'qbp_quiz_topics';
	$table_questions 		  = $wpdb->prefix . 'qbp_questions';
	$table_answers   		  = $wpdb->prefix . 'qbp_answers';
	$table_quizzes   		  = $wpdb->prefix . 'qbp_quizzes';
	$table_mapping   		  = $wpdb->prefix . 'qbp_quiz_question_mapping';

	// Schema 1: Quiz Topics Table
	$sql_quiz_topics = "CREATE TABLE $table_quiz_topics (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		quiz_topic_name VARCHAR(100) NOT NULL,
		is_active TINYINT(1) DEFAULT 1 NOT NULL,
		created_at DATETIME NOT NULL,
		modified_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// Schema 2: Question Topics Table
	$sql_question_topics = "CREATE TABLE $table_question_topics (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		question_topic_name VARCHAR(100) NOT NULL,
		is_active TINYINT(1) DEFAULT 1 NOT NULL,
		created_at DATETIME NOT NULL,
		modified_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// Schema 3: Quiz Configuration Master Table
	$sql_quizzes = "CREATE TABLE $table_quizzes (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		quiz_topic_id BIGINT(20) NOT NULL,
		title VARCHAR(255) NOT NULL,
		quiz_image VARCHAR(255) DEFAULT NULL,
		layout_style VARCHAR(50) DEFAULT 'paged' NOT NULL,
		created_at DATETIME NOT NULL,
		modified_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// Schema 4: Question Bank Table (With Explicit Foreign Key Index Setup)
	$sql_questions = "CREATE TABLE $table_questions (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		question_topic_id BIGINT(20) NOT NULL,
		question_text TEXT NOT NULL,
		question_hint TEXT DEFAULT NULL,
		question_image VARCHAR(255) DEFAULT NULL,
		is_active TINYINT(1) DEFAULT 1 NOT NULL,
		is_multiselect TINYINT(1) DEFAULT 0 NOT NULL,
		created_at DATETIME NOT NULL,
		modified_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY question_topic_id_idx (question_topic_id)
	) $charset_collate;";

	// Schema 5: Dynamic Answers Table (With Explicit Foreign Key Index Setup)
	$sql_answers = "CREATE TABLE $table_answers (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		question_id BIGINT(20) NOT NULL,
		sort_order INT(11) DEFAULT 0 NOT NULL,
		answer_text TEXT NOT NULL,
		is_correct TINYINT(1) DEFAULT 0 NOT NULL,
		is_active TINYINT(1) DEFAULT 1 NOT NULL,
		created_at DATETIME NOT NULL,
		modified_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY question_id_idx (question_id)
	) $charset_collate;";

	// Schema 6: Composite Many-to-Many Quiz Question Mapping Entry Relation
	$sql_mapping = "CREATE TABLE $table_mapping (
		quiz_id BIGINT(20) NOT NULL,
		question_id BIGINT(20) NOT NULL,
		sort_order INT(11) DEFAULT 0 NOT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (quiz_id, question_id),
		KEY quiz_id_idx (quiz_id),
		KEY question_id_idx (question_id)
	) $charset_collate;";

	// Execute Native Isolation Engine Upgrades
	dbDelta( $sql_quiz_topics );
	dbDelta( $sql_question_topics );
	dbDelta( $sql_questions );
	dbDelta( $sql_answers );
	dbDelta( $sql_quizzes );
	dbDelta( $sql_mapping );

	// =========================================================================
	// INTEGRATED RELATIONAL FOREIGN KEY CONSTRAINT ROUTINES
	// =========================================================================
	
	// Query database architecture layout schema directly to prevent double execution crashes
	$existing_constraints = $wpdb->get_col( $wpdb->prepare(
		"SELECT CONSTRAINT_NAME 
		 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
		 WHERE TABLE_SCHEMA = %s AND CONSTRAINT_NAME LIKE 'fk_qbp_%'",
		DB_NAME
	) );


	// 1. Safe Binding: Quiz -> Quiz Topics (Protected Rule)
	if ( ! in_array( 'fk_qbp_quizzes_topic', $existing_constraints, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_quizzes} ADD CONSTRAINT fk_qbp_quizzes_topic FOREIGN KEY (quiz_topic_id) REFERENCES {$table_quiz_topics}(id) ON DELETE RESTRICT ON UPDATE CASCADE;" );
	}
	// 2. Safe Binding: Questions -> Question Topics (Protected Rule)
	if ( ! in_array( 'fk_qbp_questions_topic', $existing_constraints, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_questions} ADD CONSTRAINT fk_qbp_questions_topic FOREIGN KEY (question_topic_id) REFERENCES {$table_question_topics}(id) ON DELETE RESTRICT ON UPDATE CASCADE;" );
	}

	// 3. Cascading Binding: Answers -> Questions (Auto-Wipe Rule)
	if ( ! in_array( 'fk_qbp_answers_question', $existing_constraints, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_answers} ADD CONSTRAINT fk_qbp_answers_question FOREIGN KEY (question_id) REFERENCES {$table_questions}(id) ON DELETE CASCADE ON UPDATE CASCADE;" );
	}

	// 4. Composite Binding: Mapping -> Quizzes (Clean Association Link Rule)
	if ( ! in_array( 'fk_qbp_mapping_quiz', $existing_constraints, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_mapping} ADD CONSTRAINT fk_qbp_mapping_quiz FOREIGN KEY (quiz_id) REFERENCES {$table_quizzes}(id) ON DELETE CASCADE ON UPDATE CASCADE;" );
	}

	// 5. Composite Binding: Mapping -> Questions (Clean Association Link Rule)
	if ( ! in_array( 'fk_qbp_mapping_question', $existing_constraints, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_mapping} ADD CONSTRAINT fk_qbp_mapping_question FOREIGN KEY (question_id) REFERENCES {$table_questions}(id) ON DELETE CASCADE ON UPDATE CASCADE;" );
	}
}
