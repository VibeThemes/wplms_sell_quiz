<?php

 if ( ! defined( 'ABSPATH' ) ) exit;

class WPLMS_Sell_Quiz_Init{

    public static $instance;

    var $schedule;

    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new WPLMS_Sell_Quiz_Init();

        return self::$instance;
    }

    private function __construct(){
    	add_filter('wplms_quiz_metabox',array($this,'wplms_sell_quiz_as_product'));
        add_filter('wplms_start_quiz_button',array($this,'the_quiz_button'),10,2);
        add_filter( 'bp_course_api_get_user_single_quiz_data',array($this,'check_quiz_access') ,10,3);


        add_filter('wplms_course_creation_tabs',array($this,'wplms_sell_quiz_4_0_settings'));
    

    }


    function wplms_sell_quiz_4_0_settings($tabs){
        $settings = array();
        if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
            $settings[] =array(
              'label' => __('Associated Product','wplms-sell-quiz'), // <label>
              'desc'  => __('Associated Product with the Course.','wplms-sell-quiz'), // description
              'id'  => 'vibe_quiz_product', // field id and name
              'type'  => 'selectcpt', // type of field
              'post_type'=> 'product',
                  'std'   => '','from'=>'meta',
            );
          }
          if ( in_array( 'paid-memberships-pro/paid-memberships-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) {
            $levels=pmpro_getAllLevels();
            $level_array= [];
            if(!empty($levels)){
                foreach($levels as $level){
                    $level_array[]= array('value' =>$level->id,'label'=>$level->name);
                }
            }
            $settings[] =array(
              'label' => __('PMPro Membership','wplms-sell-quiz'), // <label>
              'desc'  => __('Required Membership level for this quiz','wplms-sell-quiz'), // description
              'id'  => 'vibe_quiz_pmpro_membership', // field id and name
              'type'  => 'multiselect', // type of field
                  'options' => $level_array,'from'=>'meta',
            );
          }
          if(in_array('wplms-mycred-addon/wplms-mycred-addon.php', apply_filters('active_plugins', get_option('active_plugins')))){

          $settings[] =array( // Text Input
            'label' => __('MyCred Points','wplms-sell-quiz'), // <label>
            'desc'  => __('MyCred Points required to take this quiz.','wplms-sell-quiz'),
            'id'  => 'vibe_quiz_mycred_points', // field id and name
            'type'  => 'number', // type of field
            'from'=>'meta',
          );
        }

        foreach ($tabs['course_curriculum']['fields'] as $key => $field) {
            if($field['id'] == 'vibe_course_curriculum'){
                 if(!empty($field['curriculum_elements'])){
                    foreach ($field['curriculum_elements'] as $k => $elements) {
                        if($elements['type']=='quiz'){
                            foreach ($elements['types'] as $j => $types) {

                                array_splice($tabs['course_curriculum']['fields'][$key]['curriculum_elements'][$k]['types'][$j]['fields'], (count($tabs['course_curriculum']['fields'][$key]['curriculum_elements'][$k]['types'][$j]['fields'])-1),0,$settings);
                                 
                            }
                        }
                    }

                    
                 } 
            }
        }
        return $tabs;
    }

    function check_quiz_access($quiz_data, $request,$user_id=null){
        if(!empty($quiz_data['id'])){
            $html = $this->the_quiz_button('',$quiz_data['id'],$user_id);
            if(!empty($html)){
                $quiz_data['meta']['check_access'] = array('status'=>false,'html'=>$html);
            }
        }

        return $quiz_data;
    }

    function wplms_sell_quiz_as_product($metabox){
          if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
            $metabox['vibe_quiz_product']=array(
              'label' => __('Associated Product','wplms-sell-quiz'), // <label>
              'desc'  => __('Associated Product with the Course.','wplms-sell-quiz'), // description
              'id'  => 'vibe_quiz_product', // field id and name
              'type'  => 'selectcpt', // type of field
              'post_type'=> 'product',
                  'std'   => ''
            );
          }
          if ( in_array( 'paid-memberships-pro/paid-memberships-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && is_user_logged_in()) {
            $levels=pmpro_getAllLevels();
          foreach($levels as $level){
            $level_array[]= array('value' =>$level->id,'label'=>$level->name);
          }
            $metabox['vibe_quiz_pmpro_membership']=array(
              'label' => __('PMPro Membership','wplms-sell-quiz'), // <label>
              'desc'  => __('Required Membership level for this quiz','wplms-sell-quiz'), // description
              'id'  => 'vibe_quiz_pmpro_membership', // field id and name
              'type'  => 'multiselect', // type of field
                  'options' => $level_array,
            );
          }
          if(in_array('wplms-mycred-addon/wplms-mycred-addon.php', apply_filters('active_plugins', get_option('active_plugins')))){

          $metabox['vibe_quiz_mycred_points']=array( // Text Input
            'label' => __('MyCred Points','wplms-sell-quiz'), // <label>
            'desc'  => __('MyCred Points required to take this quiz.','wplms-sell-quiz'),
            'id'  => 'vibe_quiz_mycred_points', // field id and name
            'type'  => 'number' // type of field
          );
          }

        return $metabox;
    } 
    function the_quiz_button($button,$quiz_id,$user_id=null){

        global $post;
        if(empty($quiz_id)){
            $quiz_id=get_the_ID();
        }
        if(empty($user_id)){
            $user_id=get_current_user_id();
        }
        
        $flag = 1;
        if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
            $pid=get_post_meta($quiz_id,'vibe_quiz_product',true);
            if(isset($pid) && is_numeric($pid) && get_post_type($pid) == 'product'){
              $product_taken = wc_customer_bought_product('',$user_id,$pid);
                if(!$product_taken){
                  $product = wc_get_product($pid);
                  $pid=get_permalink($pid);
                  $check=vibe_get_option('direct_checkout');
                  $check =intval($check);
                  if(isset($check) &&  $check){
                    $pid .= '?redirect';
                    }
                    $flag=0;
                    
                    $html='<a href="'.$pid.'"class="button start_quiz_button full is-primary"> '.__('Purchase Quiz','vibe').'<span>'.$product->get_price_html().'</span></a>';
                }else{
                    $flag=1;
                }
            }
        }
        if ( in_array( 'paid-memberships-pro/paid-memberships-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
             $membership_ids=get_post_meta($quiz_id,'vibe_quiz_pmpro_membership',true);
             if(empty($membership_ids)){
                $membership_ids =array();
             }
             if(!empty($membership_ids)){
                if(pmpro_hasMembershipLevel($membership_ids,$user_id) ){
                   
                    $flag=1;   
                 }else{

                    $flag=0;
                    $pmpro_levels_page_id = get_option('pmpro_levels_page_id');
                      $link = get_permalink($pmpro_levels_page_id);
                      $html='<a href="'.$link.'"class="button  start_quiz_button full is-primary"> '.__('Get Membership for Quiz','vibe').'</a>';
                 }
             }
             
        }
        if(in_array('wplms-mycred-addon/wplms-mycred-addon.php', apply_filters('active_plugins', get_option('active_plugins')))){
            
            $points = get_post_meta($quiz_id,'vibe_quiz_mycred_points',true);
            if(!empty($points)){
                $mycred = mycred();
              $balance = $mycred->get_users_cred( $user_id );
              if($balance < $points){
                 $flag=0;
                 $html= '<a href="#"class="button start_quiz_button full is-primary"> '.__('Take this Quiz','vibe').'<span>'.__('Not enough points.','vibe').'</span></a>';
              }

              if(!$mycred->has_entry( 'purchase_quiz',$quiz_id,$user_id)){
                $flag=1;
                $deduct = -1*$points;
                $mycred->update_users_balance( $user_id, $deduct);
                $mycred->add_to_log('purchase_quiz',$user_id,$deduct,__('Student subscibed to quiz','wplms-mycred'),$quiz_id);
              }else{
                $flag=1;
              }
            }
        }

      if(!$flag){
        ob_start();
        ?>
        <script>
          document.addEventListener('DOMContentLoaded',function(){
            if(document.querySelector('.start_quiz_button')){
                document.querySelector('.start_quiz_button').classList.add('loading');
            }
            localforage.getItem('bp_login_token').then(function(token){
              if(token){
                document.querySelector('.start_quiz_button').style.display='none'; 
              }
            });
        });
        </script>
        <?php
        $html .= ob_get_clean();
        return $html;
      }  
      return $button;
    }

}

WPLMS_Sell_Quiz_Init::init();