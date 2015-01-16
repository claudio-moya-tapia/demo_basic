<?php
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
define('DB_NAME', 'xxxxxx');


/** MySQL database username */
define('DB_USER', 'xxxxxx');


/** MySQL database password */
define('DB_PASSWORD', 'xxxxxx');


/** MySQL hostname */
define('DB_HOST', 'localhost');


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
define('AUTH_KEY',         'N%X7d|)2cK/A;]#}OPjV]k2*pm(cB>J69C9RXuDUROgSN!7xo{;rVC-< bYed:L1');

define('SECURE_AUTH_KEY',  'oZeGfG_t ;X6,p/)li9]_|s2=mw2_0g+)Bq=4TNYfx-7*W`[tXpQ:zzA!bpN-Ob~');

define('LOGGED_IN_KEY',    'Y?C5i *HcvhsG(frUIKZ+9gBv{?NA((*|#/N[#LO<*j+od+%W<eK`~00-FQ$y-!y');

define('NONCE_KEY',        '|fG*x6zy@0hnxy-a`W/H~hNHDyyZES#zq&~QAkyc!+$u4~u<N=yn*js)(+)*U=-q');

define('AUTH_SALT',        'PBfFg)a&4}qbE2O~;RrT_cUR OVaf2eLdm9Bp6VPW}hA,Lr#yrmdCTX8yJaijk|Y');

define('SECURE_AUTH_SALT', 'p9h8rhMWrTo4|P@N9 PK+,j@Qg03BC@f@Hl$uQD+V{*+qDWsUZAaw-Sl&6|t%p|E');

define('LOGGED_IN_SALT',   '&#F[|aUeUj`.nZ(h=hX24wEv;Z*mbj+uX=VIDI[XJk[kXax2r_1V]^Fl;P|Ztx]Y');

define('NONCE_SALT',       'DoM*olK4glzM|HQ/$0}#>k+:Qs}6#|+As[vm/L[lvSl8z4+c8x$K&g|U{`9}R1;=');


/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';


/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', 'es_CL');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
