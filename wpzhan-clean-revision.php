<?php
/**
 * Plugin Name: wpzhan-clean-revision
 * Plugin URI: https://github.com/wordpresszhan/wpzhan-clean-revision
 * Description: 清除网站文章的历史版本
 * Author: worldpresszhan
 * Author URI: https://www.wordpresszhan.com
 * Version: 0.0.1
 * License: GPLv2
 */

/**
 * 插件的主文件
 */

# 只有管理员才能删除历史版本
if(!is_admin()) {
    return ;
}


function wz_clean_revision() {
    global $post, $wpdb, $table_prefix;

    $post_id = $post->ID;
    $all_revision_count = $wpdb->get_var($wpdb->prepare("select count(*) from `{$table_prefix}posts` where `post_parent` = {$post_id}", ""));
?>
    <p>当前一共有 <span style="color: red"><?php echo $all_revision_count; ?></span>个版本</p>
<p>输入数字表示保留最近的几个历史版本</p>
<p>默认保留不保留历史版本</p>

    <input type="text" id="wz_count" name="wz_count" value="0" />
    <input type="hidden" id="wz_postid" name="wz_postid" value="<?php echo $post->ID; ?>" />
    <input type="hidden" id="wz_clean_revision" name="wz_clean_revision" value='yes' />
    <input type="button" id="wz_submit" value="提交"  />

    <script>
        jQuery(function() {
            jQuery("#wz_submit").click(function () {
                //alert(jQuery("#wz_count").val());
                //jQuery.post(ajaxurl, {  #ok
                jQuery.post("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                    "action" : "wz_clean_revision",
                    "wz_count" : jQuery("#wz_count").val(),
                    "wz_postid" : jQuery("#wz_postid").val()
                }, function (response) {
                    var data = jQuery.parseJSON(response);
                    if(data.code === 200) {
                        alert("操作成功");
                        //window.href = location.href;
                        location.reload() 
                    } else {
                        alert("操作失败");
                    }
                });
            });
        
        });
    </script>
<?php
}



/**
 * 如果存在历史版本则创建 meta_box
 */
function wz_register_meta_boxes() {
    global $wpdb, $table_prefix, $post;

    $all_revision_count = $wpdb->get_var($wpdb->prepare("select count(*) from `{$table_prefix}posts` where `post_parent` = {$post->ID}", ""));
    if($all_revision_count) {
        add_meta_box("wpzhan-clean-revision", "删除历史版本", 'wz_clean_revision', 'post', 'side', 'high');
    }
}

add_action( 'add_meta_boxes', 'wz_register_meta_boxes' );


/**
 * 参数
 */
function wz_ajax_clean_revision() {
    global $wpdb;
    global $table_prefix;
    # 文章的id
    $post_id = (int) $_POST['wz_postid'];
    # 保留的历史版本数
    $count = (int) $_POST['wz_count'];
    # 获取一共有多少个历史版本
    $sql1 = "select count(*) from `{$table_prefix}post` where `post_parent` = $post_id";
    $all_revision_count = $wpdb->get_var($wpdb->prepare("select count(*) from `{$table_prefix}posts` where `post_parent` = {$post_id}", ""));
    $count = $all_revision_count - $count;
    // 如果保留的版本比现在版本都多那么 ...
    if($count < 0) $count = 0;
    
    $sql =  "delete from `{$table_prefix}posts` where `post_type`='revision' and `post_parent` = $post_id order by id asc limit $count  " ;
    $ans = $wpdb->query($sql);

    if($ans !== false) {
        echo  json_encode(array("code" => 200, "count" => (int)$ans, "msg" => "ok"));
    } else {
        echo  json_encode(array("code" => 500, "msg" => "error"));
    }    
    #!!! 正常停止
    exit (0) ;
}

add_action('wp_ajax_wz_clean_revision', "wz_ajax_clean_revision");


