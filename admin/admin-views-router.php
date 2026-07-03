<?php
/**
 * Plugin Admin Dashboard View Router Pipeline
 * Path: admin-views-router.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function qbp_render_admin_tabs( $active_tab = 'quizbank-pro' ) {
	?>
	<h2 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=quizbank-pro' ) ); ?>" class="nav-tab <?php echo $active_tab === 'quizbank-pro' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Manage Quizzes', 'quizbank-pro' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=qbp-quiz-topics' ) ); ?>" class="nav-tab <?php echo $active_tab === 'qbp-quiz-topics' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Quiz Topics', 'quizbank-pro' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=qbp-questions' ) ); ?>" class="nav-tab <?php echo $active_tab === 'qbp-questions' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Manage Questions', 'quizbank-pro' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=qbp-question-topics' ) ); ?>" class="nav-tab <?php echo $active_tab === 'qbp-question-topics' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Question Topics', 'quizbank-pro' ); ?></a>
	</h2>
	<?php
}

function qbp_render_quiz_topics_page() {
	global $wpdb;
	$table_quiz_topics = $wpdb->prefix . 'qbp_quiz_topics';

	$is_edit_mode        = ( isset( $_GET['action'], $_GET['quiz_topic_id'] ) && $_GET['action'] === 'edit' );
	$edit_quiz_topic_name = '';
	$target_quiz_topic_id = 0;

	if ( $is_edit_mode ) {
		$target_quiz_topic_id  = absint( $_GET['quiz_topic_id'] );
		$edit_quiz_topic_name = $wpdb->get_var( $wpdb->prepare( "SELECT quiz_topic_name FROM {$table_quiz_topics} WHERE id = %d", $target_quiz_topic_id ) );
	}

	$quiz_topics = $wpdb->get_results( "SELECT id, quiz_topic_name, created_at FROM {$table_quiz_topics} ORDER BY id DESC" );

	include plugin_dir_path( __FILE__ ) . 'views/quiz-topics-view.php';
}

function qbp_render_topics_page() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qbp_question_topics';

	// Check if we are currently in Edit Mode via context switches
	$is_edit_mode = ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['topic_id'] ) );
	$edit_topic_name = '';
	$target_topic_id = 0;

	if ( $is_edit_mode ) {
		$target_topic_id = absint( $_GET['topic_id'] );
		$edit_topic_name = $wpdb->get_var( $wpdb->prepare( "SELECT question_topic_name FROM {$table_name} WHERE id = %d", $target_topic_id ) );
	}

	$topics = $wpdb->get_results( "SELECT id, question_topic_name, created_at FROM {$table_name} ORDER BY id DESC" );
	
	include plugin_dir_path( __FILE__ ) . 'views/question-topics-view.php';
}

function qbp_render_questions_page() {
	global $wpdb;
	$table_question_topics    = $wpdb->prefix . 'qbp_question_topics';

	$topics    = $wpdb->get_results( "SELECT id, question_topic_name FROM {$table_question_topics} WHERE is_active = 1 ORDER BY question_topic_name ASC" );
	$qbp_questions_sort = qbp_get_questions_sort_args();
	$questions          = qbp_get_questions_for_display( $qbp_questions_sort['orderby'], $qbp_questions_sort['order'] );

	include plugin_dir_path( __FILE__ ) . 'views/questions-view.php';
}	

/**
 * 6. CORE RENDERING ENGINE (MANAGE QUIZZES VIEW)
 */
function qbp_render_quizzes_page() {
	global $wpdb;
	$quizzes_table = $wpdb->prefix . 'qbp_quizzes';
	$quiz_topics_table = $wpdb->prefix . 'qbp_quiz_topics';
	$mapping_table = $wpdb->prefix . 'qbp_quiz_question_mapping';
	$table_questions = $wpdb->prefix . 'qbp_questions';

	// Catch safe mapping submissions
	if ( isset( $_POST['qbp_map_action'] ) && $_POST['qbp_map_action'] === 'save_mapping' ) {
		if ( ! isset( $_POST['qbp_map_nonce'] ) || ! wp_verify_nonce( $_POST['qbp_map_nonce'], 'qbp_save_map_verify' ) ) {
			wp_die( __( 'Security check failed.', 'quizbank-pro' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized access.', 'quizbank-pro' ) );
		}

		$current_quiz_id = absint( $_POST['target_quiz_id'] );
		
		// Flush out the current mapping array for this quiz to prevent primary key duplications
		$wpdb->delete( $mapping_table, array( 'quiz_id' => $current_quiz_id ), array( '%d' ) );

		$assigned_questions = isset( $_POST['assigned_questions'] ) ? (array) $_POST['assigned_questions'] : array();
		$sort_idx = 0;
		foreach ( $assigned_questions as $q_id ) {
			$wpdb->insert(
				$mapping_table,
				array(
					'quiz_id'     => $current_quiz_id,
					'question_id' => absint( $q_id ),
					'sort_order'  => $sort_idx,
					'created_at'  => current_time( 'mysql', 1 )
				),
				array( '%d', '%d', '%d', '%s' )
			);
			$sort_idx++;
		}
		add_settings_error( 'qbp_messages', 'qbp_map_success', __( 'Quiz question mappings updated successfully!', 'quizbank-pro' ), 'updated' );
	}

	// Active navigation assignment mode check
	$active_quiz_id = isset( $_GET['manage_mapping'] ) ? absint( $_GET['manage_mapping'] ) : 0;

	// Query lists for context parsing
	$quiz_topics      = $wpdb->get_results( "SELECT id, quiz_topic_name FROM {$quiz_topics_table} WHERE is_active = 1 ORDER BY quiz_topic_name ASC" );
	$qbp_quizzes_sort = qbp_get_quizzes_sort_args();
	$quizzes          = qbp_get_quizzes_for_display( $qbp_quizzes_sort['orderby'], $qbp_quizzes_sort['order'] );

	include plugin_dir_path( __FILE__ ) . 'views/quizzes-view.php';

}
