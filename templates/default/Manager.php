<html>
<head>
<title><?php echo $this->doctitle; ?></title>
<link rel="stylesheet" type="text/css" media="screen" href="templates/@TEMPLATE@/main.css" />
</head>
<body <?php echo $this->uniquebody; ?>>

<div id="header"></div><!-- close header -->

<div id="container">

<div id="maintitle">
<h1>UNL's Event Publishing System</h1>
<h2>Plan. Publish. Share.</h2>
</div><!-- maintitle -->
	
<div id="main_left">

<div id="navigation">
<h3 id="sec_nav">Navigation</h3>
<?php echo $this->navigation; ?>
</div><!-- close navigation -->

<div id="maincontentarea">
<h3 id="sec_main"><?php echo $this->sectitle; ?></h3>
<?php UNL_UCBCN::displayRegion($this->output); ?>
</div><!-- close main content area -->

</div><!-- close main left -->
	
<div id="right_area">
<h3 id="sec_acc">Account</h3>
<?php echo $this->accountright; ?>
</div><!-- close right-area -->

</div><!-- close container -->
</body>
</html>