<?php

/**
 * FilterInput Types
 */
define("ARRAY_ITEM", flag_id());
define("FILE", flag_id());
define("SINGLE_VALUE", flag_id());
define("MULTI_VALUE", flag_id());
define("FALSE_IF_EMPTY", flag_id());
define("STRING", flag_id());
define("TO_LOWERCASE", flag_id());
define("TO_UPPERCASE", flag_id());
define("INT", flag_id());
define("INTEGER", flag_id());
define("FLOAT", flag_id());
define("DOUBLE", flag_id());
define("BOOL", flag_id());
define("BOOLEAN", flag_id());
define("EMAIL", flag_id());
define("URI", flag_id());
define("IP", flag_id());
define("IP_ADDRESS", flag_id());
define("JSON", flag_id());
define("UUID4", flag_id());

/**
 * Request Methods
 */
define("GET", flag_id('gijsbos\Http'));
define("POST", flag_id('gijsbos\Http'));
define("PUT", flag_id('gijsbos\Http'));
define("DELETE", flag_id('gijsbos\Http'));
define("PATCH", flag_id('gijsbos\Http'));
define("COPY", flag_id('gijsbos\Http'));
define("HEAD", flag_id('gijsbos\Http'));
define("OPTIONS", flag_id('gijsbos\Http'));
define("LINK", flag_id('gijsbos\Http'));
define("UNLINK", flag_id('gijsbos\Http'));
define("PURGE", flag_id('gijsbos\Http'));
define("LOCK", flag_id('gijsbos\Http'));
define("UNLOCK", flag_id('gijsbos\Http'));
define("PROPFIND", flag_id('gijsbos\Http'));
define("VIEW", flag_id('gijsbos\Http'));
define("COOKIE", flag_id('gijsbos\Http'));
define("SERVER", flag_id('gijsbos\Http'));
define("ENV", flag_id('gijsbos\Http'));