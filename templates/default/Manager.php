<html>
<head></head>
<link rel="stylesheet" type="text/css" media="screen" href="templates/@TEMPLATE@/main.css" />
<body>
<h1>UNL's Event Publishing System</h1>
<h2>Plan. Publish. Share.</h2>
<div id="navigation">
<?php echo $this->navigation; ?>
</div>
<div id="maincontentarea">
<?php UNL_UCBCN::displayRegion($this->output); ?>
</div>
</body>
</html>