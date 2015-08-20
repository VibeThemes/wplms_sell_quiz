<?php
/*
Plugin Name: WPLMS SELL QUIZ
Plugin URI: http://www.Vibethemes.com
Description: A simple WordPress plugin to sell quiz
Version: 1.0
Author: VibeThemes
Author URI: http://www.vibethemes.com
License: GPL2
*/


if(!class_exists('wplms_sell_quiz'))
{   
    class wplms_sell_quiz  
    {
            
        public function __construct(){   

            add_filter('wplms_quiz_metabox',array($this,'wplms_sell_quiz_as_product'));
            add_filter('wplms_start_quiz_button',array($this,'the_quiz_button'),10,2);
            
        } 
        function wplms_sell_quiz_as_product($metabox){
        	if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
	        	$metabox['vibe_quiz_product']=array(
							'label'	=> __('Associated Product','vibe-customtypes'), // <label>
							'desc'	=> __('Associated Product with the Course.','vibe-customtypes'), // description
							'id'	=> 'vibe_quiz_product', // field id and name
							'type'	=> 'selectcpt', // type of field
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
							'label'	=> __('PMPro Membership','vibe-customtypes'), // <label>
							'desc'	=> __('Required Membership level for this quiz','vibe-customtypes'), // description
							'id'	=> 'vibe_quiz_pmpro_membership', // field id and name
							'type'	=> 'multiselect', // type of field
					        'options' => $level_array,
						);
        	}
        	if(in_array('wplms-mycred-addon/wplms-mycred-addon.php', apply_filters('active_plugins', get_option('active_plugins')))){

					$metabox['vibe_quiz_mycred_points']=array( // Text Input
						'label'	=> __('MyCred Points','vibe-customtypes'), // <label>
						'desc'	=> __('MyCred Points required to take this quiz.','vibe-customtypes'),
						'id'	=> 'vibe_quiz_mycred_points', // field id and name
						'type'	=> 'number' // type of field
					);
        	}

    		return $metabox;
		} 
		function the_quiz_button($button,$quiz_id){

			global $post;
			
			   $quiz_id=get_the_ID();

			  $user_id=get_current_user_id();
			  $flag = 1;
			  if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
			      $pid=get_post_meta($quiz_id,'vibe_quiz_product',true);
			      if(isset($pid) && is_numeric($pid) && get_post_type($pid) == 'product'){
				      $product_taken = wc_customer_bought_product('',$user_id,$pid);
				      	if(!$product_taken){
					        $pid=get_permalink($pid);
					        $check=vibe_get_option('direct_checkout');
					        $check =intval($check);
					        if(isset($check) &&  $check){
					          $pid .= '?redirect';
			      		    }
			      		    $flag=0;
			      		    $html='<a href="'.$pid.'"class="button create-group-button full"> '.__('Take this Quiz','vibe').'</a>';
			     		}else{
			     			$flag=1;
			     		}
					}
				}
				if ( in_array( 'paid-memberships-pro/paid-memberships-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && is_user_logged_in()) {
				     $membership_ids=vibe_sanitize(get_post_meta($quiz_id,'vibe_quiz_membership',false));
				     if(pmpro_hasMembershipLevel($membership_ids,$user_id) && isset($membership_ids) && count($membership_ids) >= 1){
				        $membership_taken=get_user_meta($user_id,$quiz_id,true);
				        if(!$membership_taken){
				        	$pmpro_levels_page_id = get_option('pmpro_levels_page_id');
        					$link = get_permalink($pmpro_levels_page_id);
        					$html='<a href="'.$link.'"class="button create-group-button full"> '.__('Take this Quiz','vibe').'</a>';
        					$flag=0;
				        }else{
							$flag=1;
				        }    
				     }
				}
				if(in_array('wplms-mycred-addon/wplms-mycred-addon.php', apply_filters('active_plugins', get_option('active_plugins')))){
                  	$points = get_post_meta($quiz_id,'vibe_quiz_mycred_points',true);
					$mycred = mycred();
					$balance = $mycred->get_users_cred( $user_id );
					if($balance < $points){
						 $flag=0;
						 $html= '<a href="#"class="button create-group-button full"> '.__('Take this Quiz','vibe').'<span>'.__('<br/>Not enough points.','vibe').'</span></a>';
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
			if(!$flag){
				return $html;
			}  
	        return $button;
		}
	}
	new wplms_sell_quiz;
}

?>