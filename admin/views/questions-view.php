<?php
if ( ! defined( "ABSPATH" ) ) exit;

$form_state = function_exists( 'qbp_get_question_form_state' ) ? qbp_get_question_form_state() : array();

$is_edit_mode       = ! empty( $form_state['is_edit_mode'] );
$target_question_id = isset( $form_state['target_question_id'] ) ? absint( $form_state['target_question_id'] ) : 0;
$form_topic_id      = isset( $form_state['question_topic_id'] ) ? absint( $form_state['question_topic_id'] ) : 0;
$form_question_text = isset( $form_state['question_text'] ) ? $form_state['question_text'] : '';
$form_question_hint = isset( $form_state['question_hint'] ) ? $form_state['question_hint'] : '';
$form_is_multi      = ! empty( $form_state['is_multiselect'] );
$form_image         = isset( $form_state['question_image'] ) ? $form_state['question_image'] : '';
$form_answers       = isset( $form_state['answers'] ) && is_array( $form_state['answers'] ) ? array_values( $form_state['answers'] ) : array();
$form_correct       = isset( $form_state['correct_answers'] ) && is_array( $form_state['correct_answers'] ) ? array_values( array_map( 'strval', $form_state['correct_answers'] ) ) : array();
$questions_sort     = isset( $qbp_questions_sort ) && is_array( $qbp_questions_sort ) ? $qbp_questions_sort : array( 'orderby' => 'id', 'order' => 'desc' );
$current_orderby    = isset( $questions_sort['orderby'] ) ? sanitize_key( $questions_sort['orderby'] ) : 'id';
$current_order      = isset( $questions_sort['order'] ) ? sanitize_key( $questions_sort['order'] ) : 'desc';
$sortable_header    = function( $column, $label ) use ( $current_orderby, $current_order ) {
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
    qbp_render_admin_tabs( 'qbp-questions' ); 
    settings_errors( 'qbp_messages' );
    ?>

    <div id="col-container" class="wp-clearfix" style="margin-top: 20px;">
        
        <!-- LEFT COLUMN: Questionnaire Form Entry -->
        <div id="col-left" style="width: 45%; float: left;">
            <div class="form-wrap">
                <h2>
                    <?php
                    echo $is_edit_mode
                        ? esc_html__( 'Edit Question', 'quizbank-pro' )
                        : esc_html__( 'Add New Question', 'quizbank-pro' );
                    ?>
                </h2>
                <?php if ( empty( $topics ) ) : ?>
                    <div class="notice notice-warning inline"><p><?php esc_html_e( 'Please create at least one Topic before adding questions.', 'quizbank-pro' ); ?></p></div>
                <?php else : ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'qbp_question_form_verify', 'qbp_question_nonce' ); ?>
                        <input type="hidden" name="qbp_question_action" value="<?php echo esc_attr( $is_edit_mode ? 'edit_question' : 'add_question' ); ?>">
                        <?php if ( $is_edit_mode ) : ?>
                            <input type="hidden" name="target_question_id" value="<?php echo esc_attr( $target_question_id ); ?>">
                            <input type="hidden" name="current_question_image" value="<?php echo esc_attr( $form_image ); ?>">
                        <?php endif; ?>

                        <div class="form-field form-required">
                            <label for="question_topic_id"><?php esc_html_e( 'Select Topic for this Question', 'quizbank-pro' ); ?></label>
                            <select name="question_topic_id" id="question_topic_id" required style="max-width:100%;">
                                <option value=""><?php esc_html_e( 'Select a question topic', 'quizbank-pro' ); ?></option>
                                <?php foreach ( $topics as $topic ) : ?>
                                    <option value="<?php echo esc_attr( $topic->id ); ?>" <?php selected( $form_topic_id, $topic->id ); ?>><?php echo esc_html( $topic->question_topic_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p><?php esc_html_e( 'Groups this question under one of your active Question Topics.', 'quizbank-pro' ); ?></p>
                        </div>

                        <div class="form-field form-required">
                            <label for="question_text"><?php esc_html_e( 'Question', 'quizbank-pro' ); ?></label>
                            <textarea name="question_text" id="question_text" rows="3" required style="max-width:100%;"><?php echo esc_textarea( $form_question_text ); ?></textarea>
                        </div>

                        <div class="form-field">
                            <label for="question_hint"><?php esc_html_e( 'Question Hint (Optional)', 'quizbank-pro' ); ?></label>
                            <textarea name="question_hint" id="question_hint" rows="3" style="max-width:100%;"><?php echo esc_textarea( $form_question_hint ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Add an optional hint to help learners with this question.', 'quizbank-pro' ); ?></p>
                        </div>

                        <div class="form-field" style="margin-bottom:15px;">
                            <label for="question_image"><?php esc_html_e( 'Question Image (Optional)', 'quizbank-pro' ); ?></label>
                            <input type="file" name="question_image" id="question_image" accept="image/*" class="qbp-file-input">
                            
                            <div id="qbp-preview-container" class="qbp-preview-container<?php echo ! empty( $form_image ) ? ' is-visible' : ''; ?>">
                                <img
                                    id="qbp-image-preview"
                                    class="qbp-image-preview"
                                    src="<?php echo ! empty( $form_image ) ? esc_url( $form_image ) : '#'; ?>"
                                    data-original-src="<?php echo ! empty( $form_image ) ? esc_url( $form_image ) : ''; ?>"
                                    alt="<?php esc_attr_e( 'Question image preview', 'quizbank-pro' ); ?>"
                                >
                            </div>
                            <?php if ( ! empty( $form_image ) ) : ?>
                                <label for="remove_question_image" class="qbp-question-image-remove">
                                    <input type="checkbox" name="remove_question_image" id="remove_question_image" value="1">
                                    <?php esc_html_e( 'Remove current image', 'quizbank-pro' ); ?>
                                </label>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e( 'Supports PNG, JPG, or GIF schemas.', 'quizbank-pro' ); ?></p>
                        </div>

                        <div class="form-field" style="margin: 12px 0;">
                            <label for="is_multiselect">
                                <input type="checkbox" name="is_multiselect" id="is_multiselect" value="1" <?php checked( $form_is_multi ); ?>> 
                                <?php esc_html_e( 'Multi-Select (Allows choosing multiple correct answers)', 'quizbank-pro' ); ?>
                            </label>
                        </div>

                        <div id="qbp-answers-wrapper" class="qbp-answer-builder">
                            <h3 style="margin-top:0; margin-bottom: 12px;"><?php esc_html_e( 'Answer Choices Options', 'quizbank-pro' ); ?></h3>
                            <div
                                id="qbp-rows-container"
                                data-answers="<?php echo esc_attr( wp_json_encode( $form_answers ) ); ?>"
                                data-correct="<?php echo esc_attr( wp_json_encode( $form_correct ) ); ?>"
                            ></div>
                            <div class="qbp-answer-builder-actions">
                                <button type="button" id="qbp-add-row" class="button button-secondary" style="font-weight:600;">+ Add More Options</button>
                            </div>
                        </div>

                        <?php if ( $is_edit_mode ) : ?>
                            <div style="display:flex; gap:10px; margin-top:15px; align-items:center;">
                                <?php submit_button( __( 'Update Question', 'quizbank-pro' ), 'primary', 'submit', false ); ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=qbp-questions' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'quizbank-pro' ); ?></a>
                            </div>
                        <?php else : ?>
                            <?php submit_button( __( 'Save Question', 'quizbank-pro' ) ); ?>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: Questions Inventory Presentation -->
        <div id="col-right" style="width: 52%; float: right;">
            <table
                id="qbp-questions-table"
                class="wp-list-table widefat fixed striped table-view-list qbp-questions-table"
                data-current-orderby="<?php echo esc_attr( $current_orderby ); ?>"
                data-current-order="<?php echo esc_attr( $current_order ); ?>"
            >
                <thead>
                    <tr>
                        <th scope="col" style="width: 12%;"><?php echo $sortable_header( 'id', __( 'ID', 'quizbank-pro' ) ); ?></th>
                        <th scope="col" style="width: 15%; text-align:center;"><?php esc_html_e( 'Image', 'quizbank-pro' ); ?></th>
                        <th scope="col" style="width: 37%;"><?php esc_html_e( 'Question', 'quizbank-pro' ); ?></th>
                        <th scope="col" style="width: 23%;"><?php echo $sortable_header( 'topic', __( 'Topic', 'quizbank-pro' ) ); ?></th>
                        <th scope="col" style="width: 15%;"><?php echo $sortable_header( 'is_multiselect', __( 'Is Multi-Select', 'quizbank-pro' ) ); ?></th>
                    </tr>
                </thead>
                <tbody id="qbp-questions-table-body">
                    <?php echo function_exists( 'qbp_render_questions_table_rows' ) ? qbp_render_questions_table_rows( $questions ) : ''; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
