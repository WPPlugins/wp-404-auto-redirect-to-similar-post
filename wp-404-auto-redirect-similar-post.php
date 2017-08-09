<?php
/**
 * Plugin Name: WP 404 Auto Redirect to Similar Post
 * Description: Automatically Redirect any 404 to a Similar Post based on the Title, Post Type, Category & Taxonomy using 301 Redirects!
 * Author: 		hwk-fr
 * Version: 	0.4.0.2
 * Author URI: 	http://hwk.fr
 */

if(!defined('ABSPATH'))
  die('You are not allowed to call this page directly.');

function wp404arsp_settings(){
	$return['debug'] = false;
	return $return;
}

add_action('template_redirect', 'wp404arsp');
function wp404arsp(){
	
	if(!is_404() || (defined('DOING_AJAX') && DOING_AJAX))
		return;
	
	$query = wp404arsp_query_setup($_SERVER['REQUEST_URI']);
	wp404arsp_search($query);
	
}

function wp404arsp_query_setup($request){
	
	$path 				= pathinfo(urldecode(htmlspecialchars(strtok($request, '?'))));
	$path['filename'] 	= sanitize_title(pathinfo(basename($request), PATHINFO_FILENAME));
	$path['dirname'] 	= ltrim($path['dirname'], '/');
	$path['dirname'] 	= ltrim($path['dirname'], '\\');
	
	if(!empty($path['dirname'])){
		
		if(strpos($path['dirname'], '/') !== false){
			$path['directories'] = explode('/', $path['dirname']);
			foreach($path['directories'] as $k => $v){
				$path['directories'][$k] = sanitize_title($v);
			}
		}else{
			$path['directories'][] = sanitize_title($path['dirname']);
		}
		
	}
	
	$path['directories'][] = $path['filename'];
	
	$keywords['split'] 	= $path['directories'];
	$keywords['split'] 	= wp404arsp_remove_stop_words($keywords['split']);
	
	$keywords['full'] 	= implode('/', $keywords['split']);
	
	$return = array();
	$return['keywords']['split'] 	= $keywords['split'];
	$return['keywords']['full'] 	= $keywords['full'];
	
	if(!empty($return['keywords']['split'])){
		$i=0; foreach($return['keywords']['split'] as $keyword){
			
			// Test: Post
			$get_posts_types = get_post_types(array('public' => true), 'names');
			foreach($get_posts_types as $post_type){
				if($post = get_page_by_path($keyword, 'object', $post_type)){
					$return['known'][$i]['slug'] 				= $keyword;
					$return['known'][$i]['type'] 				= 'post';
					$return['known'][$i]['data']['post_type'] 	= $post->post_type;
					$return['known'][$i]['data']['ID'] 			= $post->ID;
					break;
				}
			}
			
			if(empty($return['known'][$i]['type'])){
				
				// Test: Post Type
				$get_posts_types = get_post_types(array('public' => true), 'names');
				foreach($get_posts_types as $post_type){
					$post_type = get_post_type_object($post_type);
					if($post_type->rewrite['slug'] == $keyword || $post_type->has_archive == $keyword){
						$return['known'][$i]['slug'] 				= $keyword;
						$return['known'][$i]['type'] 				= 'post_type';
						$return['known'][$i]['data']['post_type'] 	= $post_type->name;
						break;
					}
				}
			
			}
			
			if(empty($return['known'][$i]['type'])){
			
				// Test: Taxonomy
				$get_taxonomies = get_taxonomies(array('public' => true), 'names');
				foreach($get_taxonomies as $taxonomy){
					if($tax = get_term_by('slug', $keyword, $taxonomy)){
						$return['known'][$i]['slug'] 				= $keyword;
						$return['known'][$i]['type'] 				= 'term';
						$return['known'][$i]['data']['taxonomy'] 	= $taxonomy;
						break;
					}
				}
			
			}
			
			if(empty($return['known'][$i]['slug']))
				$return['unknown'][]['slug'] = $keyword;
			
			$i++;
		}
		
	}
	
	return $return;
	
}

function wp404arsp_search($query){
	
	global $wpdb;
	
	if(empty($query))
		return false;
	
	$known 		= (!empty($query['known'])) 	? array_reverse($query['known']) 	: '';
	$unknown 	= (!empty($query['unknown'])) 	? array_reverse($query['unknown']) 	: '';
	
	$debug['query'] = $query;
	
	if(empty($unknown) && !empty($known)){
		foreach($known as $slug){
			
			$get_permalink = false;
			
			if($slug['type'] == 'post')
				$get_permalink = get_permalink($slug['data']['ID']);
			
			if($slug['type'] == 'post_type')
				$get_permalink = get_post_type_archive_link($slug['data']['post_type']);
			
			if($slug['type'] == 'term')
				$get_permalink = get_term_link($slug['slug'], $slug['data']['taxonomy']);
			
			wp404arsp_redirect($get_permalink, $debug);
		}
	}
	
	if(empty($known) && !empty($unknown)){
		foreach($unknown as $word){
			$collapse[] = $word['slug'];
		}
		$explode = explode('-', implode('-', $collapse));
		
		$get_permalink = false;
		
		$sql = "SELECT p.ID, ";
		foreach($explode as $word){
			$sql .= " if(INSTR(LCASE(p.post_name),'" . $word . "'), 1, 0) + ";
		}
		$sql .= "0 as score FROM " . $wpdb->posts . " AS p
				WHERE p.post_status = 'publish' AND p.post_type <> 'attachment' AND p.post_type <> 'nav_menu_item'
				ORDER BY score DESC, post_modified DESC LIMIT 1";
		
		$debug['sql'] = $sql;
		
		$get_post = $wpdb->get_row($sql);
		if(!empty($get_post)){
			$debug['result'] = array('score' => $get_post->score, 'id' => (int)$get_post->ID, 'url' => get_permalink($get_post->ID));
			if($get_post->score > 0){
				$get_permalink = get_permalink((int)$get_post->ID);
			}
		}
		
		wp404arsp_redirect($get_permalink, $debug);
		
		/*
		$sql = "SELECT t.term_id, ";
		
		foreach(wp404arsp_remove_stop_words($search) as $word){
			$sql .= " if(INSTR(LCASE(t.slug),'" . $word . "'), 1, 0) + ";
		}
		
		$sql .= "0 as score FROM " . $wpdb->terms . " AS t 
				INNER JOIN $wpdb->term_taxonomy AS tt ON (t.term_id = tt.term_id)
				WHERE tt.taxonomy = '".$query['wp_query']['taxonomy']."' 
				ORDER BY score DESC LIMIT 1";
				
		$debug['sql'] = $sql;
		
		$get_term = $wpdb->get_row($sql);
		if(!empty($get_term)){
			$debug['results'][] = array('id' => (int)$get_term->term_id, 'url' => get_term_link((int)$get_term->term_id, $query['wp_query']['taxonomy']), 'score' => $get_term->score);
			$post = (int)$get_term->term_id;
		}
		*/
		
		// TODO
		// Search Term and compare with Post search
		
	}
	
	if(!empty($known) && !empty($unknown)){
		foreach($known as $slug){
			
			foreach($unknown as $word){
				$collapse[] = $word['slug'];
			}
			$explode = explode('-', implode('-', $collapse));
			
			$get_permalink = false;
			
			if($slug['type'] == 'post_type'){
				
				$sql = "SELECT p.ID, ";
				foreach($explode as $word){
					$sql .= " if(INSTR(LCASE(p.post_name),'" . $word . "'), 1, 0) + ";
				}
				$sql .= "0 as score FROM " . $wpdb->posts . " AS p
						WHERE 
							p.post_type = '".$slug['data']['post_type']."' 
							AND p.post_status = 'publish' AND p.post_type <> 'attachment' AND p.post_type <> 'nav_menu_item'
						ORDER BY score DESC, post_modified DESC LIMIT 1";
				
				$debug['sql'] = $sql;
				
				$get_post = $wpdb->get_row($sql);
				if(!empty($get_post)){
					$debug['result'] = array('score' => $get_post->score, 'id' => (int)$get_post->ID, 'url' => get_permalink($get_post->ID));
					if($get_post->score > 0){
						$get_permalink = get_permalink((int)$get_post->ID);
					}
				}
				
				wp404arsp_redirect($get_permalink, $debug);
				
			}
		
			if($slug['type'] == 'term'){
				
				$sql = "SELECT p.ID, ";
				foreach($explode as $word){
					$sql .= " if(INSTR(LCASE(p.post_name),'" . $word . "'), 1, 0) + ";
				}
				$sql .= "0 as score FROM " . $wpdb->posts . " AS p
						INNER JOIN $wpdb->term_relationships AS tr ON (p.ID = tr.object_id)
						INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
						INNER JOIN $wpdb->terms AS t ON (t.term_id = tt.term_id)
						WHERE 
							tt.taxonomy = '".$slug['data']['taxonomy']."' AND t.slug = '".$slug['slug']."' 
							AND p.post_status = 'publish' AND p.post_type <> 'attachment' AND p.post_type <> 'nav_menu_item'
						ORDER BY score DESC, post_modified DESC LIMIT 1";
				
				$debug['sql'] = $sql;
				
				$get_post = $wpdb->get_row($sql);
				if(!empty($get_post)){
					$debug['result'] = array('score' => $get_post->score, 'id' => (int)$get_post->ID, 'url' => get_permalink($get_post->ID));
					if($get_post->score > 0){
						$get_permalink = get_permalink((int)$get_post->ID);
					}
				}
				
				wp404arsp_redirect($get_permalink, $debug);
				
			}

		}

		unset($query['unknown']);
		wp404arsp_search($query, $debug);
		
	}
	
	wp404arsp_redirect(home_url(), 'Go home, you\'re drunk...');
	
}

function wp404arsp_redirect($url = '', $debug = ''){
	
	$wp404arsp_settings = wp404arsp_settings();
	
	if(is_super_admin() && $wp404arsp_settings['debug']){
		echo "<pre>"; echo 'Redirect to: ' . ((!empty($url)) ? $url : '<span>Empty</span>') . "</pre>";

		if(!empty($debug))
			echo "<pre>"; print_r($debug); echo "</pre>";
		
		echo "<hr />";
		return;
	}
	
	if(!empty($url)){
		wp_redirect($url, 301);
		exit;
	}
	
}

function wp404arsp_remove_stop_words($input){
				
	$words = array('a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any', 'are', 'arent', 'as', 'at', 'be', 'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by', 'cant', 'cannot', 'could', 'couldnt', 'did', 'didnt', 'do', 'does', 'doesnt', 'doing', 'dont', 'down', 'during', 'each', 'few', 'for', 'from', 'further', 'had', 'hadnt', 'has', 'hasnt', 'have', 'havent', 'having', 'he', 'hed', 'hell', 'hes', 'her', 'here', 'heres', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'hows', 'i', 'id', 'ill', 'im', 'ive', 'if', 'in', 'into', 'is', 'isnt', 'it', 'its', 'itself', 'lets', 'me', 'more', 'most', 'mustnt', 'my', 'myself', 'no', 'nor', 'not', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'same', 'shant', 'she', 'shed', 'shell', 'shes', 'should', 'shouldnt', 'so', 'some', 'such', 'than', 'that', 'thats', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'there', 'theres', 'these', 'they', 'theyd', 'theyll', 'theyre', 'theyve', 'this', 'those', 'to', 'too', 'under', 'until', 'up', 'very', 'was', 'wasnt', 'we', 'wed', 'well', 'were', 'weve', 'werent', 'what', 'whats', 'when', 'whens', 'where', 'wheres', 'which', 'while', 'who', 'whos', 'whom', 'why', 'whys', 'with', 'wont', 'would', 'wouldnt', 'you', 'youd', 'youll', 'youre', 'youve', 'your', 'yours', 'category', 'page', 'paged');
	
	if(is_array($input)){
		$return = array_diff($input, $words);
		
		foreach($return as $key => $val){
			if(is_numeric($val))
				unset($return[$key]);
		}
		
		return array_values($return);
		
	}else{
		return preg_replace('/\b('.implode('|', $words).')\b/', '', $input);
		
	}
}