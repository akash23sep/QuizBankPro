<?php
if ( ! defined( "ABSPATH" ) ) exit;
?>
<div class="wrap">
    <h1><?php esc_html_e( 'QuizBank Pro Dashboard', 'quizbank-pro' ); ?></h1>
    <?php 
    qbp_render_admin_tabs( 'qbp-question-topics' ); 
    settings_errors( 'qbp_messages' );
    ?>
    <div id="col-container" class="wp-clearfix" style="margin-top: 20px;">
        
        <!-- LEFT COLUMN: Dynamic Context Switch Form -->
        <div id="col-left" style="width: 35%; float: left;">
            <div class="form-wrap">
                <?php if ( $is_edit_mode ) : ?>
                    <h2><?php esc_html_e( 'Edit Topic', 'quizbank-pro' ); ?></h2>
                    <form method="POST" action="">
                        <?php wp_nonce_field( 'qbp_question_topic_form_verify', 'qbp_question_topic_nonce' ); ?>
                        <input type="hidden" name="qbp_question_topic_action" value="edit_topic">
                        <input type="hidden" name="target_topic_id" value="<?php echo esc_attr( $target_topic_id ); ?>">
                        
                        <div class="form-field form-required">
                            <label for="question_topic_name"><?php esc_html_e( 'Question Topic Name', 'quizbank-pro' ); ?></label>
                            <input type="text" name="question_topic_name" id="question_topic_name" value="<?php echo esc_attr( $edit_topic_name ); ?>" required style="max-width: 100%;">
                        </div>
                        <div style="display:flex; gap:10px; margin-top:15px; align-items:center;">
                            <?php submit_button( __( 'Update Topic', 'quizbank-pro' ), 'primary', 'submit', false ); ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=qbp-question-topics' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'quizbank-pro' ); ?></a>
                        </div>
                    </form>
                <?php else : ?>
                    <h2><?php esc_html_e( 'Add New Topic for Questions', 'quizbank-pro' ); ?></h2>
                    <form method="POST" action="">
                        <?php wp_nonce_field( 'qbp_question_topic_form_verify', 'qbp_question_topic_nonce' ); ?>
                        <input type="hidden" name="qbp_question_topic_action" value="add_topic">
                        <div class="form-field form-required">
                            <label for="question_topic_name"><?php esc_html_e( 'Question Topic Name', 'quizbank-pro' ); ?></label>
                            <input type="text" name="question_topic_name" id="question_topic_name" required style="max-width: 100%;">
                        </div>
                        <?php submit_button( __( 'Save Question Topic', 'quizbank-pro' ) ); ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: Inventory with Row Actions Buttons -->
        <div id="col-right" style="width: 62%; float: right;">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" style="width: 15%;"><?php esc_html_e( 'ID', 'quizbank-pro' ); ?></th>
                        <th scope="col" style="width: 55%;"><?php esc_html_e( 'Question Topic Name', 'quizbank-pro' ); ?></th>
                        <th scope="col" style="width: 30%;"><?php esc_html_e( 'Created (EST)', 'quizbank-pro' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $topics ) ) : ?>
                        <tr><td colspan="3"><?php esc_html_e( 'No topics found for Questions.', 'quizbank-pro' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $topics as $topic ) : 
                            // Prepare security nonces for safe deletions
                            $delete_nonce = wp_create_nonce( 'qbp_delete_topic_' . $topic->id );
                            $edit_url   = admin_url( 'admin.php?page=qbp-question-topics&action=edit&topic_id=' . $topic->id );
                            $delete_url = admin_url( 'admin.php?page=qbp-question-topics&action=delete&topic_id=' . $topic->id . '&_wpnonce=' . $delete_nonce );
                            ?>
                            <tr class="qbp-row-hover">
                                <td><strong><?php echo esc_html( $topic->id ); ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; color: #23282d; font-size:14px;"><?php echo esc_html( $topic->question_topic_name ); ?></div>
                                    <!-- Native row-actions menu wrapper handles visibility cleanly on hover -->
                                    <div class="row-actions" style="font-size:12px; padding:2px 0 0 0;">
                                        <span class="edit">
                                            <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'quizbank-pro' ); ?></a> | 
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo esc_url( $delete_url ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php esc_html_e( 'Are you absolutely sure you want to delete this topic?', 'quizbank-pro' ); ?>');">
                                                <?php esc_html_e( 'Delete', 'quizbank-pro' ); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    try {
                                        $utc_time = new DateTimeImmutable( $topic->created_at, new DateTimeZone( 'UTC' ) );
                                        $est_time = $utc_time->setTimezone( new DateTimeZone( 'America/New_York' ) );
                                        echo esc_html( $est_time->format( 'F j, Y g:i a' ) );
                                    } catch ( Exception $e ) {
                                        echo esc_html( $topic->created_at );
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>