<?php
if (!defined('ABSPATH')) exit;

function tfp_dashboard_render_week_meeting_experience($week)
{
    $user_id = get_current_user_id();
    $lesson_id = $week->ID;
    $progress = tfp_ld_get_week_progress($user_id, $lesson_id);
    $allowed = ['attendance','homework','test','exercise','notes'];
    $active = isset($_GET['meeting_tab']) ? sanitize_key($_GET['meeting_tab']) : 'attendance';
    if (!in_array($active, $allowed, true)) $active = 'attendance';

    $url = function ($tab, $extra = []) use ($lesson_id) {
        return add_query_arg(array_merge(['lesson_id'=>$lesson_id,'tab'=>'meeting','meeting_tab'=>$tab], $extra));
    };

    $test_result = function_exists('tfp_week_get_test_result') ? tfp_week_get_test_result($user_id, $lesson_id) : null;
    $tasks = [
        'attendance' => ['Attendance', (bool)get_user_meta($user_id,'tfp_week_meeting_attendance_'.$lesson_id,true)],
        'homework' => ['Homework Review', !empty($progress['homework'])],
        'test' => ['Test Review', !empty($test_result)],
        'exercise' => ['Exercises', false],
        'notes' => ['Weekly Notes', trim((string)get_user_meta($user_id,'tfp_week_meeting_notes_'.$lesson_id,true)) !== ''],
    ];
    ?>
    <div class="tfp-week__meeting-layout">
        <aside class="tfp-week__meeting-sidebar">
            <div class="tfp-week__meeting-progress-card">
                <h5><?php esc_html_e('Weekly Progress','tfp-dashboard'); ?></h5>
                <?php foreach (['reading'=>'Reading','homework'=>'Homework','test'=>'Tests'] as $step=>$label) : $done=!empty($progress[$step]); ?>
                    <div class="tfp-week__meeting-progress-row">
                        <div class="tfp-week__meeting-progress-top"><strong><?php echo esc_html($label); ?></strong><span><?php echo $done ? esc_html__('Completed','tfp-dashboard') : esc_html__('Not Started','tfp-dashboard'); ?></span></div>
                        <div class="tfp-week__meeting-progress-bar"><span style="width:<?php echo $done?'100':'0'; ?>%"></span></div>
                    </div>
                <?php endforeach; ?>
                <h5 class="tfp-week__meeting-tasks-title"><?php esc_html_e('Meeting Tasks','tfp-dashboard'); ?></h5>
                <?php foreach ($tasks as $key=>$task) : ?>
                    <div class="tfp-week__meeting-task">
                        <div><strong><?php echo esc_html($task[0]); ?></strong></div>
                        <span class="tfp-week__meeting-status <?php echo $task[1]?'is-complete':''; ?>"><?php echo $task[1] ? esc_html__('Completed','tfp-dashboard') : esc_html__('Not Started','tfp-dashboard'); ?></span>
                        <a class="tfp-dash-btn-small tfp-dash-btn--primary" href="<?php echo esc_url($url($key)); ?>"><?php echo esc_html($key==='attendance' && $task[1] ? 'Review' : 'Start'); ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="tfp-week__meeting-content">
            <nav class="tfp-week__meeting-subtabs">
                <?php foreach (['attendance'=>'Attendance','homework'=>'Homework','test'=>'Test','exercise'=>'Exercise','notes'=>'Notes'] as $key=>$label) : ?>
                    <a href="<?php echo esc_url($url($key)); ?>" class="<?php echo $active===$key?'is-active':''; ?>"><?php echo esc_html__($label,'tfp-dashboard'); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="tfp-week__meeting-body">
                <?php if ($active==='attendance') :
                    $description=(string)get_post_meta($lesson_id,'tfp_week_meeting_description',true);
                    $date=(string)get_post_meta($lesson_id,'tfp_week_meeting_date',true);
                    $time=(string)get_post_meta($lesson_id,'tfp_week_meeting_time',true);
                    $facilitator=(string)get_post_meta($lesson_id,'tfp_week_facilitator_name',true);
                    $discord=(string)get_post_meta($lesson_id,'tfp_week_meeting_discord_url',true);
                    $discord=$discord?:apply_filters('tfp_dashboard_discord_url','#');
                ?>
                    <article class="tfp-week__meeting-attendance">
                        <h5><?php esc_html_e('Weekly Meetings','tfp-dashboard'); ?></h5>
                        <div class="tfp-week__meeting-intro"><?php echo wp_kses_post(wpautop($description ?: __("Your weekly meeting is designed to help you grow through connection and conversation. Join your facilitator and group to discuss this week's topic, share insights, and ask questions. Attendance is required to unlock the next section of your discipleship journey.",'tfp-dashboard'))); ?></div>
                        <div class="tfp-week__meeting-details">
                            <h6><span><?php esc_html_e('Facilitator:','tfp-dashboard'); ?></span> <?php echo esc_html($facilitator?:'—'); ?></p>
                            <h6><span><?php esc_html_e('Date:','tfp-dashboard'); ?></span> <?php echo esc_html($date?:'—'); ?></p>
                            <h6><span><?php esc_html_e('Time:','tfp-dashboard'); ?></span> <?php echo esc_html($time?:'—'); ?></p>
                        </div>
                        <a href="<?php echo esc_url($discord); ?>" target="_blank" rel="noopener" class="tfp-dash-btn tfp-dash-btn--primary tfp-week__meeting-join"><?php esc_html_e('Join Discord for Class','tfp-dashboard'); ?></a>
                    </article>
                <?php elseif ($active==='homework') :
                    $questions=tfp_week_get_homework_questions($lesson_id,false);
                    $answers=tfp_week_get_homework_answers($user_id,$lesson_id);
                    if (!$questions) { echo '<p class="tfp-week__meeting-empty">'.esc_html__('No homework review is available for this week.','tfp-dashboard').'</p>'; }
                    else {
                        $index=min(isset($_GET['meeting_item'])?absint($_GET['meeting_item']):0,count($questions)-1);
                        $q=$questions[$index]; $answer=$answers[$q['id']]??[];
                ?>
                    <div class="tfp-week__meeting-review">
                        <div class="tfp-week__meeting-question-rail"><?php foreach($questions as $i=>$item): ?><a href="<?php echo esc_url($url('homework',['meeting_item'=>$i])); ?>" class="<?php echo $index===$i?'is-active':''; ?>"><?php echo esc_html($i+1); ?></a><?php endforeach; ?></div>
                        <div class="tfp-week__meeting-review-main">
                            <h3><?php printf(esc_html__('Q%d. %s','tfp-dashboard'),$index+1,$q['prompt']); ?></h3>
                            <div class="tfp-week__meeting-answer-label"><span>✓</span><?php esc_html_e('Your Answer','tfp-dashboard'); ?></div>
                            <div class="tfp-week__meeting-answer-box"><?php
                                if (($q['type']??'')==='multiple_choice') { $selected=isset($answer['selected_index'])?(int)$answer['selected_index']:-1; echo esc_html($q['options'][$selected]??'—'); }
                                else { echo nl2br(esc_html($answer['text']??'—')); }
                            ?></div>
                            <h4><?php esc_html_e('Further Explanation','tfp-dashboard'); ?></h4>
                            <div class="tfp-week__meeting-explanation-box"><?php echo wp_kses_post(wpautop($q['explanation']??__('Your response is available here for review and discussion during the weekly meeting.','tfp-dashboard'))); ?></div>
                        </div>
                    </div>
                <?php } ?>
                <?php elseif ($active==='test') :
                    if (!$test_result) { echo '<p class="tfp-week__meeting-empty">'.esc_html__('Complete the test to unlock your test review.','tfp-dashboard').'</p>'; }
                    else { $missed=array_values(array_filter((array)$test_result['results'],function($item){return empty($item['is_correct']);})); ?>
                        <h3><?php esc_html_e('Test Review Summary','tfp-dashboard'); ?></h3>
                        <div class="tfp-week__meeting-summary-card">
                            <p><strong><?php esc_html_e('Score:','tfp-dashboard'); ?></strong><span><?php echo esc_html((int)$test_result['score']); ?>%</span></p>
                            <p><strong><?php esc_html_e('Results:','tfp-dashboard'); ?></strong><span><?php echo !empty($test_result['passed'])?esc_html__('Passed','tfp-dashboard'):esc_html__('Needs Review','tfp-dashboard'); ?></span></p>
                            <p><strong><?php esc_html_e('Questions Missed:','tfp-dashboard'); ?></strong><span><?php echo esc_html(count($missed).' of '.(int)$test_result['total']); ?></span></p>
                        </div>
                        <?php if ($missed): $item=$missed[0]; ?>
                            <article class="tfp-week__meeting-test-question">
                                <h3><?php echo esc_html($item['prompt']); ?></h3>
                                <p class="tfp-week__meeting-your-answer"><strong><?php esc_html_e('Your Answer:','tfp-dashboard'); ?></strong> <?php echo esc_html($item['options'][$item['selected_index']]??'—'); ?></p>
                                <p class="tfp-week__meeting-correct-answer"><strong><?php esc_html_e('Answer:','tfp-dashboard'); ?></strong> <?php echo esc_html($item['options'][$item['correct_index']]??'—'); ?></p>
                                <div class="tfp-week__meeting-explanation-box"><h4><?php esc_html_e('Explanation:','tfp-dashboard'); ?></h4><?php echo wp_kses_post(wpautop($item['explanation']??'')); ?></div>
                            </article>
                        <?php endif; ?>
                    <?php } ?>
                <?php elseif ($active==='exercise') :
                    $title=(string)get_post_meta($lesson_id,'tfp_week_meeting_exercise_title',true);
                    $format=(string)get_post_meta($lesson_id,'tfp_week_meeting_exercise_format',true);
                    $description=(string)get_post_meta($lesson_id,'tfp_week_meeting_exercise_description',true);
                    $purpose=(string)get_post_meta($lesson_id,'tfp_week_meeting_exercise_purpose',true);
                ?>
                    <article class="tfp-week__meeting-exercise">
                        <h3><?php echo esc_html($title?:__('Weekly Exercise','tfp-dashboard')); ?></h3>
                        <?php if($format): ?><p class="tfp-week__meeting-format"><?php echo esc_html($format); ?></p><?php endif; ?>
                        <h4><?php esc_html_e('Description','tfp-dashboard'); ?></h4><div><?php echo wp_kses_post(wpautop($description?:__('No exercise description has been added for this week yet.','tfp-dashboard'))); ?></div>
                        <?php if($purpose): ?><h4><?php esc_html_e('Purpose','tfp-dashboard'); ?></h4><div><?php echo wp_kses_post(wpautop($purpose)); ?></div><?php endif; ?>
                    </article>
                <?php else :
                    $reflection=(string)get_user_meta($user_id,'tfp_week_meeting_notes_'.$lesson_id,true);
                    $notes=(string)get_post_meta($lesson_id,'tfp_week_meeting_facilitator_notes',true);
                ?>
                    <article class="tfp-week__meeting-notes">
                        <h3><?php esc_html_e('Performance Notes','tfp-dashboard'); ?></h3>
                        <div class="tfp-week__meeting-performance"><p><?php echo !empty($progress['homework'])?esc_html__('Homework • Pass','tfp-dashboard'):esc_html__('Homework • Pending','tfp-dashboard'); ?></p><p><?php echo $test_result?esc_html('Test • '.($test_result['passed']?'Pass':'Review').' • '.(int)$test_result['score'].'%'):esc_html__('Test • Pending','tfp-dashboard'); ?></p></div>
                        <h4><?php esc_html_e('Facilitator Notes','tfp-dashboard'); ?></h4>
                        <div class="tfp-week__meeting-facilitator-notes"><?php echo $notes?wp_kses_post(wpautop($notes)):'<p class="tfp-week__meeting-empty">'.esc_html__('No facilitator notes have been added yet.','tfp-dashboard').'</p>'; ?></div>
                        <h4><?php esc_html_e('Weekly Close-Out Reflection','tfp-dashboard'); ?></h4>
                        <textarea class="tfp-week__meeting-reflection" rows="6" placeholder="<?php esc_attr_e('Share your reflection...','tfp-dashboard'); ?>"><?php echo esc_textarea($reflection); ?></textarea>
                        <p class="tfp-week__meeting-empty"><?php esc_html_e('Notes UI is ready; save action will be connected in the next implementation pass.','tfp-dashboard'); ?></p>
                    </article>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php
}
