<h2><?php echo htmlspecialchars($_GET['addon'])?></h2>
<p>To download your backup, please click the link below.</p>
<a href="<?php echo $backup_link?>"><?php echo htmlspecialchars($_GET['addon'])?>-backup.tar.gz</a>
<br />
<br />
<a href="config.php?type=setup&display=digiumaddons">back to all addons</a>
