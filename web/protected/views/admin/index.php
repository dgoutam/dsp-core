<?php
/**
 * @var array $resourceColumns
 */
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

$_content = $_tabs = null;

$_class = ' class="active"';

foreach ( $resourceColumns as $_resource => $_config )
{
	$_html = '<h3>Coming Soon!</h3>';
	$_labels = null;
	$_active = $_resource == 'apps' ? ' active' : null;

	//	Get/create a menu name
	$_menuName = Option::get(
		$_config,
		'menu_name',
		Option::get(
			$_config,
			'header',
			Inflector::pluralize( $_config['resource'] )
		)
	);

	if ( isset( $_config['labels'] ) && !empty( $_config['labels'] ) )
	{
		$_id = 'tab-' . $_resource;
		$_count = 0;

		foreach ( $_config['labels'] as $_label )
		{
			$_labels .= '<th>' . $_label . '</th>';
			$_count++;
		}

		$_html
			= <<<HTML
<h3>{$_config['header']}<div id="admin-toolbar" class=" pull-right"></div></h3>
<table class="table table-striped table-hover table-bordered table-resource" id="{$_resource}-table">
<thead>
	<tr>{$_labels}</tr>
</thead>
<tbody>
	<tr>
		<td colspan="{$_count}" class="dataTables_empty">Nothing to see here. Move along...</td>
	</tr>
</tbody>
</table>
HTML;
	}

	$_content .= '<div class="tab-pane' . $_active . '" id="tab-' . $_resource . '">' . $_html . '</div>';

	$_tabs .= '<li ' . $_class . '><a href="#tab-' . $_resource . '" data-toggle="tab"><i class="icon-gear"></i> ' . $_menuName . '</a></li> ';
	$_class = null;
}

//	Fix up functions
$_dtConfig = json_encode( $resourceColumns );
$_dtConfig = str_replace( array( '"##', '##"', '\"' ), array( null, null, '"' ), $_dtConfig );

?>
<div class="container">
	<div class="tabbable tabs-left">
		<ul class="nav nav-tabs">
			<?php echo $_tabs; ?>
		</ul>

		<div class="tab-content"><?php echo $_content; ?></div>
	</div>
</div>

<script type="text/javascript">
jQuery(function($) {
	var _dtColumns = <?php echo $_dtConfig; ?>, _fields;
	var _tables = {};

	$('a[data-toggle="tab"]').on('shown', function(e) {
		var _type = $(e.target).attr('href').replace('#tab-', '');
		var _id = '#' + _type + '-table';

		var _table = false;

		if (_tables[_type]) {
			_table = _tables[_type];
		}

		if (!_table) {
			if (_dtColumns[_type]) {
				var _fields = _dtColumns[_type].fields;
				var _resource = _dtColumns[_type].resource;
				var _columns = _dtColumns[_type].columns;

				_table = $(_id).dataTable({
					bProcessing:     true,
					bServerSide:     true,
					bStateSave:      true,
					sAjaxSource:     "/rest/system/" + _resource,
					sPaginationType: "bootstrap",
					aoColumns:       _columns,
					oLanguage:       {
						sSearch: "Filter:"
					},
					fnServerParams:  function(aoData) {
						aoData.push({ "name": "format", "value": 100 }, { "name": "app_name", "value": "php-admin" }, { "name": "fields", "value": _fields });
					}
				});

				_tables[_type] = _table;
			}
		} else {
			if (_table && _table.oApi) {
				_table.oApi.fnReloadAjax();
			}
		}
	});

	//	Make the first tab load
	$('li.active a').trigger('shown');

	/* Add events */
	$('.table-resource').on('click', 'tbody tr', function() {
		var _row = $('td', this);
		var _id = $(_row[0]).text();
		window.location.href = '/admin/' + $(this).closest('table').attr('id').replace('-table', '') + '/update/id/' + _id;
		return false;
	});
});

</script>
