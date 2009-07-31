<?php
require_once(dirname(__FILE__) . '/../../../wp-config.php');
require_once(dirname(__FILE__) . '/../tweetbacks/tweetbacks.php' );
nocache_headers();

//debug - Set it false in production
define("TW_DEBUG", true);

$options = get_option('tweetbacks-helper-options');

if ($options['cron-enabled'] == "1") {

    // get current counter
    $current = get_option('tweetback-helper-current');
    $current = ($current > 0) ? $current: "0";

    twhelper_debug("Current starting value: $current <br />");

    // fetch at a max of 50 posts
    $posts = $wpdb->get_results("select ID from {$wpdb->posts} where ID > $current and post_type = 'post' order by ID LIMIT 0, 50");

    if (count($posts) < 1) {
        // if no more posts found reset the counter to 0
        twhelper_debug("Reseting counter");
        update_option('tweetback-helper-current', '0');
    } else {
        // if posts found, process them.
        foreach ($posts as $post) {
            $current = $post->ID;
            twhelper_debug("Processing $current <br>");
            yoast_get_tweetback($current);
            sleep( 2 );
            @set_time_limit(60);
        }

        twhelper_debug("Current: $current <br /> ");
        update_option('tweetback-helper-current', $current);
    }
} else {
    twhelper_debug("Manual Cron is not enabled<br /> ");
    
}
/**
 * print debug messages
 * @param <type> $msg
 */
function twhelper_debug($msg) {
    if (TW_DEBUG) {
        echo $msg;
    }
}
?>