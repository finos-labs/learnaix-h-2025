<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli'; // Using MySQL Improved extension
$CFG->dblibrary = 'native'; // Using native PHP database library
$CFG->dbhost    = 'db'; // Database host (service name in Docker Compose)
$CFG->dbname    = 'moodle'; // Database name
$CFG->dbuser    = 'moodle'; // Database user
$CFG->dbpass    = 'moodle'; // Database password
$CFG->prefix    = '';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'http://localhost:8080'; // URL to access Moodle
$CFG->dataroot  = '/var/www/moodledata'; // Path to Moodle data directory
$CFG->admin     = 'admin'; // Admin username

$CFG->directorypermissions = 0777; // Permissions for created directories

require_once(__DIR__ . '/lib/setup.php'); // Load Moodle's core libraries

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
