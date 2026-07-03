<?php
if ( ! defined( "ABSPATH" ) ) exit;

$quizzes_sort    = isset( $qbp_quizzes_sort ) && is_array( $qbp_quizzes_sort ) ? $qbp_quizzes_sort : array( 'orderby' => 'id', 'order' => 'desc' );
$current_orderby = isset( $quizzes_sort['orderby'] ) ? sanitize_key( $quizzes_sort['orderby'] ) : 'id';
$current_order   = isset( $quizzes_sort['order'] ) ? sanitize_key( $quizzes_sort['order'] ) : 'desc';
$sortable_header = function( $column, $label ) use ( $current_orderby, $current_order ) {
    $column        = sanitize_key( $column );
    $is_active     = $current_orderby === $column;
    $current_order = 'asc' === $current_order ? 'asc' : 'desc';
    $next_order    = $is_active && 'asc' === $current_order ? 'desc' : 'asc';
    $arrow         = 'desc' === $current_order ? '&uarr;' : '&darr;';

    ob_start();
    ?>
    <button
        type="button"
        class="qbp-sort-button<?php echo $is_active ? ' is-active' : ''; ?>"
        data-sort-column="<?php echo esc_attr( $column ); ?>"
        data-sort-next-order="<?php echo esc_attr( $next_order ); ?>"
        aria-sort="<?php echo $is_active ? esc_attr( 'desc' === $current_order ? 'descending' : 'ascending' ) : 'none'; ?>"
    >
        <span><?php echo esc_html( $label ); ?></span>
        <span class="qbp-sort-arrow" aria-hidden="true"><?php echo $is_active ? $arrow : ''; ?></span>
    </button>
    <?php
    return ob_get_clean();
};
?>
<div class="wrap">
    <h1><?php esc_html_e( 'QuizBank Pro Dashboard', 'quizbank-pro' ); ?></h1>
    <?php 
    qbp_render_admin_tabs( 'quizbank-pro' ); 
    settings_errors( 'qbp_messages' );

    $is_edit_mode = false;
    $edit_quiz    = null;
    $action       = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

    if ( isset( $_GET['quiz_id'] ) && 'edit' === $action ) {
        $target_quiz_id = absint( wp_unslash( $_GET['quiz_id'] ) );
        if ( $target_quiz_id ) {
            $edit_quiz = $wpdb->get_row( $wpdb->prepare( "SELECT id, title, quiz_topic_id, layout_style, quiz_image FROM {$quizzes_table} WHERE id = %d", $target_quiz_id ) );
            $is_edit_mode = (bool) $edit_quiz;
        }
    }
    ?>

    <div id="col-container" class="wp-clearfix" style="margin-top: 20px;">
        
        <!-- LEFT COLUMN: Form Context Switching Engine -->
        <div id="col-left" style="width: 35%; float: left;">
            <?php if ( $active_quiz_id ) : 
                $target_title = $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$quizzes_table} WHERE id = %d", $active_quiz_id ) );
                $mapped_ids   = $wpdb->get_col( $wpdb->prepare( "SELECT question_id FROM {$mapping_table} WHERE quiz_id = %d ORDER BY sort_order ASC", $active_quiz_id ) );
                $all_questions = $wpdb->get_results( "SELECT id, question_text FROM {$table_questions} WHERE is_active = 1 ORDER BY id DESC" );
                ?>
                <div class="form-wrap" style="background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:3px;">
                    <h2><?php printf( esc_html__( 'Map Questions: %s', 'quizbank-pro' ), esc_html( $target_title ) ); ?></h2>
                    <form method="POST" action="">
                        <?php wp_nonce_field( 'qbp_save_map_verify', 'qbp_map_nonce' ); ?>
                        <input type="hidden" name="qbp_map_action" value="save_mapping">
                        <input type="hidden" name="target_quiz_id" value="<?php echo esc_attr( $active_quiz_id ); ?>">
                        
                        <p class="description" style="margin-bottom:15px;"><?php esc_html_e( 'Check the boxes next to the questions you want to assign to this quiz.', 'quizbank-pro' ); ?></p>
                        
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fdfdfd; margin-bottom: 15px; border-radius: 3px;">
                            <?php if ( empty( $all_questions ) ) : ?>
                                <p><?php esc_html_e( 'No master questions exist yet.', 'quizbank-pro' ); ?></p>
                            <?php else : ?>
                                <?php foreach ( $all_questions as $q_row ) : 
                                    $is_checked = in_array( (string)$q_row->id, $mapped_ids, true ) ? 'checked' : '';
                                    ?>
                                    <div style="margin-bottom:10px; display:flex; align-items:flex-start; gap:8px;">
                                        <input type="checkbox" name="assigned_questions[]" value="<?php echo esc_attr( $q_row->id ); ?>" <?php echo $is_checked; ?> style="margin-top:2px;">
                                        <label style="font-weight:normal; line-height:1.3;">
                                            <strong>#<?php echo esc_html( $q_row->id ); ?>:</strong> <?php echo esc_html( wp_trim_words( $q_row->question_text, 10, '...' ) ); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex; gap:10px; align-items:center;">
                            <?php submit_button( __( 'Save Assignments', 'quizbank-pro' ), 'primary', 'submit', false ); ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=quizbank-pro' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'quizbank-pro' ); ?></a>
                        </div>
                    </form>
                </div>
            <?php elseif ( $is_edit_mode ) : ?>
                <div class="form-wrap">
                    <h2><?php esc_html_e( 'Edit Quiz', 'quizbank-pro' ); ?></h2>
                    <?php if ( empty( $quiz_topics ) ) : ?>
                        <div class="notice notice-warning inline"><p><?php esc_html_e( 'Please create at least one Quiz Topic before editing quizzes.', 'quizbank-pro' ); ?></p></div>
                    <?php else : ?>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?php wp_nonce_field( 'qbp_edit_quiz_verify', 'qbp_quiz_nonce' ); ?>
                            <input type="hidden" name="qbp_quiz_action" value="edit_quiz">
                            <input type="hidden" name="target_quiz_id" value="<?php echo esc_attr( $edit_quiz->id ); ?>">
                        
                            <div class="form-field form-required">
                                <label for="quiz_title"><?php esc_html_e( 'Quiz Title', 'quizbank-pro' ); ?></label>
                                <input type="text" name="quiz_title" id="quiz_title" value="<?php echo esc_attr( $edit_quiz->title ); ?>" required style="max-width: 100%;">
                                <p><?php esc_html_e( 'The display name for your quiz module instance.', 'quizbank-pro' ); ?></p>
                            </div>

                            <div class="form-field form-required">
                                <label for="quiz_topic_id"><?php esc_html_e( 'Select Topic for this Quiz', 'quizbank-pro' ); ?></label>
                                <select name="quiz_topic_id" id="quiz_topic_id" required style="max-width:100%;">
                                    <option value=""><?php esc_html_e( 'Select a quiz topic', 'quizbank-pro' ); ?></option>
                                    <?php foreach ( $quiz_topics as $quiz_topic ) : ?>
                                        <option value="<?php echo esc_attr( $quiz_topic->id ); ?>" <?php selected( (int) $edit_quiz->quiz_topic_id, (int) $quiz_topic->id ); ?>><?php echo esc_html( $quiz_topic->quiz_topic_name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p><?php esc_html_e( 'Groups this quiz under one of your active Quiz Topics.', 'quizbank-pro' ); ?></p>
                            </div>

                            <div class="form-field">
                                <label for="layout_style"><?php esc_html_e( 'Layout Engine Style', 'quizbank-pro' ); ?></label>
                                <select name="layout_style" id="layout_style">
                                    <option value="paged" <?php selected( $edit_quiz->layout_style, 'paged' ); ?>><?php esc_html_e( 'Paged (One Question Per Screen View)', 'quizbank-pro' ); ?></option>
                                    <option value="single_scroll" <?php selected( $edit_quiz->layout_style, 'single_scroll' ); ?>><?php esc_html_e( 'Single Page (All Questions Listed Vertically)', 'quizbank-pro' ); ?></option>
                                </select>
                                <p><?php esc_html_e( 'Determines frontend application user interface flow logic.', 'quizbank-pro' ); ?></p>
                            </div>

                            <div class="form-field qbp-image-upload-field">
                                <label for="quiz_image"><?php esc_html_e( 'Quiz Image (Optional)', 'quizbank-pro' ); ?></label>
                                <input type="file" name="quiz_image" id="quiz_image" accept="image/*" class="qbp-file-input">
                            
                                <div id="qbp-quiz-preview-container" class="qbp-preview-container" style="<?php echo ! empty( $edit_quiz->quiz_image ) ? 'display: block;' : 'display: none;'; ?>">
                                    <img id="qbp-quiz-image-preview" class="qbp-image-preview" src="<?php echo ! empty( $edit_quiz->quiz_image ) ? esc_url( $edit_quiz->quiz_image ) : '#'; ?>" data-original-src="<?php echo esc_attr( $edit_quiz->quiz_image ); ?>" alt="<?php esc_attr_e( 'Quiz image live preview', 'quizbank-pro' ); ?>">
                                </div>
                                <?php if ( ! empty( $edit_quiz->quiz_image ) ) : ?>
                                    <label class="qbp-question-image-remove">
                                        <input type="checkbox" name="remove_quiz_image" id="remove_quiz_image" value="1">
                                        <?php esc_html_e( 'Remove current image', 'quizbank-pro' ); ?>
                                    </label>
                                <?php endif; ?>
                                <p class="description"><?php esc_html_e( 'Upload a new image to replace the current quiz image.', 'quizbank-pro' ); ?></p>
                            </div>

                            <div style="display:flex; gap:10px; align-items:center;">
                                <?php submit_button( __( 'Update Quiz Module', 'quizbank-pro' ), 'primary', 'submit', false ); ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=quizbank-pro' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'quizbank-pro' ); ?></a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="form-wrap">
                    <h2><?php esc_html_e( 'Create New Quiz', 'quizbank-pro' ); ?></h2>
                    <?php if ( empty( $quiz_topics ) ) : ?>
                        <div class="notice notice-warning inline"><p><?php esc_html_e( 'Please create at least one Quiz Topic before adding quizzes.', 'quizbank-pro' ); ?></p></div>
                    <?php else : ?>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?php wp_nonce_field( 'qbp_add_quiz_verify', 'qbp_quiz_nonce' ); ?>
                            <input type="hidden" name="qbp_quiz_action" value="add_quiz">
                        
                            <div class="form-field form-required">
                                <label for="quiz_title"><?php esc_html_e( 'Quiz Title', 'quizbank-pro' ); ?></label>
                                <input type="text" name="quiz_title" id="quiz_title" required style="max-width: 100%;">
                                <p><?php esc_html_e( 'The display name for your quiz module instance.', 'quizbank-pro' ); ?></p>
                            </div>

                            <div class="form-field form-required">
                                <label for="quiz_topic_id"><?php esc_html_e( 'Select Topic for this Quiz', 'quizbank-pro' ); ?></label>
                                <select name="quiz_topic_id" id="quiz_topic_id" required style="max-width:100%;">
                                    <option value=""><?php esc_html_e( 'Select a quiz topic', 'quizbank-pro' ); ?></option>
                                    <?php foreach ( $quiz_topics as $quiz_topic ) : ?>
                                        <option value="<?php echo esc_attr( $quiz_topic->id ); ?>"><?php echo esc_html( $quiz_topic->quiz_topic_name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p><?php esc_html_e( 'Groups this quiz under one of your active Quiz Topics.', 'quizbank-pro' ); ?></p>
                            </div>

                            <div class="form-field">
                                <label for="layout_style"><?php esc_html_e( 'Layout Engine Style', 'quizbank-pro' ); ?></label>
                                <select name="layout_style" id="layout_style">
                                    <option value="paged"><?php esc_html_e( 'Paged (One Question Per Screen View)', 'quizbank-pro' ); ?></option>
                                    <option value="single_scroll"><?php esc_html_e( 'Single Page (All Questions Listed Vertically)', 'quizbank-pro' ); ?></option>
                                </select>
                                <p><?php esc_html_e( 'Determines frontend application user interface flow logic.', 'quizbank-pro' ); ?></p>
                            </div>

                            <div class="form-field qbp-image-upload-field">
                                <label for="quiz_image"><?php esc_html_e( 'Quiz Image (Optional)', 'quizbank-pro' ); ?></label>
                                <input type="file" name="quiz_image" id="quiz_image" accept="image/*" class="qbp-file-input">
                            
                                <div id="qbp-quiz-preview-container" class="qbp-preview-container" style="display: none;">
                                    <img id="qbp-quiz-image-preview" class="qbp-image-preview" src="#" alt="<?php esc_attr_e( 'Quiz image live preview', 'quizbank-pro' ); ?>">
                                </div>
                                <p class="description"><?php esc_html_e( 'Supports PNG, JPG, or GIF images for quiz dashboard and frontend display usage.', 'quizbank-pro' ); ?></p>
                            </div>


                            <?php submit_button( __( 'Create Quiz', 'quizbank-pro' ) ); ?>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: WP List Table Representation -->
        <div id="col-right" style="width: 62%; float: right;">
            <table
                id="qbp-quizzes-table"
                class="wp-list-table widefat fixed striped table-view-list qbp-quizzes-table"
                data-current-orderby="<?php echo esc_attr( $current_orderby ); ?>"
                data-current-order="<?php echo esc_attr( $current_order ); ?>"
            >
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-id" style="width: 10%;"><?php echo $sortable_header( 'id', __( 'ID', 'quizbank-pro' ) ); ?></th>
                        <th scope="col" class="manage-column column-image" style="width: 15%; text-align: center;"><?php esc_html_e( 'Image', 'quizbank-pro' ); ?></th>
                        <th scope="col" class="manage-column column-title" style="width: 30%;"><?php esc_html_e( 'Quiz Title', 'quizbank-pro' ); ?></th>
                        <th scope="col" class="manage-column column-topic" style="width: 18%;"><?php echo $sortable_header( 'topic', __( 'Topic', 'quizbank-pro' ) ); ?></th>
                        <th scope="col" class="manage-column column-style" style="width: 17%;"><?php echo $sortable_header( 'layout_style', __( 'Layout Style', 'quizbank-pro' ) ); ?></th>
                        <th scope="col" class="manage-column column-count" style="width: 10%; text-align: center;"><?php echo $sortable_header( 'questions', __( 'Questions', 'quizbank-pro' ) ); ?></th>
                    </tr>
                </thead>
                <tbody id="qbp-quizzes-table-body">
                    <?php echo function_exists( 'qbp_render_quizzes_table_rows' ) ? qbp_render_quizzes_table_rows( $quizzes ) : ''; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
