<?php
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;

/**
 * @var string          $content
 * @var AdminController $this
 */
$_route = $this->route;
$_step = 'light';
$_headline = 'Platform Administration';

//	Change these to update the CDN versions used. Set to false to disable
$_bootstrapVersion = '3.0.0'; // Set to false to disable
$_bootswatchVersion = '3.0.0';
$_dataTablesVersion = '1.9.4';
$_useBootswatchThemes = true; // Set to false to disable
$_bootswatchTheme = FilterInput::request( 'theme', 'cerulean', FILTER_SANITIZE_STRING );
$_fontAwesomeVersion = '3.2.1'; // Set to false to disable
$_jqueryVersion = '1';

//	Our css building begins...
$_css = '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700,800" rel="stylesheet" type="text/css">';
$_scripts = null;

if ( $_useBootswatchThemes )
{
	$_css .= '<link href="//netdna.bootstrapcdn.com/bootswatch/' . $_bootswatchVersion . '/' . $_bootswatchTheme . '/bootstrap.min.css" rel="stylesheet" media="screen">';
}
else if ( false !== $_bootstrapVersion )
{
	$_css .= '<link href="//netdna.bootstrapcdn.com/bootstrap/' . $_bootstrapVersion . '/css/bootstrap.no-icons.min.css" rel="stylesheet"  media="screen">';
}

if ( false !== $_fontAwesomeVersion )
{
	$_css .= '<link href="//netdna.bootstrapcdn.com/font-awesome/' . $_fontAwesomeVersion . '/css/font-awesome.css" rel="stylesheet">';
}

if ( false !== $_dataTablesVersion )
{
//	$_css .= '<link href="/public/css/jquery.dataTables.css" rel="stylesheet">';
}

if ( false !== $_jqueryVersion )
{
	$_scripts .= '<script src="//ajax.googleapis.com/ajax/libs/jquery/' . $_jqueryVersion . '/jquery.min.js"></script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>DreamFactory Services Platform&trade;</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8">
	<?php echo $_css; ?>
	<?php echo $_scripts; ?>
	<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
	<script src="/public/js/html5shiv.js"></script>
	<script src="/public/js/respond.min.js"></script>
	<![endif]-->
	<link rel="icon" type="image/png" href="/public/images/logo-48x48.png">
	<link href="/public/css/main.css" rel="stylesheet">
</head>
<body>

<div id="wrap">
	<div class="navbar navbar-default navbar-fixed-top">

		<div class="container">

			<div class="navbar-header">
				<button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a href="/" class="navbar-brand"><?php echo $_headline; ?></a>
			</div>

			<div class="collapse navbar-collapse" id="navbar-main">
				<ul class="nav navbar-nav">
					<li class="active">
						<a href="#">Home</a>
					</li>
					<li>
						<a href="#about">About</a>
					</li>
					<li>
						<a href="#contact">Contact</a>
					</li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" id="themes">Themes <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=default">Default</a>
							</li>
							<li class="divider"></li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=amelia">Amelia</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=cerulean">Cerulean</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=cosmo">Cosmo</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=cyborg">Cyborg</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=flatly">Flatly</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=journal">Journal</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=readable">Readable</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=simplex">Simplex</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=slate">Slate</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=spacelab">Spacelab</a>
							</li>
							<li>
								<a tabindex="-1" href="<?php echo Curl::currentUrl( false ); ?>?theme=united">United</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#">Help</a>
					</li>
					<li>
						<a href="http://blog.dreamfactory.com/blog">Blog</a>
					</li>
					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#" id="download">Download <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li class="nav-header">Source Code</li>
							<li>
								<a tabindex="-1" href="https://github.com/dreamfactorysoftware/dsp-core">GitHub</a>
							</li>
							<li class="nav-header">Linux Packages</li>
							<li>
								<a tabindex="-1" href="#">Redhat</a>
							</li>
							<li>
								<a tabindex="-1" href="#">Ubuntu</a>
							</li>
							<li>
								<a tabindex="-1" href="#">CentOS</a>
							</li>
							<li>
								<a tabindex="-1" href="#">Debian</a>
							</li>
							<li class="nav-header">Virtual Machine Images</li>
							<li>
								<a tabindex="-1" href="#">Amazon EC2 AMI</a>
							</li>
							<li>
								<a tabindex="-1" href="">Windows Azure VHD</a>
							</li>
						</ul>
					</li>

				</ul>
			</div>
			<!--/.nav-collapse -->
		</div>
	</div>

	<div class="container">
		<?php echo $content; ?>
	</div>
</div>

<div id="footer">
	<div class="container">
		<ul class="list-inline">
			<li class="pull-right">
				<a href="#top">Back to top</a>
			</li>
			<li>
				<a href="http://blog.dreamfactory.com/blog">Blog</a>
			</li>
			<li>
				<a href="http://feeds.feedburner.com/dreamfactory">RSS</a>
			</li>
			<li>
				<a href="https://twitter.com/dfsoftwareinc">Twitter</a>
			</li>
			<li>
				<a href="https://github.com/dreamfactorysoftware">GitHub</a>
			</li>
		</ul>
		<p>
			<span class="pull-right">Code licensed under the <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License v2.0</a></span>
			<span class="pull-left">&copy; DreamFactory Software, Inc. <?php echo date( 'Y' ); ?>. All Rights Reserved.</span>
		</p>
	</div>
</div>

<?php
if ( false !== $_bootstrapVersion )
{
	echo '<script src="//netdna.bootstrapcdn.com/bootstrap/' . $_bootstrapVersion . '/js/bootstrap.min.js"></script>';
}

if ( false !== $_dataTablesVersion )
{
	echo '<script src="//ajax.aspnetcdn.com/ajax/jquery.dataTables/' . $_dataTablesVersion . '/jquery.dataTables.min.js"></script>';
	echo '<script src="/public/js/jquery.dataTables.bootstrap.support.js"></script>';
}
?>
<script src="/public/js/app.jquery.js"></script>
</body>
</html>
