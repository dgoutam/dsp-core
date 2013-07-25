<?php
/**
 * @var array $resourceColumns
 */
$_dtConfig = json_encode( $resourceColumns );
$_content = null;

foreach ( $resourceColumns as $_resource => $_config )
{
	$_html = '<h3>Coming Soon!</h3>';
	$_labels = null;
	$_active = $_resource == 'apps' ? ' active' : null;

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
<h3>{$_config['header']}</h3>
<div id="{$_resource}-table"></div>
HTML;

		/**
		 * <table class="table table-striped table-hover table-bordered" id="{$_resource}-table">
		<thead>
		<tr>{$_labels}</tr>
		</thead>

		<tbody>
		<tr>
		<td colspan="{$_count}" class="dataTables_empty">Momentito...</td>
		</tr>
		</tbody>
		</table>

		 */
	}

	$_content .= '<div class="tab-pane' . $_active . '" id="tab-' . $_resource . '">' . $_html . '</div>';

}

?>
<div class="container">
	<div class="tabbable tabs-left">
		<ul class="nav nav-tabs">

			<li class="active">
				<a href="#tab-apps" data-toggle="tab"><i class="icon-cloud"></i> Applications</a>
			</li>
			<li>
				<a href="#tab-app-groups" data-toggle="tab"><i class="icon-sitemap"></i> App Groups </a>
			</li>
			<li>
				<a href="#tab-users" data-toggle="tab"><i class="icon-user"></i> Users</a>
			</li>
			<li>
				<a href="#tab-roles" data-toggle="tab"><i class="icon-group"></i> Roles</a>
			</li>
			<li>
				<a href="#tab-data" data-toggle="tab"><i class="icon-table"></i> Manage Data </a>
			</li>
			<li>
				<a href="#tab-services" data-toggle="tab"><i class="icon-exchange"></i> Services</a>
			</li>
			<li>
				<a href="#tab-files" data-toggle="tab"><i class="icon-folder-open"></i> Manage Files </a>
			</li>
			<li>
				<a href="#tab-schema" data-toggle="tab"><i class="icon-table"></i> Manage Schema </a>
			</li>
			<li>
				<a href="#tab-doc" data-toggle="tab"><i class="icon-book"></i> API Documentation </a>
			</li>
			<li>
				<a href="#tab-packager" data-toggle="tab"><i class="icon-gift"></i> Package Apps </a>
			</li>
			<li>
				<a href="#tab-config" data-toggle="tab"><i class="icon-cogs"></i> System Config </a>
			</li>
			<li>
				<a href="#tab-providers" data-toggle="tab"><i class="icon-key"></i> Portal Providers </a>
			</li>
			<li>
				<a href="#tab-accounts" data-toggle="tab"><i class="icon-key"></i> Provider Accounts </a>
			</li>
		</ul>

		<div class="tab-content"><?php echo $_content; ?></div>
	</div>
</div>

<script type="text/javascript">
jQuery(function($) {
	var _dtColumns = <?php echo $_dtConfig; ?>, _fields;

	$('a[data-toggle="tab"]').on('shown', function(e) {
		var _type = $(e.target).attr('href').replace('#tab-', '');
		var _id = '#' + _type + '-table';

		var _table = $(_id).data('jtable');

		if (!_table) {
			if (_dtColumns[_type]) {
				var _fields = _dtColumns[_type].fields;
				var _resource = _dtColumns[_type].resource;
				var _columns = _dtColumns[_type].columns;
				var _header = _dtColumns[_type].header || _type;

				_table = $(_id).jtable({
					useHttpVerbs: true,
					title:        _header,
					ajaxSettings: {
						data: {
							'app_name': 'admin',
							'format':   101
						}
					},
					actions:      {
						listAction:   '/rest/system/' + _resource + '?fields=id,name,api_name,url,is_active',
						createAction: '/rest/system/' + _resource,
						updateAction: '/rest/system/' + _resource,
						deleteAction: '/rest/system/' + _resource
					},
					fields:       _fields
				}).jtable('load');

				$(_id).data('jtable', _table);
			}
		}
		else {
			if (_table && _table.oApi) {
				_table.oApi.fnReloadAjax();
			}
		}
	});

//	Make the first tab load
	$('li.active a').trigger('shown');
});

</script>

