<?php ob_start(); ?>
<html>
<!--
 - install.php (c) Shish 2007
 -
 - Initialise the database, check that folder
 - permissions are set properly, set an admin
 - account.
 -
 - This file should be independant of the database
 - and other such things that aren't ready yet
-->
	<head>
		<title>SCore Installation</title>
		<style>
BODY {background: #EEE;font-family: "Arial", sans-serif;font-size: 14px;}
H1, H3 {border: 1px solid black;background: #DDD;text-align: center;}
H1 {margin-top: 0px;margin-bottom: 0px;padding: 2px;}
H3 {margin-top: 32px;padding: 1px;}
FORM {margin: 0px;}
A {text-decoration: none;}
A:hover {text-decoration: underline;}
#block {width: 512px; margin: auto; margin-top: 64px;}
#iblock {width: 512px; margin: auto; margin-top: 16px;}
TD INPUT {width: 350px;}
		</style>
	</head>
	<body>
<?php if(false) { ?>
		<div id="block">
			<h1>Install Error</h1>
			<p>SCore needs to be run via a web server with PHP support -- you
			appear to be either opening the file from your hard disk, or your
			web server is mis-configured.
			<p>If you've installed a web server on your desktop PC, you probably
			want to visit <a href="http://localhost/">the local web server</a>.
			<p>For more help with installation, visit
			<a href="http://trac.shishnet.org/shimmie2/wiki/Guides/Admin/Install">the
			documentation wiki</a>.
		</div>
		<div style="display: none;">
			<PLAINTEXT>
<?php }
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

/*
 * This file lets anyone destroy the database -- disable it
 * as soon as the admin is done installing for the first time
 */
if(is_readable("config.php")) {
	echo "'config.php' exists -- install function is disabled";
	exit;
}
require_once "core/compat.inc.php";
require_once "core/database.class.php";

do_install();

// utilities {{{
function check_gd_version() {
	$gdversion = 0;

	if (function_exists('gd_info')){
		$gd_info = gd_info();
		if (substr_count($gd_info['GD Version'], '2.')) {
			$gdversion = 2;
		} else if (substr_count($gd_info['GD Version'], '1.')) {
			$gdversion = 1;
		}
	}

	return $gdversion;
}

function check_im_version() {
	if(!ini_get('safe_mode')) {
		$convert_check = exec("convert");
	}
	return (empty($convert_check) ? 0 : 1);
}
// }}}
function do_install() { // {{{
	if(isset($_POST['database_dsn'])) {
		install_process($_POST['database_dsn']);
	}
	else if(file_exists("auto_install.conf")) {
		install_process(trim(file_get_contents("auto_install.conf")));
		unlink("auto_install.conf");
	}
	else {
		begin();
	}
} // }}}
function begin() { // {{{
	$err = "";
	$thumberr = "";
	$dberr = "";

	if(!function_exists("mysql_connect")) {
		$dberr = "<p>PHP's MySQL extension seems to be missing; you may ".
				"be able to use an unofficial alternative, checking ".
				"for libraries...";
		if(!function_exists("pg_connect")) {
			$dberr .= "<br>PgSQL is missing";
		}
		else {
			$dberr .= "<br>PgSQL is available";
		}
		if(!function_exists("sqlite_open")) {
			$dberr .= "<br>SQLite is missing";
		}
		else {
			$dberr .= "<br>SQLite is available";
		}
	}

	if($thumberr || $dberr) {
		$err = "<h3>Error</h3>";
	}

	print <<<EOD
		<div id="iblock">
			<h1>SCore Installer</h1>

			$err
			$thumberr
			$dberr

			<h3>Install</h3>
			<form action="install.php" method="POST">
				<center>
					<table>
						<tr><td>Database:</td><td><input type="text" name="database_dsn" size="40"></td></tr>
						<tr><td colspan="2"><center><input type="submit" value="Go!"></center></td></tr>
					</table>
				</center>
			</form>

			<h3>Help</h3>
					
			<p>Databases should be specified like so:
			<br>ie: <code>protocol://username:password@host/database?options</code>
			<br>eg: <code>mysql://SCore:pw123@localhost/SCore?persist</code>
			
			<p>For more help with installation, visit
			<a href="http://trac.shishnet.org/shimmie2/wiki/Guides/Admin/Install">the
			documentation wiki</a>.
		</div>
EOD;
} // }}}
function install_process($database_dsn) { // {{{
	create_tables($database_dsn);
	insert_defaults($database_dsn);
	write_config($database_dsn);
	
	header("Location: index.php");
} // }}}
function create_tables($dsn) { // {{{
	if(substr($dsn, 0, 5) == "mysql") {
		$engine = new MySQL();
	}
	else if(substr($dsn, 0, 5) == "pgsql") {
		$engine = new PostgreSQL();
	}
	else if(substr($dsn, 0, 6) == "sqlite") {
		$engine = new SQLite();
	}
	else {
		die("Unknown database engine; SCore currently officially supports MySQL
		(mysql://), with hacks for Postgres (pgsql://) and SQLite (sqlite://)");
	}

	$db = NewADOConnection($dsn);
	if(!$db) {
		die("Couldn't connect to \"$dsn\"");
	}
	else {
		$engine->init($db);

		$db->execute($engine->create_table_sql("config", "
			name VARCHAR(128) NOT NULL PRIMARY KEY,
			value TEXT
		"));
		$db->execute($engine->create_table_sql("users", "
			id SCORE_AIPK,
			name VARCHAR(32) UNIQUE NOT NULL,
			pass CHAR(32),
			joindate SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			admin SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
			email VARCHAR(128)
		"));
		$db->execute("INSERT INTO config(name, value) VALUES('db_version', 8)");
	}
	$db->Close();
} // }}}
function insert_defaults($dsn) { // {{{
	$db = NewADOConnection($dsn);
	if(!$db) {
		die("Couldn't connect to \"$dsn\"");
	}
	else {
		if(substr($dsn, 0, 5) == "mysql") {
			$engine = new MySQL();
		}
		else if(substr($dsn, 0, 5) == "pgsql") {
			$engine = new PostgreSQL();
		}
		else if(substr($dsn, 0, 6) == "sqlite") {
			$engine = new SQLite();
		}
		else {
			die("Unknown database engine; SCore currently officially supports MySQL
			(mysql://), with hacks for Postgres (pgsql://) and SQLite (sqlite://)");
		}
		$engine->init($db);

		$config_insert = $db->Prepare("INSERT INTO config(name, value) VALUES(?, ?)");
		$user_insert = $db->Prepare("INSERT INTO users(name, pass, joindate, admin) VALUES(?, ?, now(), ?)");

		$db->Execute($user_insert, Array('Anonymous', null, 'N'));
		$db->Execute($config_insert, Array('anon_id', $db->Insert_ID()));

		$db->Close();
	}
} // }}}
function write_config($dsn) { // {{{
	$file_content = "<?php \$database_dsn='$dsn'; ?>";
	
	if(is_writable("./") && file_put_contents("config.php", $file_content)) {
		assert(file_exists("config.php"));
	}
	else {
		$h_file_content = htmlentities($file_content);
		print <<<EOD
		The web server isn't allowed to write to the config file; please copy
	    the text below, save it as 'config.php', and upload it into the SCore
	    folder manually. Make sure that when you save it, there is no whitespace
		before the "&lt;?php" or after the "?&gt;"

		<p><textarea cols="80" rows="2">$file_content</textarea>
						
		<p>One done, <a href='index.php'>Continue</a>
EOD;
		exit;
	}
} // }}}
?>
	</body>
</html>
