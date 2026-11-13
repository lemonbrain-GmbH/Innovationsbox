#v1.4.3

* Remove moment.js from this package, since it is available in the core (and used/updated from there) now;

#v1.4.2

* Change check for "numeric" value for "Page" field type instead of "string", which would cause not to show in PHP 8.1.x and up (special thanks to Lemonbrain for quick debugging);

#v1.4.1

* Add check if file exists before showing form in "Edit mode" (to prevent popup saying "File not found");

#v1.4.0

* Add option to exclude/include plugins for "WYSIWYG" field type, by passing an array of plugin keys into the config (only for non-repeatable);

#v1.3.1

* Add PHP8 support (previous versions wouldn't work at all);

#v1.3.0

* Fixed non-working "File" field type used in "Repeatable" field type in combination with ConcreteCMS 9.x;
* Fixed non-working "Image" field type used in "Repeatable" field type in combination with ConcreteCMS 9.x;
* Update "Link" field type used in "Repeatable" field type in combination with ConcreteCMS 9.x (new page selector);

#v1.2.5

* Fix non-working tabbed content in version 9 and higher of ConcreteCMS;

#v1.2.4

* Additional checks for building database file, view template and additional templates - makes it possible to (temporarily) extend Block Types from core BlockController;

#v1.2.3

* Fixed non-working tooltip in version 9 when hovering over question mark icon (?) in forms;

#v1.2.2

* Code readability/guidelines: Applied PHP CS Fixer on all PHP files;
* Made it possible to have up to 2000 characters (previously 255) for the "Url" field type;

#v1.2.1

* Fixed bug where WYSIWYG content would be translated from EditMode in normal page viewing mode, resulting in i.e. links not te be prettified (index.php?cID=123);
* Minor code improvement/removal;

#v1.2.0

* Fixed bug where upon duplicating a Block Type, Repeatable items wouldn't get duplicated along with them (which also happens when changing Custom Template);

#v1.1.2

* Fixed issue where if both default and field suffix were set, the field suffix would not overrule the default value;
* Fixed issue where "class" attribute would not be set when used for the "Image" field type;

#v1.1.1

* Fixed "Command" class compatibility issues because of core SymfonyCommand update;

#v1.1.0

* Added $btExportPageColumns and $btExportFileColumns support for multilingual sites - used in "File", "Image" and "Page" field types (`getExportFileColumns` and `getExportPageColumns` function calls);
* Fixed non-rendering link from "Page" field type when (copy) pasted from "Clipboard";

#v1.0.0

* Breaking change for "Code" field type, now does htmlspecialchars (concrete5 h() function) on the value by default for security reasons;
* Added option to skip htmlspecialchars for "Code" field type by setting "skip_h" value to `true` in the config (see documentation);
* Added option to skip `<pre><code>` open/close tags for the "Code" field type by setting "skip_pre_code" value to `true` in the config (see documentation);
* Minor code change in "Repeatable" field type for "getSearchableContent", a function that handles the search indexing;
* Fixed issue with "Page" field type, when pasted from the clipboard with a selected page, it would throw an error and not add/paste the block on the page;

#v0.9.12

* Fixed issue with "Repeatable" field type, where some assets weren't loaded for some field types used within a "Repeatable" field;
* Minimal CSS additions for "datetimepicker" widget, due to within the concrete5 "composer" form not loading frontend/Bootstrap CSS;

#v0.9.11

* Fixed non-working "Page" field type in some situations (by adding "core/sitemap" asset upon add/edit);

#v0.9.10

* Fixed issue with "options" within the "Select" field type, when array keys were numeric (changed from array_merge() to a union of the arrays);

#v0.9.9

* Added configurability for default "prefix" and "suffix" values for field, to increase view/template readability;
* Field types "Image" and "Page" now always save an integer (0) when no image/page was selected;

#v0.9.8

* Fixed compatibility issues with concrete5 versions below 8.2;

#v0.9.7

* Fixed issue with "File" validation (used in "Image" and "File" field types), where after hitting "Clear" to remove the file, it would not validate correctly (because of default "0" string in a hidden input field, entered by concrete5 core);

#v0.9.6

* Fixed an issue for validation of the "Image" field type, giving an error: "Cannot use Concrete\Core\File\File as File because the name is already in use";

#v0.9.5

* Implemented a check before uninstalling, if there are still Block Types installed created with this Add-On, which have to be uninstalled for before actually uninstalling this Add-On;

#v0.9.4

* Fixed issue when name/description or interface height/width was changed in the config file, it wouldn't actually update the values;
* Developers: Core::make for legacy versions rewritten to non-deprecated code;
* Developers: "execute" function calling on the Connection class rewritten to non-deprecated code (executeQuery);
* Developers: Rewritten "scandir" function to use the core/concrete5 file service instead;

#v0.9.3

* Fixed an issue with environment/overrides cache (needed to be flushed), which caused errors upon generating a Block Type in some situations - not all situations;
* Developers: Removed unused use statements in classes located in the "/block_developer/src/BlockDeveloper/Block/Builder/" directory;

#v0.9.2

* Reduced minimum concrete5 version from 8.2 to 5.7.5.3 - for those still using old(er) concrete5 versions;
* Developers: Changed "use BlockType" into the full class name;

#v0.9.1

* Better error message upon entering an invalid "Block Handle" for the CLI command;
* Handles of the default Block Types that ship with your concrete5 install (like "autonav" or "faq" for example), can't be used for generating Block Types in Block Developer;
* Developers: Changed "use Package" and "use Config" into their full class names;
* Developers: Removed unneeded defined('C5_EXECUTE') or die("Access Denied.") in a whole lot of files;

#v0.9.0

* Initial Release
