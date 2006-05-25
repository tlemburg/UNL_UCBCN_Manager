<html>
<head></head>
<link rel="stylesheet" type="text/css" media="screen" href="templates/@TEMPLATE@/main.css" />
<body>

<div id="header"></div><!-- close header -->

<div id="container">

	<div id="maintitle">
		<h1>UNL's Event Publishing System</h1>
		<h2>Plan. Publish. Share.</h2>
	</div><!-- maintitle -->
	
	<div id="main_left">
		<div id="navigation">
		<h3>Navigation</h3>
		<?php echo $this->navigation; ?>
		</div><!-- close navigation -->
		<div id="maincontentarea">
		<h3>Main Screen</h3>
		<?php UNL_UCBCN::displayRegion($this->output); ?>
		</div><!-- close main content area -->
	</div><!-- close main left -->
	
	<div id="right_area">
	<h3>Account</h3>
	<?php echo $this->accountright; ?>
	</div><!-- close right-area -->

</div><!-- close container -->
</body>
</html>