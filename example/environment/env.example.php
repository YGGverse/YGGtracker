<?php

/*
 * MIT License
 *
 * Copyright (c) 2023 YGGverse
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Project home page
 * https://github.com/YGGverse/YGGtracker
 *
 * Get support
 * https://github.com/YGGverse/YGGtracker/issues
*/

// Debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Database
define('DB_PORT', 3306);
define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');

// Sphinx
define('SPHINX_HOST', '127.0.0.1');
define('SPHINX_PORT', 9306);

// Memcached
define('MEMCACHED_PORT', 11211);
define('MEMCACHED_HOST', 'localhost');
define('MEMCACHED_NAMESPACE', 'yggtracker');
define('MEMCACHED_TIMEOUT', 60 * 5);

// Webapp
define('WEBSITE_URL', '');
define('WEBSITE_NAME', 'YGGtracker');
define('WEBSITE_CSS_VERSION', 1);

define('WEBSITE_PAGINATION_LIMIT', 20);

// Moderation
define('MODERATOR_IP_LIST', (array)
  [
    '127.0.0.1',
    // ...
  ]
);

// User
define('USER_DEFAULT_APPROVED', false);

define('USER_AUTO_APPROVE_ON_MAGNET_APPROVE', false);
define('USER_AUTO_APPROVE_ON_COMMENT_APPROVE', false);
define('USER_AUTO_APPROVE_ON_IMPORT_APPROVED', false);

define('USER_DEFAULT_IDENTICON', 'jidenticon'); // jidenticon|false
define('USER_IDENTICON_FIELD', 'address'); // address|userId|...

// Magnet
define('MAGNET_DEFAULT_APPROVED', USER_DEFAULT_APPROVED);
define('MAGNET_DEFAULT_PUBLIC', false);
define('MAGNET_DEFAULT_COMMENTS', true);
define('MAGNET_DEFAULT_SENSITIVE', false);

define('MAGNET_AUTO_APPROVE_ON_IMPORT_APPROVED', true);


define('MAGNET_EDITOR_LOCK_TIMEOUT', 60*60);

define('MAGNET_TITLE_MIN_LENGTH', 10);
define('MAGNET_TITLE_MAX_LENGTH', 140);

define('MAGNET_PREVIEW_MIN_LENGTH', 0);
define('MAGNET_PREVIEW_MAX_LENGTH', 255);

define('MAGNET_DESCRIPTION_MIN_LENGTH', 0);
define('MAGNET_DESCRIPTION_MAX_LENGTH', 10000);


define('MAGNET_STOP_WORDS_SIMILAR',
  [
    'series',
    'season',
    'discography',
    // ...
  ]
);

// Comment
define('MAGNET_COMMENT_DEFAULT_APPROVED', false);
define('MAGNET_COMMENT_DEFAULT_PUBLIC', false);
define('MAGNET_COMMENT_MIN_LENGTH', 1);
define('MAGNET_COMMENT_MAX_LENGTH', 1000);

// Yggdrasil
define('YGGDRASIL_HOST_REGEX', '/^0{0,1}[2-3][a-f0-9]{0,2}:/'); // thanks to @ygguser (https://github.com/YGGverse/YGGo/issues/1#issuecomment-1498182228 )

// Crawler
define('CRAWLER_SCRAPE_QUEUE_LIMIT', 1);
define('CRAWLER_SCRAPE_TIME_OFFLINE_TIMEOUT', 60*60*24);

// Node
define('NODE_RULE_SUBJECT', 'Common');
define('NODE_RULE_LANGUAGES', 'All');

// API
define('API_VERSION', 1);

/// Export
define('API_EXPORT_ENABLED', true);

define('API_EXPORT_PUSH_ENABLED', true);             // depends of API_EXPORT_ENABLED

define('API_EXPORT_USERS_ENABLED', true);            // depends of API_EXPORT_ENABLED
define('API_EXPORT_MAGNETS_ENABLED', true);          // depends of API_EXPORT_ENABLED, API_EXPORT_USERS_ENABLED
define('API_EXPORT_MAGNET_DOWNLOADS_ENABLED', true); // depends of API_EXPORT_ENABLED, API_EXPORT_USERS_ENABLED, API_EXPORT_MAGNETS_ENABLED
define('API_EXPORT_MAGNET_COMMENTS_ENABLED', true);  // depends of API_EXPORT_ENABLED, API_EXPORT_USERS_ENABLED, API_EXPORT_MAGNETS_ENABLED
define('API_EXPORT_MAGNET_STARS_ENABLED', true);     // depends of API_EXPORT_ENABLED, API_EXPORT_USERS_ENABLED, API_EXPORT_MAGNETS_ENABLED
define('API_EXPORT_MAGNET_VIEWS_ENABLED', true);     // depends of API_EXPORT_ENABLED, API_EXPORT_USERS_ENABLED, API_EXPORT_MAGNETS_ENABLED

/// Import
define('API_IMPORT_ENABLED', true);

define('API_IMPORT_PUSH_ENABLED', true);                   // depends of API_IMPORT_ENABLED

define('API_IMPORT_USERS_ENABLED', true);                  // depends of API_IMPORT_ENABLED
define('API_IMPORT_USERS_APPROVED_ONLY', false);           // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED
define('API_IMPORT_MAGNETS_ENABLED', true);                // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED
define('API_IMPORT_MAGNETS_APPROVED_ONLY', false);         // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED, API_IMPORT_MAGNETS_ENABLED
define('API_IMPORT_MAGNET_DOWNLOADS_ENABLED', true);       // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED, API_IMPORT_MAGNETS_ENABLED
define('API_IMPORT_MAGNET_COMMENTS_ENABLED', true);        // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED, API_IMPORT_MAGNETS_ENABLED
define('API_IMPORT_MAGNET_COMMENTS_APPROVED_ONLY', false); // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED, API_IMPORT_MAGNETS_ENABLED, API_IMPORT_MAGNET_COMMENTS_ENABLED
define('API_IMPORT_MAGNET_STARS_ENABLED', true);           // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED, API_IMPORT_MAGNETS_ENABLED
define('API_IMPORT_MAGNET_VIEWS_ENABLED', true);           // depends of API_IMPORT_ENABLED, API_IMPORT_USERS_ENABLED, API_IMPORT_MAGNETS_ENABLED