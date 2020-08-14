<?php
// define( 'WP_CACHE', true ); // Added by WP Rocket
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );
/** MySQL database username */
define( 'DB_USER', 'root' );
/** MySQL database password */
define( 'DB_PASSWORD', 'root' );
/** MySQL hostname */
define( 'DB_HOST', 'localhost' );
/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );
/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'oNIVrIGbiY3B+v3iZty3ht1WctG8wn+7zpNJRYYY45atBTjIa5PNrclafwPUFB66afRL5+yrlRluMjN7U/Yqcw==');
define('SECURE_AUTH_KEY',  'NnNZJfFD+oju6HCemOYpi58b9gUVsOzfJ6d6R84HS9HL64riwhyxvU/En8KljSza2Ks6Ypr9P/hhHTNTK3yqcA==');
define('LOGGED_IN_KEY',    'JygiN75dQYJ5QaqVDart0eF9ZQyV7UEnbQ7IA6LZhmPmV+jCHQLK0rOb9JvNixVZxfyLzeO7jIkbaT7cFeIt5A==');
define('NONCE_KEY',        'teY3Jac5HFFwMZ+mdETf4vB7BUAsxurlhjFGewk5y5adbDF7cvBN6N6RBwRi2nRbNwfuB9PGCydWebVIPRvTXg==');
define('AUTH_SALT',        'fPh3+/d3BSvPvGQXxPhGudmBv0aEjritVPovsseAu1kQMcA0fT+Iv/OPBWM0Eov8z48S9zj5xGDQjOCFwquruw==');
define('SECURE_AUTH_SALT', 'x6dvE9jx0im5+CgKn5DqmdxRa64Y2WD6Xl04f1ogDnbqMBl8q2R72DFCUP40Pdm92POBaWKr+6oTkOY1mYd6PQ==');
define('LOGGED_IN_SALT',   'SODIvGEvqtaIx/dzDZCPYt4JDXZWX8SMoUIFGzKQE3lpjsGi/xlWR9kLPJMh6CRFcSjLwMYD8Q3rs1jDtCFLbw==');
define('NONCE_SALT',       'H24o/SUjhMP++sImP/ApsOTcYyliiO+vFGqyt5xZ7k5+PL24zeWDh0st/1IVMFCgcoMaf00dBmlRpvMYKS6p2A==');
/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';