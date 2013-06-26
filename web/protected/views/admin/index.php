<div class="container">
	<div class="tabbable tabs-left">
		<ul class="nav nav-tabs">
			<li class="active"><a href="#tab-apps" data-toggle="tab"><i class="icon-cloud"></i>Applications</a></li>
			<li><a href="#tab-app-groups" data-toggle="tab"><i class="icon-sitemap"></i>App Groups</a></li>
			<li><a href="#tab-users" data-toggle="tab"><i class="icon-user"></i>Users</a></li>
			<li><a href="#tab-roles" data-toggle="tab"><i class="icon-group"></i>Roles</a></li>
			<li><a href="#tab-data" data-toggle="tab"><i class="icon-table"></i>Manage Data</a></li>
			<li><a href="#tab-services" data-toggle="tab"><i class="icon-exchange"></i>Services</a></li>
			<li><a href="#tab-files" data-toggle="tab"><i class="icon-folder-open"></i>Manage Files</a></li>
			<li><a href="#tab-schema" data-toggle="tab"><i class="icon-table"></i>Manage Schema</a></li>
			<li><a href="#tab-doc" data-toggle="tab"><i class="icon-book"></i>API Documentation</a></li>
			<li><a href="#tab-packager" data-toggle="tab"><i class="icon-gift"></i>Package Apps</a></li>
			<li><a href="#tab-config" data-toggle="tab"><i class="icon-cogs"></i>System Config</a></li>
			<li><a href="#tab-auths" data-toggle="tab"><i class="icon-key"></i>Authorizations</a></li>
		</ul>

		<div class="tab-content">
			<div class="tab-pane active" id="tab-apps"><?php require_once __DIR__ . '/_applications.php'; ?></div>
			<div class="tab-pane" id="tab-app-groups"></div>
			<div class="tab-pane" id="tab-users"></div>
			<div class="tab-pane" id="tab-roles"></div>
			<div class="tab-pane" id="tab-data"></div>
			<div class="tab-pane" id="tab-services"><?php require_once __DIR__ . '/_services.php'; ?></div>
			<div class="tab-pane" id="tab-files"></div>
			<div class="tab-pane" id="tab-schema"></div>
			<div class="tab-pane" id="tab-doc"></div>
			<div class="tab-pane" id="tab-packager"></div>
			<div class="tab-pane" id="tab-config"></div>
			<div class="tab-pane" id="tab-auths"><?php require_once __DIR__ . '/_authorizations.php'; ?></div>
		</div>
	</div>
</div>

<ul style="display: block;">

</ul>

<script type="text/javascript">
jQuery(document).ready(function() {
	var _initialized = false;

	$('a[data-toggle="tab"]').on('shown', function(e) {
		var _id = $(e.target).attr('href').replace('tab-', '') + '-table';

		var _columns = [
			{
				"sName":  "id",
				"sWidth": "50px"
			},
			{
				"sName": "name"
			},
			{
				"sName": "tag"
			},
			{
				"sName": "enabled"
			},
			{
				"sName": "last_used"
			}
		];

		if (!_initialized) {
			switch (_id) {
				case '#services-table':
				case '#applications-table':
				case '#authorizations-table':
					$(_id).dataTable({
//						"sDom":            "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
						"bProcessing":     true,
						"bServerSide":     true,
						"sAjaxSource":     "/rest/registry?app_name=launchpad",
						"sPaginationType": "bootstrap",
						'aoColumns':       _columns,
						"oLanguage":       {
							"sSearch":     "Filter:",
							"sLengthMenu": "_MENU_ records per page"
						},
						"fnServerParams":  function(aoData) {
							aoData.push({ "dt": 1, "app_name": "launchpad" });
						}
					});
					_initialized = true;
					break;
			}
		}
	});

//		/* Add events */
//		$("#platforms-table").find("tbody tr").on('click', function () {
//			var _row = $('td', this);
//			var _id = $(_row[0]).text();
//			window.location.href = '/services/update/id/' + _id;
//			return false;
//		});

});

</script>

