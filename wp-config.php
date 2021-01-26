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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'test' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '12nyAz[Z)AjbTR/B,57`tGPZ= p;)T4uv&MrgL(*&FH25 y05?*eU{^h64qZ!#8v' );
define( 'SECURE_AUTH_KEY',  '#g4]z!kWEd5sI~1fVEu+3)=_)m|6H%O8kMj~2w#zz+E]47$s&i~;,CMfA,halZCk' );
define( 'LOGGED_IN_KEY',    '|>!,W>=L;J|Am;%^>O~d&`wjzZ5|Cep %Kg]!,^)f8f0K?pO3U&L9KKe>u-TZH K' );
define( 'NONCE_KEY',        ',VH&>BII;nDuw|j|+4Na,zM{ l(?W}y.<?(h;xF&)/$:d{6`Z2bwn[^s+,jRMO<-' );
define( 'AUTH_SALT',        'CBVk1WR}eX/cOq^/x:Ryc__h%locMW4;4Uw%Sa7FL*>[G(A~7)1U?s@^wRElf~ht' );
define( 'SECURE_AUTH_SALT', '^4D(DM^}43KuHh64h]=vistZS(TM1p/|iQlohry~*6^co[@>@;-!x!sDdJ+/m7K(' );
define( 'LOGGED_IN_SALT',   '|L!Af&3=1e%:s#Q^_^U2ik4De9;+m]]*=E;k+3>(h!W(hw*70tQDz]fHH#W(cu,.' );
define( 'NONCE_SALT',       ';|4_ YN4O(mc@Xs8NVu})uFfu7je]vduv6nPw39Lxv@-W&.9HiY711]II7,}2M6`' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_test';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
