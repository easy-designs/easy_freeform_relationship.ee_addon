<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Easy Freeform Relationship Fieldtype Class
 *
 * @package   Easy Freeform Relationship
 * @author    Aaron Gustafson <aaron@easy-designs.net>
 * @copyright Copyright (c) 2012 Aaron Gustafson
 * @license   MIT
 */
class Easy_freeform_relationship_ft extends EE_Fieldtype {

	/**
	 * Fieldtype Info
	 * @var array
	 */
	var $info = array(
  		'name'             => 'Easy Freeform Relationship',
  		'version'          => '1.0',
	);

	var $addon_name = 'easy_freeform_relationship';
	var $has_array_data = TRUE;
	
	/**
	 * Constructor
	 */
  	function __construct()
	{
		parent::EE_Fieldtype();
	}

	// --------------------------------------------------------------------

	/**
	 * Display Settings Screen
	 *
	 * @access	public
	 * @return	Displays the field settings form
	 *
	 */
	function display_settings($data)
	{
		# get the language file
		$this->EE->lang->loadfile($this->addon_name);
		
		# get the forms
		$forms = array();
		$form_fields = array();
		$query = $this->EE->db->query("
			SELECT	`form_name`,
					`form_label`,
					`field_ids`,
					`field_order`
			FROM	`exp_freeform_forms`
			WHERE	`site_id` = {$this->EE->config->item('site_id')}
			ORDER BY `form_name` ASC
		");
		if ( $query->num_rows() > 0 )
		{
			foreach( $query->result_array() as $row )
			{
				$forms[$row['form_name']] = $row['form_label'];
				# field order should dominate, but sometimes it is not full filled in
				if ( strlen( $row['field_ids'] ) == strlen( $row['field_order'] ) )
				{
					$form_fields[$row['form_name']] = $row['field_order'];
				}
				else
				{
					$form_fields[$row['form_name']] = $row['field_ids'];
				}
			}
		}
		
		# create the field
		$form_name = isset($data['form_name']) ? $data['form_name'] : $this->settings['form_name'];
		$this->EE->table->add_row(
			form_label($this->EE->lang->line('which_form'),"{$this->addon_name}_form_name"),
			form_dropdown( "{$this->addon_name}_form_name", $forms, $form_name, 'id="' . $this->addon_name . '_form_name"', FALSE )
		);

		# collect all fields
		$fields = array();
		$query = $this->EE->db->query("
			SELECT	`field_id`,
					`field_label`
			FROM	`exp_freeform_fields`
			WHERE	`site_id` = {$this->EE->config->item('site_id')}
			ORDER BY `field_id` ASC
		");
		if ( $query->num_rows() > 0 )
		{
			foreach( $query->result_array() as $row )
			{
				$fields[$row['field_id']] = $row['field_label'];
			}
			
		}
		
		# assign as appropriate
		foreach ( $form_fields as $form => $field_list )
		{
			$field_list = explode( '|', $field_list );
			$form_fields[$form] = array();
			foreach( $field_list as $field )
			{
				$form_fields[$form]["form_field_{$field}"] = $fields[$field];
			}
		}
		
		# Create the dependent row
		# set a default set for the first form
		if ( empty( $form_name ) )
		{
			foreach ( $form_fields as $form => $fields )
			{
				$form_name = $form;
				break;
			}
		}
		# create the options
		$options = array_merge(
			array(
				''	=> $this->EE->lang->line('none')
			),
			$form_fields[$form_name]
		);
		# set up the fields
		$html = '<ol class="' . $this->addon_name . '_fields">';
		$i = 1;
		while ( $i < 4 )
		{
			$this_field = isset( $data["field_{$i}"] ) ? $data["field_{$i}"] : $this->settings["field_{$i}"];
			$html .= '<li>';
			$html .= form_label($this->EE->lang->line("display_{$i}"),"{$this->addon_name}_form_name");
			$html .= form_dropdown( "{$this->addon_name}_field_{$i}", $options, $this_field, 'id="' . $this->addon_name . '_field_' . $i . '"', FALSE );
			$html .= '</li>';
			$i++;
		}
		$html .= '</ol>';
		# build the table row
		$this->EE->table->add_row(
			"<strong>{$this->EE->lang->line('choose_fields_to_display')}</strong>",
			$html
		);
		
		# load the JS
		$this->EE->load->library('javascript');
		$js = file_get_contents( 'display_settings.js', TRUE );
		$css = file_get_contents( 'display_settings.css', TRUE );
		
		# inject the field info
		$js = $this->EE->functions->var_swap(
			$js,
			array(
				'PREFIX'	=> $this->addon_name,
				'CSS'		=> str_replace( array( "\r", "\n" ), '', $css ),
				'JSON'		=> json_encode( $form_fields )
			)
		);

		$this->EE->javascript->output( array( $js ) );
		
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Save Settings
	 *
	 * @access	public
	 * @return	field settings
	 *
	 */
	function save_settings($data)
	{
		return array(
			'form_name'	=> $data["{$this->addon_name}_form_name"],
			'field_1'	=> $data["{$this->addon_name}_field_1"],
			'field_2'	=> $data["{$this->addon_name}_field_2"],
			'field_3'	=> $data["{$this->addon_name}_field_3"]
		);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Field on Publish
	 *
	 * @access	public
	 * @param	existing data
	 * @return	field html
	 *
	 */
	function display_field($data='')
	{
		# get the form_id
		$form_id = $this->EE->db->query("
			SELECT	`form_id`
			FROM	`exp_freeform_forms`
			WHERE	`form_name` = '{$this->settings['form_name']}'
			LIMIT 1
		")->row('form_id');
		
		# options
		$submissions = array(	
			''	=> $this->EE->lang->line('none')
		);
		$query = $this->EE->db->query("
			SELECT	*
			FROM	`exp_freeform_form_entries_{$form_id}`
			ORDER BY `entry_date` DESC
		");
		if ( $query->num_rows() > 0 )
		{
			foreach( $query->result_array() as $row )
			{
				$text = array();
				$i = 1;
				while ( $i < 4 )
				{
					if ( !empty( $this->settings["field_{$i}"] ) )
					{
						$text[] = $row[$this->settings["field_{$i}"]];
					}
					$i++;
				}
				$submissions["{$form_id}:{$row['entry_id']}"] = implode( ' - ', $text );
			}
		}
		
		# return the field
		return form_dropdown( $this->field_name, $submissions, $data, 'id="' . $this->field_name . '"' );
		
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Replace tag
	 *
	 * @access	public
	 * @param	field contents
	 * @return	replacement text
	 *
	 */
	function replace_tag( $data, $params=array(), $tagdata=FALSE )
	{

		if ( $tagdata )
		{
			if ( preg_match( '/\\d+:\\d+/', $data ) )
			{
				list( $form_id, $entry_id ) = explode( ':', $data );
			
				# get the submission
				$submission = array_shift(
					$this->EE->db->query("
						SELECT	*
						FROM	`exp_freeform_form_entries_{$form_id}`
						WHERE	`entry_id` = '{$entry_id}'
						LIMIT 1
					")->result_array()
				);

				# transcribe numeric fields
				$fields = array();
				$rows = $this->EE->db->query(
					"
					SELECT	`field_id`,
							`field_name`
					FROM	`exp_freeform_fields`
					"
				)->result_array();
				foreach ( $rows as $row )
				{
					if ( isset( $submission["form_field_{$row['field_id']}"] ) )
					{
						$fields[0][$row['field_name']] = $submission["form_field_{$row['field_id']}"];
					}
				}

				# swap!
				$data = $this->EE->TMPL->parse_variables( $tagdata, $fields );
			}
			
		}
		
		return $data;

	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Install Fieldtype
	 *
	 * @access	public
	 * @return	default global settings
	 *
	 */
	function install()
	{
		return array(
			'form_name'	=> '',
			'field_1'	=> '',
			'field_2'	=> '',
			'field_3'	=> ''
		);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Uninstall Fieldtype
	 * 
	 */
	function uninstall()
	{
		return TRUE;
	}

}

/* End of file ft.easy_freeform_relationship.php */
/* Location: ./system/expressionengine/third_party/easy_freeform_relationship/ft.easy_freeform_relationship.php */