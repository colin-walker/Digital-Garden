# Digital-Garden
Digital garden add-on for the (b)log-In system

Requires the [(b)log-In](https://github.com/colin-walker/blog-In) blogging system to operate.
Uses the same config.php and content_filters.php files.

Use the following SQL to create the garden table (replace pref with your table prefix from (b)log-In):

```
CREATE TABLE `pref_garden` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Updated` datetime NOT NULL,
  `Archive` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

config.php will also need the following line added (again replace pref):

`define('GARDEN', 'pref_garden');`
