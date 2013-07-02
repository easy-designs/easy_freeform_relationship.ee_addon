(function( $ ){
	
	var $original_field = $('#{PREFIX}_form_name'),
		$dependent_row = $original_field.closest('tr').next('tr'),
		data = {JSON};
	
	$('head')
		.append(
			$('<style media="screen">{CSS}</style>')
		 );
	
	function update_fields()
	{
		var form = $original_field.val(),
			$selects = $dependent_row.find('select'),
			$option = $selects.eq(0)
						.find('option:first-child').clone()
						.removeAttr('selected');
		
		$selects
			.empty()
			.append(
				$option.clone()
			 );
		
		$.each( data[form], function( key, value ){
			$selects.append(
				$option.clone()
					.attr( 'value', key )
					.text( value )
			);
		});
	}
	
	$original_field.change( update_fields );
	
}( jQuery ))