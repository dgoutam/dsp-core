<?php
/**
 * @var string        $content
 * @var WebController $this
 */
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;

$_route = $this->route;
$_headline = 'DSP Administration';
$_step = 'light';

//	Change these to update the CDN versions used. Set to false to disable
$_bootstrapVersion = '3.0.0';
$_fontAwesomeVersion = '3.2.1';
$_jqueryVersion = '2.0.2';
$_css = $_scripts = null;

$_css .= '<link href="//netdna.bootstrapcdn.com/bootstrap/' . $_bootstrapVersion . '/css/bootstrap.no-icons.min.css" rel="stylesheet"  media="screen">';
$_css .= '<link href="//netdna.bootstrapcdn.com/font-awesome/' . $_fontAwesomeVersion . '/css/font-awesome.css" rel="stylesheet">';
$_scripts .= '<script src="//ajax.googleapis.com/ajax/libs/jquery/' . $_jqueryVersion . '/jquery.min.js"></script>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>DreamFactory Services Platform&trade; Installation and Setup</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="author" content="DreamFactory Software, Inc.">
	<meta name="language" content="en" />
	<link rel="icon" type="image/png" href="/public/images/logo-48x48.png">
	<link rel="shortcut icon" type="image/png" href="/public/images/logo-48x48.png" />
	<?php echo $_css; ?>
	<?php echo $_scripts; ?>
	<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
	<script src="/public/js/html5shiv.js"></script>
	<script src="/public/js/respond.min.js"></script>
	<![endif]-->
	<link href="/public/css/main.css" rel="stylesheet">
	<link href="/public/css/df.effects.css" rel="stylesheet">
</head>
<body>
<div id="wrap">
	<header class="navbar navbar-default navbar-fixed-top dsp-header" role="banner">
		<div class="container">
			<div class="navbar-header">
				<button data-target=".navbar-collapse" data-toggle="collapse" class="navbar-toggle" type="button">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a href="/" class="navbar-brand"><?php echo $_headline; ?></a>
			</div>

			<nav class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#" id="download">Downloads <b class="caret"></b></a>

						<ul class="dropdown-menu">
							<li class="dropdown-header">Source Code</li>
							<li>
								<a href="https://github.com/dreamfactorysoftware/dsp-core">GitHub</a>
							</li>
							<li class="dropdown-header">Linux Packages</li>
							<li>
								<a href="#">Redhat</a>
							</li>
							<li>
								<a href="#">Ubuntu</a>
							</li>
							<li>
								<a href="#">CentOS</a>
							</li>
							<li>
								<a href="#">Debian</a>
							</li>
							<li class="dropdown-header">Virtual Machine Images</li>
							<li>
								<a href="#">Amazon EC2 AMI</a>
							</li>
							<li>
								<a href="">Windows Azure VHD</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="http://blog.dreamfactory.com/blog">Blog</a>
					</li>
					<li>
						<a href="#">Support</a>
					</li>
				</ul>
			</nav>
		</div>
	</header>

	<div class="container"><?php echo $content; ?></div>
</div>

<div id="footer">
	<div class="container">
		<div class="social-links pull-right">
			<ul class="list-inline">
				<li>
					<a href="http://facebook.com/dreamfactory"><i class="icon-facebook-sign icon-2x"></i></a>
				</li>
				<li>
					<a href="https://twitter.com/dfsoftwareinc"><i class="icon-twitter-sign icon-2x"></i></a>
				</li>
				<li>
					<a href="https://github.com/dreamfactorysoftware"><i class="icon-github-sign icon-2x"></i></a>
				</li>
			</ul>
		</div>

		<div class="clearfix"></div>
		<p>
			<span class="left">All source code is licensed under the <a
					href="http://www.apache.org/licenses/LICENSE-2.0">Apache License v2.0
				</a></span>
			<span class="pull-right">&copy; DreamFactory Software, Inc. <?php echo date( 'Y' ); ?>. All Rights Reserved.</span>
		</p>
	</div>
</div>

<?php
echo '<script src="//netdna.bootstrapcdn.com/bootstrap/' . $_bootstrapVersion . '/js/bootstrap.min.js"></script>';
?>
<script src="/public/js/app.jquery.js"></script>
<script src="/public/js/sidebarEffects.js"></script>
</body>
</html>
