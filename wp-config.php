<?php
define('WP_CACHE', true); // WP-Optimize Cache
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'donnbarbearia_com_br');
/** MySQL database username */
define('DB_USER', 'donnbarbeariacom');
/** MySQL database password */
define('DB_PASSWORD', 'J5XFu?-z');
/** MySQL hostname */
define('DB_HOST', 'mysql.donnbarbearia.com.br');
/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');
/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');
/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'EZ*Te*b)uUJq+c2|TWl1XW"+o)d97VSlWho&N|Q!Tpa58)lxXau(658*w5mnF7N!');
define('SECURE_AUTH_KEY',  '$z7$O6apNx%gP_2jRqzl#T0EH6kO:9!8LM7?3mULxn#0LP(%rZl58Gq2#i#:x$7&');
define('LOGGED_IN_KEY',    'D_IrGu&1tmgNnasR$Zjdcz(_jFw4:M$%x!5jEPmftfx:MBrTZouoNUPV(xrW3?9r');
define('NONCE_KEY',        'dBTvxT+wWsq^j6SzQ4+u2y|u:/V8oQb%+RDxaH+rHiF||B)#%CI^J*iyYNxhY6:s');
define('AUTH_SALT',        '!EQ!htTY92bJ/S%73NtxVaNc_Nrtjgb26diPDc7p4WFaEm:0JQ6JAY:F4QjEz+1P');
define('SECURE_AUTH_SALT', '+%gMksY*V9hpPGi98/;L8Xe7Q"%cO12tm%E#uy*2w)alg:i&J$Lb_*UxV7FPzM$2');
define('LOGGED_IN_SALT',   '$|KIng!mI`brCTilC+NLoB~nHpn?+(CV"o~~Z"cDZ$8u0!|Z&t_4bqmh;y&NnIcV');
define('NONCE_SALT',       ':8:atp`%CEacMbm:)z36r?7aopH2VYt4Se`CuW?6~i0&kkG5M7AfMMkVY@`ld3i:');
/**#@-*/
/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_etqgff_';
/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);
/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);
/**
 * Removing this could cause issues with your experience in the DreamHost panel
 */
if (preg_match("/^(.*)\.dream\.website$/", $_SERVER['HTTP_HOST'])) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        define('WP_SITEURL', $proto . '://' . $_SERVER['HTTP_HOST']);
        define('WP_HOME',    $proto . '://' . $_SERVER['HTTP_HOST']);
}
/* That's all, stop editing! Happy blogging. */
/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');
/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');