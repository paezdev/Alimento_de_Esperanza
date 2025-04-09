<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'DE^u~OfOHP(VHI^8 vNOA-k=v7qmbpMi2wGlo~(F,b][}e}Lk]Ajf~?{Hn0e1ZX:' );
define( 'SECURE_AUTH_KEY',   '+FWAAGw{5`Q@0(x,u3koAJE6%iac/8{(q?LN+c*zgp88U]UD&;r%{je2(e=u4s$I' );
define( 'LOGGED_IN_KEY',     'nB-`W7]ErP*N}IGhg>^/P/Z dG9_8L*2uNS>??RxjE(P-*oXZU2%sr!ErP=WHyI-' );
define( 'NONCE_KEY',         'z_EhQsgT7|7!%iX#-~Ka}]keCwabC`0=D*-#<l1g[),JlQm/r9/0DKR3riQ+eAD6' );
define( 'AUTH_SALT',         '8:gN@,,KSW{M/%-2rYG7$+]Pe6N5r5X}yLu:A?9S1eAIXThxU8?p0Nih;aJVd.&G' );
define( 'SECURE_AUTH_SALT',  'LUc;R-~?g7{yJ@SIgeIxRPWagk}j#ByN1=Q=}M2`tu3`b}_qMCFX,}#z5V@eI{)M' );
define( 'LOGGED_IN_SALT',    '=7j{<h)B?~;/1e?9{=mJ^:TpW5.P>1smJ=t*=e(4FnpFwP!FMG3pj--3+B:90]bU' );
define( 'NONCE_SALT',        'm.@g&^)8hcnA8FM]!c!@E&0&Kg){@QI3Sft~{pbzt:lmTFWg#N;3GKYYceTdN3S0' );
define( 'WP_CACHE_KEY_SALT', 'dqpoN:#y-ED}!YC_y%)@fA47k)>g<i@|~6K<,?@cR-f#`):p+<j/DW-;k4K/{KaG' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
