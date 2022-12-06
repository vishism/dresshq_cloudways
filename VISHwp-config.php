<?php
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

// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp5905095' );

/** MySQL database username */
define( 'DB_USER', 'wpuser21051' );

/** MySQL database password */
define( 'DB_PASSWORD', 'ksn8LYWpmCxwbsc' );

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
define( 'AUTH_KEY',          '}rC4}0@U6;a)tn$pd&foLQ^OX{&STPeBZ`MX992gIgm%@`fWO3L08ER[is^yxF-a' );
define( 'SECURE_AUTH_KEY',   'ObmlI/b&5WfEf]AW#>GfV!A0.K)RnNX/mEeMG846#:`oY~BOYtG[ddMMI!nC@*j`' );
define( 'LOGGED_IN_KEY',     '#,Tf$Go01*f9LaB]$-!fjar+Yvq #92r_?S;m0>j<R>V69+0C6YulgaBDD#(yo8h' );
define( 'NONCE_KEY',         '&0f:2k,;JAi&vc%+5BdY*mP*Zg7mT=CF(/:NB=YFL7tI)GRQT:_.Kct`ELf,cLz8' );
define( 'AUTH_SALT',         '89u*JkZG#cPH:Ark4V7TDKktcLX-THrwh8w t]y~t8nmnady$MXo5G(~X*gR$Stv' );
define( 'SECURE_AUTH_SALT',  'RjG?*9Jdg>-512!dh5<h.g]MD&0}GqZ<>x^3q.wHN`)KHa3qI!f)-KknZmIS5+O^' );
define( 'LOGGED_IN_SALT',    'd3^%Bn5@-KolmXQ$G/xB*.jz *$@5MEp)bI,0CGm))_5Ub*KTKJJ-ZAB!N~9j%U!' );
define( 'NONCE_SALT',        ')x xWf ELt>E0.3@!k2_@W0;yPE4]UnF<lI(o~Cl,i(2Ji[,~yF?{C(pHP} EMPQ' );
define( 'WP_CACHE_KEY_SALT', 'VttP$]f,8t]kSpwl+n}`<40w7?qwz97W$KiXbaQ;>{OuflOVP~SG+X0D*]]1D~iY' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


define( 'FORCE_SSL_ADMIN', false );


/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
