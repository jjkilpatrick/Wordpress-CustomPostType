<?php

class CustomPostType {

	/**
	* Version.
	*/
	const VERSION = '0.1';
	
	/**
	 * Human-readable name
	 * 
	 * @var String
	 */
	private $postTypeName;

	/**
	 * Array of arguments for the register_post_type method
	 * http://bit.ly/1nHDw0w
	 * 
	 * @var array
	 */
	private $postTypeArgs;
	
	/**
	 * Boolean check for adding meta box object
	 * 
	 * @var boolean
	 */
	private $metaActionsRegistered = false;
	
	/**
	 * Array of taxonomies for the custom post type
	 * http://bit.ly/1rD8qd3
	 * 
	 * @var array
	 */
	private $taxonomies = array();

	/**
	 * Array of meta boxes for the custom post type
	 * 
	 * @var array
	 */
	private $metaBoxes = array();
	
	/**
	 * [CustomPostType Constructor]
	 * @param [string] $name   [Human-readable name e.g. Studio]
	 * @param array    $args   [Array of arguments for the register_post_type method. http://bit.ly/1nHDw0w]
	 * @param array    $labels [Labels for the post type]
	 */
	public function CustomPostType($name, $args = array(), $labels = array()) {
		
		$this->postTypeName = self::sanitize($name);
	
		if (!post_type_exists($this->postTypeName)) {
			
			add_action('init', array($this, 'register'));
			
			$plural = pluralize(2, $name);
			
			//merge labels with defaults
			$labels = array_merge(array(
				'name' 					=> _x($plural, 'post type general name'),
				'singular_name' 		=> _x($name, 'post type singular name'),
				'add_new' 				=> _x('Add new', strtolower($name)),
				'add_new_item' 			=> __('Add New ' . $name),
				'edit_item' 			=> __('Edit ' . $name),
				'new_item' 				=> __('New ' . $name),
				'all_items' 			=> __('All ' . $plural),
				'view_item' 			=> __('View ' . $name),
				'search_items' 			=> __('Search ' . $plural),
				'not_found' 			=> __('No ' . strtolower( $plural ) . ' found'),
				'not_found_in_trash' 	=> __('No ' . strtolower( $plural ) . ' found in Trash'), 
				'parent_item_colon' 	=> '',
				'menu_name' 			=> $plural
			), $labels);
			
			//merge arguments with defaults
			$this->postTypeArgs = array_merge(array(
				'label' 			=> $plural,
				'labels' 			=> $labels,
				'public' 			=> true,
				'show_ui' 			=> true,
				'supports' 			=> array('title', 'editor'),
				'show_in_nav_menus' => true,
				'_builtin' 			=> false
			), $args);
			
		}
	}
	
	/**
	 * [Adds taxonomy for the custom post type]
	 * @param [string] $name   [Human-readable name e.g. Studio]
	 * @param array    $args   [Array of arguments for the register_taxonomies method. http://bit.ly/1iysXQb]
	 * @param array    $labels [Labels for the taxonomies]
	 */
	public function addTaxonomy($name, $args = array(), $labels = array()) {
		
		if (!empty($name)) {
			
			$taxonomyName = self::sanitize($name);
			$plural = pluralize(2, $name);
			
			//merge labels with defaults
			$labels = array_merge(array(
				'name' 				=> _x($plural, 'taxonomy general name'),
				'singular_name' 	=> _x($name, 'taxonomy singular name'),
			    'search_items' 		=> __('Search ' . $plural),
			    'all_items' 		=> __('All ' . $plural),
			    'parent_item' 		=> __('Parent ' . $name),
			    'parent_item_colon' => __('Parent ' . $name . ':'),
			    'edit_item' 		=> __('Edit ' . $name), 
			    'update_item' 		=> __('Update ' . $name),
			    'add_new_item' 		=> __('Add New ' . $name),
			    'new_item_name' 	=> __('New ' . $name . ' Name'),
			    'menu_name' 		=> $plural,
			), $labels);
			
			//merge arguments with defaults
			$args = array_merge(array(
				'label'				=> $plural,
				'labels'			=> $labels,
				'public' 			=> true,
				'show_ui' 			=> true,
				'show_in_nav_menus' => true,
				'_builtin' 			=> false,
			), $args);
			
			$this->taxonomies[] = array('name' => $taxonomyName, 'args' => $args);
			
		}
		
	}

	/**
	 * Adds a metabox to the custom post edit page
	 * 
	 * @param $title Title in human-readable format, e.g. Publisher Details
	 * @param $fields Array of fields to add to the metabox
	 */
	
	/**
	 * [Adds a metabox to the custom post type edit page]
	 * @param [string] $title       [Human-readable name e.g. Address]
	 * @param [array]  $fields      [Array of fields to add to custom meta box]
	 * @param [string] $description [Human-readable description of the metabox]
	 */
	public function addMetaBox($title, $fields, $description = NULL) {
		$this->addMetaboxObject(new MetaBox($title, $fields, $description));
	}

	public function addMetaboxObject($metaBox) {
		$this->metaBoxes[$metaBox->id] = $metaBox;
		
		if (!$this->metaActionsRegistered) {
			add_action('add_meta_boxes', array($this, 'registerMetaBoxes'));
			add_action('save_post', array($this, 'saveMeta'));
			$this->metaActionsRegistered = true;
		}
	}

	public function register() {
		
		if (count($this->taxonomies) > 0) {
			foreach ($this->taxonomies as $taxonomy) {
				register_taxonomy($taxonomy['name'], null, $taxonomy['args']);
			}
		}

		register_post_type($this->postTypeName, $this->postTypeArgs);
		
		if (count($this->taxonomies) > 0) {
			foreach ($this->taxonomies as $taxonomy) {
				register_taxonomy_for_object_type($taxonomy['name'], $this->postTypeName);
			}
		}
		
	}
	
	public function registerMetaBoxes() {
		
		foreach ($this->metaBoxes as $metabox) {
			add_meta_box($metabox->id, $metabox->title, array($this, 'metaboxContent'), $this->postTypeName, $metabox->context);
		}
		
	}
	
	public function metaboxContent($post, $args) {
		
		$metaBoxId = $args['id'];
		$metaBox = $this->metaBoxes[$metaBoxId];
		
		wp_nonce_field(plugin_basename( __FILE__ ), 'custom_post_type');

		$metaBox->output(get_post_custom($post->ID));
	}
	
	public function saveMeta($postID) {
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (!wp_verify_nonce($_POST['custom_post_type'], plugin_basename(__FILE__))) return;
		
		if (isset($_POST) && (get_post_type($postID) == $this->postTypeName)) {
			
			foreach ($this->metaBoxes as $metaBoxId => $metaBox) {
				foreach ($metaBox->getFields() as $field => $type) {
					$name = self::fieldName($metaBoxId, $field);
					if (isset($_POST['custom_meta'][$name])) {
						update_post_meta($postID, $name, $_POST['custom_meta'][$name]);
					} else {
						delete_post_meta($postID, $name);
					}
				}
			}
			
		}
		
	}
	
	private static function fieldName($metaBoxId, $field) {
		return str_replace('-', '_', $metaBoxId) . '_' . self::sanitize($field, '_');	
	}
	
	private static function sanitize($name, $replace = '-') {
		return str_replace(' ', $replace, strtolower($name));
	}

	public function pluralize($count, $singular, $plural = false)
	{
		if (!$plural) $plural = $singular . 's';

		return ($count == 1 ? $singular : $plural) ;
	}
	
}

class MetaBox {
	
	public $id;
	public $title;
	public $description;
	public $context = 'side';
	
	private $fields = array();
	
	private $cols = array();
	
	public function __construct($title, $fields = array(), $description = NULL) {
		$this->id = self::sanitize($title);
		$this->title = $title;
		$this->description = $description;
		$this->addFields($fields);
	}
	
	public function addFields($fields, $col = 0) {
		if (array_key_exists($col, $this->cols) && is_array($this->cols[$col])) {
			$this->cols[$col] += $fields;
		} else {
			$this->cols[$col] = $fields;
		}
		$this->fields += $fields;
	}
	
	public function getFields() {
		return $this->fields;
	}

	public function output($values) {
			
		if ($this->description) {
			echo '<p>' . $this->description . '</p>';
		}
			
		echo '<div style="overflow:hidden">';
		foreach ($this->cols as $fields) {
			echo '<div style="width: 50%; float: left;">';
			foreach ($fields as $field => $type) {
				echo '<p>';
				$name = self::fieldName($this->id, $field);
				$value = array_key_exists($name, $values) ? $values[$name][0] : null;
				$this->outputField($type, $name, $field, $value);
				echo '</p>';
			}
			echo '</div>';
		}
		echo '</div>';
		
	}

	public function outputField($type, $name, $label, $value = null) {
		$this->outputLabel($label, $name);
		
		if (is_array($type)) {
			list($type, $typeOptions) = $type;
		}
		
		switch ($type) {
			case 'text':
				$size = ($this->context == 'side' ? 28 : 50);
				echo "<input type=\"text\" id=\"$name\" name=\"custom_meta[$name]\" value=\"$value\" size=\"$size\" />";
				break;
			case 'textarea':
				echo "<textarea id=\"$name\" name=\"custom_meta[$name]\" cols=\"50\" rows=\"5\">$value</textarea>"; 
				break;
			case 'checkbox':
				echo "<input type=\"checkbox\" id=\"$name\" name=\"custom_meta[$name]\" value=\"1\"" . (($value == 1) ? ' checked="checked"' : '') . ' />'; 
				break;
			case 'select':
				echo "<select id=\"$name\" name=\"custom_meta[$name]\">";
				foreach ($typeOptions as $key => $option) {
					$selected = ($key == $value);
					echo "<option value=\"$key\"" . ($selected ? ' selected="selected"' : '') . ">$option</option>\n"; 
				}
				echo "</select>";
				break;
		}
	}
	
	public function outputLabel($label, $name) {
		echo "<label for=\"$name\" style=\"float: left; width: 10em;\">$label</label>";
	}
	
	public function setContext($context) {
		$this->context = $context;
	}
	
	private static function sanitize($name, $replace = '-') {
		return str_replace(' ', $replace, strtolower($name));
	}

	private static function fieldName($metaBoxId, $field) {
		return str_replace('-', '_', $metaBoxId) . '_' . self::sanitize($field, '_');	
	}
	
}

?>