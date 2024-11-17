<?php
// The path such as /usr/bin/git
$git_path = '/usr/bin/git';

// The path to tar & zip
$tar_path = '/usr/bin/tar';
$zip_path = '/usr/bin/zip';

/***************************************/
/***** END OF CONFIGURATION CHANGES ****/

global $args;
parseArgs();

// Debugging?
if (isset($_SERVER['USER'], $args) && !empty($args['debug']))
	error_reporting(E_ALL);

if (empty($args) || empty($args['src']) || empty($args['dst']))
	die('missing critical settings');

// Get in the trunk.
chdir($args['src']);

if (!empty($args['skip-pull']))
{
	$out = shell_exec($git_path . ' pull');

	// No comprenda senior.
	if (strpos($out, 'From git://github.com') === false && strpos($out, 'Already up-to-date.') === false)
		die('GIT build returned an unexpected output: ' . $out);
}

// Try to find our version.
$pkg_file = file_get_contents('package-info.xml');
preg_match('~<version>([^<]+)</version>~i', $pkg_file, $v);
preg_match('~<name>([^<]+)</name>~i', $pkg_file, $n);

if (empty($v))
	die('Unknown Version');

$version = strtr(
	ucFirst(trim($v[1])),
	array(
		' ' => '-',
		'Rc' => 'RC',
	)
);

if (empty($n))
	die('Unknown Name');

$name = strtr($n[1], array(
	' ' => '-',
));

$package_file_base = $name . '_' . $version;

// Build baby, build!

if (file_exists($args['dst'] . '/' . $package_file_base . '.tgz'))
	unlink($args['dst'] . '/' . $package_file_base . '.tgz');
shell_exec($tar_path . ' --no-xattrs --no-acls --no-mac-metadata --no-fflags --exclude=\'.git\' --exclude=\'screenshots\' --exclude=\'vendor\' --exclude=\'.*\' --exclude=\'composer.*\' -czf ' . $args['dst'] . '/' . $package_file_base . '.tgz *');

// Zip it, zip it good.
if (file_exists($args['dst'] . '/' . $package_file_base . '.zip'))
	unlink($args['dst'] . '/' . $package_file_base . '.zip');
shell_exec($zip_path . ' -x ".git" "screenshots/*" "vendor/*"  ".*/" "composer.*"  -1 ' . $args['dst'] . '/' . $package_file_base . '.zip -r *');

// Undo the damage we did to the package file
shell_exec($git_path . ' checkout -- package-info.xml');

// FINALLY, we are done.
exit;

function parseArgs()
{
	global $args;

	if (!isset($_SERVER['argv']))
		$_SERVER['argv'] = array();

	// If its empty, force help.
	if (empty($_SERVER['argv'][1]))
		$_SERVER['argv'][1] = '--help';

	// Lets get the path_to and path_from
	foreach ($_SERVER['argv'] as $i => $arg)
	{
		// Trim spaces.
		$arg = trim($arg);

		if (preg_match('~^--src=(.+)$~', $arg, $match) != 0)
			$args['src'] = substr($match[1], -1) == '/' ? substr($match[1], 0, -1) : $match[1];
		elseif (preg_match('~^--dst=(.+)$~', $arg, $match) != 0)
			$args['dst'] = substr($match[1], -1) == '/' ? substr($match[1], 0, -1) : $match[1];
		elseif ($arg == '--debug')
			$args['debug'] = 1;
		elseif ($arg == '--help')
		{
			echo 'Build Tool
Usage: /path/to/php ' . realpath(__FILE__) . ' -- [OPTION]...

    --src               	Path to customization (' . realpath($_SERVER['PWD']) . ').
    --dst            		Output directory for files (' . realpath($_SERVER['PWD'] . '/..') . ')
    --debug                 Output debugging information.';
    		die;
		}

		// Did we shortcut this?
		if (empty($args['src']) && empty($args['dst']) && file_exists(realpath($_SERVER['PWD'] . '/' . $_SERVER['argv'][1])))
		{
			$args['src'] = realpath($_SERVER['PWD'] . '/' . $_SERVER['argv'][1]);
			$args['dst'] = realpath($_SERVER['PWD'] . '/');
		}
		
		if (empty($args['src']))
			$args['src'] = realpath($_SERVER['PWD']);
		if (empty($args['dst']))
			$args['dst'] = realpath($args['src'] . '/..');

		// We have extra params.
		if (preg_match('~^--(.+)=(.+)$~', $arg, $match) != 0 && !array_key_exists($match[1], $_POST))
			$_POST[$match[1]] = $match[2];
	}
}
