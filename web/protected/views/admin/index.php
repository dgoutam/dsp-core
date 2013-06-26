<div class="container">
	<div class="tabbable tabs-left">

		<ul class="nav nav-tabs">
			<li>
				<a href="#tab-applications" data-toggle="tab"><i class="icon-cogs"></i>Installed Applications</a>
			</li>
			<li>
				<a href="#tab-auths" data-toggle="tab"><i class="icon-key"></i>Authorizations</a>
			</li>
			<li>
				<a href="#tab-services" data-toggle="tab"><i class="icon-cog"></i>Services</a>
			</li>
		</ul>

		<div class="tab-content">
			<div class="tab-pane active" id="tab-services">
				<?php require_once __DIR__ . '/_services.php'; ?>
			</div>
			<div class="tab-pane" id="tab-applications">
				<?php require_once __DIR__ . '/_applications.php'; ?>
			</div>
			<div class="tab-pane" id="tab-auths">
				<?php require_once __DIR__ . '/_authorizations.php'; ?>
			</div>
		</div>

	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function() {
	$('#services-table, #applications-table, #authorizations-table').dataTable({
		"bProcessing":     true,
		"bServerSide":     true,
		"sAjaxSource":     "/rest/registry/?app_name=launchpad&dt=1&c=id,name,tag,enabled,last_used",
		"sDom":            "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
		"sPaginationType": "bootstrap",
		"oLanguage":       {
			"sSearch":     "Filter:",
			"sLengthMenu": "_MENU_ records per page"
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

