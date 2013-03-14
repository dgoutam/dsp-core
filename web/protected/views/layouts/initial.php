<?php
/**
 * @var string        $content
 * @var AppController $this
 */
$_route = $this->route;
$_step = 'Launchpad';

if ( $_route == 'site/login' )
{
	$_step .= ' -- DSP Activation';
}
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>DreamFactory Services Platform&trade;</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="author" content="DreamFactory Software, Inc.">
	<meta name="language" content="en" />
	<link rel="shortcut icon" href="/public/images/logo-32x32.png" />
	<style>
		body {
			padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
		}
	</style>
	<link rel="stylesheet" type="text/css" href="/public/vendor/bootstrap/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="/public/vendor/bootstrap/css/bootstrap-responsive.min.css" />
	<link rel="stylesheet" type="text/css" href="/public/css/initial.css" />
	<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->    <!--[if lt IE 9]>
	<script type="text/javascript" src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>    <![endif]-->
</head>
<body>
<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">

			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"> <span class="icon-bar"></span> <span
					class="icon-bar"></span> <span class="icon-bar"></span> </a> <img id="logo-img" src="/public/images/logo-48x48.png"><a
				class="brand" href="#"><?php echo $_step; ?></a>

			<div class="nav-collapse collapse">
				<ul class="nav"></ul>
			</div>
		</div>
	</div>
</div>
<div class="container">
	<?php
	echo $content;
	?>
	<footer>
		<p>&copy; DreamFactory Software, Inc. <?php echo date( 'Y' ); ?>. All Rights Reserved.</p>
	</footer>
</div>
<!-- /container -->
<script src="/public/vendor/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
</html>
