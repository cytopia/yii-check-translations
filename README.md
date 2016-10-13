# yii-check-translations

Shell script to check/validate yii translations in various directories recursively.

1. Check if all `messages` directories have specified language dirs
2. Check if every language dir has the same set of files
3. Check if all language files (of different languages) have the same PHP array indices

## Usage

```shell
$ ./yii-check-translations.php <path-to-yii-project> lang1 [lang2 [lang3 ...]]

  <path-to-yii-project>   Specify path to yii project or any other path inside the project.
                          This script will look for 'messages' folders and compare the
					      specified languages against each 'messages' folder.

  lang1                   First language to check.
                          If only one language is specified, no index comparison with other
						  languages will be possible.

  lang2                   (optional) Second language. each file of the second language will
                          also make an index comparison against the other language.

  ...                     Specify as many languages as you like
```

## Example usage

### (1/3) Missing language directories

```shell
$ ./yii-check-translations.php /shared/httpd/yii-project/ de_DE en_GB
--------------------------------------------------------------------------------
- (1/3) Checking directories pairs
--------------------------------------------------------------------------------

[E] Missing: /shared/httpd/yii-project/application/gii/module/templates/whitelabel/messages/de_DE
[E] Missing: /shared/httpd/yii-project/application/gii/module/templates/whitelabel/messages/en_GB

[X] Found:   /shared/httpd/yii-project/application/messages/de_DE
[X] Found:   /shared/httpd/yii-project/application/messages/en_GB

[X] Found:   /shared/httpd/yii-project/application/modules/admin/messages/de_DE
[X] Found:   /shared/httpd/yii-project/application/modules/admin/messages/en_GB

[X] Found:   /shared/httpd/yii-project/application/yii/requirements/messages/de_DE
[E] Missing: /shared/httpd/yii-project/application/yii/requirements/messages/en_GB

[FAIL] 3 missing language folder(s).
==> Aborting...
[1]
```

### (2/3) Missing translation files

```shell
$ ./yii-check-translations.php/shared/httpd/yii-project/application/modules de_DE en_GB
--------------------------------------------------------------------------------
- (1/3) Checking directories pairs
--------------------------------------------------------------------------------

[X] Found:   /shared/httpd/yii-project/application/modules/admin/messages/de_DE
[X] Found:   /shared/httpd/yii-project/application/modules/admin/messages/en_GB

[X] Found:   /shared/httpd/yii-project/application/modules/user/messages/de_DE
[X] Found:   /shared/httpd/yii-project/application/modules/user/messages/en_GB

[PASS] All subdirectories have all language folders.

--------------------------------------------------------------------------------
- (2/3) Check if all possible lang files are present in each lang folder
--------------------------------------------------------------------------------

[E] File not found: /shared/httpd/yii-project/application/modules/user/messages/en_GB/emails.php

[FAIL] 1 language file(s) missing
==> Aborting...
[1]
```

### (3/3) Missing PHP indices

```shell
$ ./yii-check-translations.php /shared/httpd/yii-project/protected/application/messages de_DE en_GB
--------------------------------------------------------------------------------
- (1/3) Checking directories pairs
--------------------------------------------------------------------------------

[X] Found:   /shared/httpd/yii-project/protected/application/messages/de_DE
[X] Found:   /shared/httpd/yii-project/protected/application/messages/en_GB

[PASS] All subdirectories have all language folders.

--------------------------------------------------------------------------------
- (2/3) Check if all possible lang files are present in each lang folder
--------------------------------------------------------------------------------

[PASS] All files exist.

--------------------------------------------------------------------------------
- (3/3) Validate PHP Array indices
--------------------------------------------------------------------------------

==> /shared/httpd/yii-project/protected/application/messages/de_DE/BrowserHintWidget.php ... OK
==> /shared/httpd/yii-project/protected/application/messages/en_GB/BrowserHintWidget.php ... OK
==> /shared/httpd/yii-project/protected/application/messages/de_DE/core.php ... ERROR
[E] de_DE Missing key 'Remote Addr' (found in: en_GB)
[E] de_DE Missing key 'moduleToken:dlt' (found in: en_GB)
==> /shared/httpd/yii-project/protected/application/messages/en_GB/core.php ... ERROR
[E] en_GB Missing key 'Remote Address' (found in: de_DE)
[E] en_GB Missing key '(dd/mm/yyyy)' (found in: de_DE)
[E] en_GB Missing key '{attribute} can not be blank.' (found in: de_DE)
[E] en_GB Missing key '{attribute} is not a valid email address.' (found in: de_DE)
==> /shared/httpd/yii-project/protected/application/messages/en_GB/core.php ... OK
==> /shared/httpd/yii-project/protected/application/messages/de_DE/core.php ... OK
==> /shared/httpd/yii-project/protected/application/messages/en_GB/emails.php ... OK
==> /shared/httpd/yii-project/protected/application/messages/de_DE/emails.php ... OK
==> /shared/httpd/yii-project/protected/application/messages/de_DE/javascript.php ... ERROR
[E] de_DE Missing key 'MB of' (found in: en_GB)
[E] de_DE Missing key 'MB at' (found in: en_GB)
[E] de_DE Missing key 'MBps' (found in: en_GB)
[E] de_DE Missing key 'remaining' (found in: en_GB)
==> /shared/httpd/yii-project/protected/application/messages/en_GB/javascript.php ... ERROR
[E] en_GB Missing key '{personName} is already in the list.' (found in: de_DE)
[E] en_GB Missing key 'Salutation can not be empty.' (found in: de_DE)
[E] en_GB Missing key ' MB of ' (found in: de_DE)
[E] en_GB Missing key ' MB at ' (found in: de_DE)
[E] en_GB Missing key ' MBps' (found in: de_DE)
[E] en_GB Missing key ' remaining' (found in: de_DE)
==> /shared/httpd/yii-project/protected/application/messages/en_GB/tasks.php ... OK
==> /shared/httpd/yii-project/protected/application/messages/de_DE/tasks.php ... OK

[FAIL] 16 language file(s) have different indices
==> Aborting...
```
