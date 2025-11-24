# Language Files

This directory contains the language files for the application. Each file should be a PHP file that returns an array of key-value pairs. The keys should be in English, and the values should be the translated strings.

## Adding a New Language

1.  Create a new file in this directory with the language code as the filename (e.g., `es.php` for Spanish).
2.  Copy the contents of `template.php` into the new file.
3.  Translate the values in the new file.

## Adding a New String

1.  Add the new string to the `en.php` file with the English text as both the key and the value.
2.  Add the new string to all other language files with the English text as the key and the translated text as the value.
3. All keys should be in `lowercase_snake_case`.
