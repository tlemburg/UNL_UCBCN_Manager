<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" ><!-- InstanceBegin template="/Templates/php.fixed.dwt.php" codeOutsideHTMLIsLocked="false" -->
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo $this->doctitle; ?></title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" type="text/css" media="screen" href="/ucomm/templatedependents/templatecss/layouts/main.css" />
<link rel="stylesheet" type="text/css" media="print" href="/ucomm/templatedependents/templatecss/layouts/print.css"/>
<script type="text/javascript" src="/ucomm/templatedependents/templatesharedcode/scripts/all_compressed.js"></script>

<?php require_once($GLOBALS['unl_template_dependents'].'/templatesharedcode/includes/browsersniffers/ie.html'); ?>
<?php require_once($GLOBALS['unl_template_dependents'].'/templatesharedcode/includes/comments/developersnote.html'); ?>
<?php require_once($GLOBALS['unl_template_dependents'].'/templatesharedcode/includes/metanfavico/metanfavico.html'); ?>
<!-- InstanceBeginEditable name="head" -->
<link rel="stylesheet" type="text/css" media="screen" href="templates/@TEMPLATE@/manager_main.css" />
<script type="text/javascript" src="templates/@TEMPLATE@/manager.js"></script>

<!-- InstanceEndEditable -->
</head>
<body <?php echo $this->uniquebody; ?>>
<!-- InstanceBeginEditable name="siteheader" -->
<?php require_once($GLOBALS['unl_template_dependents'].'/templatesharedcode/includes/siteheader/siteheader.shtml'); ?>
<!-- InstanceEndEditable -->
<div id="red-header">
	<div class="clear">
		<h1>University of Nebraska&ndash;Lincoln</h1>
		<div id="breadcrumbs"> <!-- InstanceBeginEditable name="breadcrumbs" -->
			<!-- WDN: see glossary item 'breadcrumbs' -->
			<ul>
				<li class="first"><a href="http://www.unl.edu/">UNL</a></li>
				<li>Events</li>
			</ul><img src="templates/@TEMPLATE@/images/eventbeta.png" alt="Event publishing system is still in beta phase" id="badge" />
			<!-- InstanceEndEditable --> </div>
	</div>
</div>
<!-- close red-header -->
  
<?php require_once($GLOBALS['unl_template_dependents'].'/templatesharedcode/includes/shelf/shelf.shtml'); ?>

<div id="container">
	<div class="clear">
		<div id="title"> <!-- InstanceBeginEditable name="collegenavigationlist" -->
			<?php if (isset($this->user)) { ?>
			<ul>
				<li><a href="http://ucommdev.unl.edu/webdev/wiki/index.php/UNL_Calendar_Documentation">Help</a></li>
				<li><strong><a href="<?php echo $this->uri; ?>?logout=true">LogOut</a></strong></li>
			</ul>
			<?php } //End if user ?>
			<!-- InstanceEndEditable -->
			<div id="titlegraphic">
				<!-- WDN: see glossary item 'title graphics' -->
				<!-- InstanceBeginEditable name="titlegraphic" -->
				<h1><?php
				if (isset($this->calendar)) { 
				    echo $this->calendar->name;
				} else {
				    echo 'UNL\'s Event Publishing System';
				}
				?></h1>
				<h2>Plan. Publish. Share.</h2>
				<!-- InstanceEndEditable --></div>
			<!-- maintitle -->
		</div>
		<!-- close title -->
		
		<div id="navigation">
			<h4 id="sec_nav">Navigation</h4>
			<!-- InstanceBeginEditable name="navcontent" -->
			<div id="navlinks">
				<?php
				if (isset($this->user)) { ?>
					<ul>
					<li id="mycalendar"><a href="<?php echo $this->uri; ?>?" title="My Calendar">Pending Events</a></li>
					<li id="create"><a href="<?php echo $this->uri; ?>?action=createEvent" title="Create Event">Create Event</a></li>
					<li id="subscribe"><a href="<?php echo $this->uri; ?>?action=subscribe" title="Subscribe">Subscribe</a></li>
					<!--  <li id="import"><a href="<?php echo $this->uri; ?>?action=import" title="Import/Export">Import/Export</a></li> -->
					</ul>
				<?php 
				} ?>
			</div>
			<!-- InstanceEndEditable -->
			<div id="nav_end"></div>
			<!-- InstanceBeginEditable name="leftRandomPromo" -->
			<!-- InstanceEndEditable -->
			<!-- WDN: see glossary item 'sidebar links' -->
			<div id="leftcollinks"> <!-- InstanceBeginEditable name="leftcollinks" -->
				<?php if (isset($this->user)) { ?>
				<div class="cal_widget">
					<h3><span><?php echo date("F jS, Y"); ?></span></h3>
					<ul>
					<li class="nobullet">Welcome, <?php echo $this->user->uid; ?></li>
					<li><a href="<?php echo $this->frontenduri.'?calendar_id='.$this->calendar->id; ?>">Live Calendar</a></li>
					<li><a href="<?php echo $this->uri; ?>?action=account">Account Info</a></li>
					<li><a href="<?php echo $this->uri; ?>?action=calendar">Calendar Info</a></li>
					<li><a href="<?php echo $this->uri; ?>?action=users">Users &amp; Permissions</a></li>
					</ul>
				</div>
				<?php
				}
				UNL_UCBCN::displayRegion($this->calendarselect);
				if (!empty($this->plugins)) {
					echo '	<div class="cal_widget"><h3>Plugins</h3><ul>';
					foreach ($this->plugins as $plugin) {
						echo '<li><a href="'.$plugin->uri.'">'.$plugin->name.'</a></li>';
					}
					echo '</ul></div>';
				}
				?>
				<!-- InstanceEndEditable --> </div>
		</div>
		<!-- close navigation -->
		
		<div id="main_right" class="mainwrapper">
			<!--THIS IS THE MAIN CONTENT AREA; WDN: see glossary item 'main content area' -->
			
			<div id="maincontent"> <!-- InstanceBeginEditable name="maincontent" -->
				
				<?php
				if (isset($this->user)) { ?>
					<form id="event_search" name="event_search" method="get" action="<?php echo $this->uri; ?>">
						<input type='text' name='q' id='searchinput' value="<?php if (isset($_GET['q'])) { echo htmlentities($_GET['q']); } ?>" />
						<input type='submit' name='submit' value="Search" />
						<input type='hidden' name='action' value='search' />
					</form>
				<? }
				UNL_UCBCN::displayRegion($this->output);
				?>
				<!-- InstanceEndEditable --> </div>
			 </div>
		<!-- close main right -->
	</div>
</div>
<!-- close container -->

<div id="footer">
	<div id="footer_floater"> <!-- InstanceBeginEditable name="optionalfooter" --> <!-- InstanceEndEditable -->
		<div id="copyright"> <!-- InstanceBeginEditable name="footercontent" -->
			
			<!-- InstanceEndEditable --> <span><a href="http://jigsaw.w3.org/css-validator/check/referer">CSS</a> <a href="http://validator.w3.org/check/referer">W3C</a> <a href="http://www-1.unl.edu/feeds/">RSS</a> </span><a href="http://www.unl.edu/" title="UNL Home"><img src="/ucomm/templatedependents/templatecss/images/wordmark.png" alt="UNL's wordmark" id="wordmark" /></a></div>
	</div>
</div>

<!-- close footer -->
<!-- sifr -->
<script type="text/javascript" src="/ucomm/templatedependents/templatesharedcode/scripts/sifr_replacements.js"></script>
</body>
<!-- InstanceEnd --></html>