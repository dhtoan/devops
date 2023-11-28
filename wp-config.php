<?php
define('WP_CACHE', true); // Added by FlyingPress
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
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'nspyebce_devops' );

/** Database username */
define( 'DB_USER', 'nspyebce_do' );

/** Database password */
define( 'DB_PASSWORD', 'pT0@n123!@#db' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'a4{^p*TeoP@RL<)6JIQT:/4r/2!&w@P8v1o{/VRs:{( NHzDQD-oCJ7/}_v@V5h^' );
define( 'SECURE_AUTH_KEY',  '-n-jUsIk=/5tVJUFkJZ&ZPVS}Ob]8N/^!Ufb7F$w4[l2(.H{xt2}J6 q/.PLBR&~' );
define( 'LOGGED_IN_KEY',    'x{T}=%tV.InhF|8wkj:He;G[/*&i;tJ|z:86T}.pHLwQ3qw*E:SW%erC{x:9p<=I' );
define( 'NONCE_KEY',        'abCoj*jwGJ)@|EqpG`NWVIt2<a5&nGh.V<yN7E&&QFTGK6)&4J,d<cP(vMn_x2lg' );
define( 'AUTH_SALT',        'V=^U{Apc4LO?OTg/fv@g Q}z5Ya]Y0H(oOT5^;,B B]Q=rri,CIl#bj`apkR$C<#' );
define( 'SECURE_AUTH_SALT', '`K^p0*3&a[%1$)2NJW.<WsK;*MBAA18Plr/hr_VU!,_|z0w((!m7wS=?<Y}o05IY' );
define( 'LOGGED_IN_SALT',   '1gE 3,SyAeHp|{t:QO5di1?e99m|/iq9%`^RlD}^cV!RQP7=lNSgbfcsSj^W/!Ts' );
define( 'NONCE_SALT',       '_v><D5 `22{KW3,hJvOzjM_yTNC{RRP,S<=?%?|(hhe3k!UHOXSvNJhh.X.$%d_i' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
