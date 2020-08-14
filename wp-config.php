<?php
define( 'WP_CACHE', true ); // Added by WP Rocket

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
define('AUTH_KEY',         'yab9BIju3YIl2e7pc5tQkBslN59pCBLKqX7h0r8g6z/O2z5NdqTDb6+2abKM/Hov3p2BeDoATvQFt2Nh/ezQQA==');
define('SECURE_AUTH_KEY',  '5dy6K3HXntGmQ+sJSroXd2WpD5NoSyW+TC39cRoqyzRGf4DWlkVSxzJYpUmVjmACT15IqFeUpSHL2wE0hKZ4WA==');
define('LOGGED_IN_KEY',    'pvrEySqVCpzW8QOTrROv8o9OeXMzENeGAgvRh/4oCD2KqJ94ATHim0fqLwECJkfEg4A25ns2xYNdxWd0DSsI+w==');
define('NONCE_KEY',        '0bu602sAJ8EOM//VOjXvgj7Z32w47hPrVo0vNIwLgunLJV0azrb6RSKZgitiUQu5iWDDaO07/F0zAEc7LRElvQ==');
define('AUTH_SALT',        'Xh5NNYz83A6NsYHdwe3i82FGQ/2jVvWX7IcmOlk3wsgde0k+F+AViM6m3h+gSbFirci6qWCcKyPKkuJVailjQQ==');
define('SECURE_AUTH_SALT', 'At59Mr2mdZIf7yalCWtVfd+m1eDCLl8r9c7MS5w0h5fgWuwot+EiYtg7JEv7pThsRkAPBW8u5+WxXG/NTGPdiw==');
define('LOGGED_IN_SALT',   '4BUvy+DU82mF+M/r+KLEeN8Yi0pcTOtGaFAvCOrfOKqoZJ1sN5vitthT2eB/H3EvvwhsAQwF80rsf+Jjp2pyiA==');
define('NONCE_SALT',       'QZCwLSFRrAB6vPapUz2Egzwq4xb22rJpwy4qcEblmJMBP2no8oJu7M5IeprAgRg0xvM2lvlHbRB4FMldMP2gkw==');

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
