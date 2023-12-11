# Docblock writer

This module contains a task `DocblockTagWriterTask` that automatically writes docblock `@method` tag's on `DataObject`'s and `Extension`'s.

This is only intended to be used on Silverstripe modules that are either commercially supported, or managed by the
Silverstripe CMS squad either in a dev or a CI environment. It is not intended to be used on regular projects.

## Usage

`composer install --dev silverstripe/docblock-writer`

`vendor/bin/sake dev/tasks/SilverStripe-DocblockWriter-Tasks-DocblockTagWriterTask <path>`
