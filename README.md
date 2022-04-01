# moodle-local_oer

The Open Educational Resources (OER) plugin provides a graphical user interface (GUI) to release files from courses for public use.
The first version of the plugin was developed during a project about [open education](https://www.openeducation.at/). It was developed very close to the Graz University of Technology customizations of Moodle. The use for other educational institutions was therefore very limited. 

In this repository a refactored version of the plugin can be found. This version has been developed for a vanilla moodle. Adaptations, which are necessary for the Moodle instance at Graz University of Technology, were moved to subplugins. The base plugin is fully functional without these subplugins.

# Setup

After installation of the plugin, there are several settings that can be made:

* `local_oer | metadataaggregator` if a subplugin is for linked external course metadata is installed, select the subplugin that should be used. (Default: Default courseinfo aggregator) 
* `local_oer | uselicensereplacement` When enabled, an additional textarea will appear below. (Default: No)
* `local_oer | licensereplacement` Enter replacements for the moodle license shortnames in the format: `cc-nd=>CC BY-ND 3.0`. The exchange takes place when the metadata is queried.
* `local_oer | zipperfilesize` Size of the ZIP files. The Zipper is a helper that can be used by upload subplugins to create ZIP Packages from released files in courses. For administrators there is a Download ZIP button in the OER view in courses, but this always generates only one ZIP of full size and ignores this setting.
* `local_oer | allowedlist` The capabilities to use the oer plugin are set for the editingteacher role by default. An allowance list is used to allow instructors to share files for OER. If all editingteachers should get access to it. This setting can be turned off, and the list then is used as disallowance list.
* `local_oer | notallowedtext` The text that is shown to editingteachers when they are not allowed to use the plugin.
* `local_oer | pullservice` When enabled, the Metadata and URLs to the released files can be directly accessed in Moodle (Default: No). The released metadata can be accessed through `https://*yourmoodledomain*/local/oer/public_metadata.php`.
* `local_oer | next_upload_window` This Button opens a configuration formular to configure the release cycle.
* `local_oer | releaseplugin` Select the upload plugin that will be used to upload released files to a repository.
* List of installed subplugins. Enable or disable them. If a subplugin has settings, a settings link will be shown.

# Release snapshots
 TODO
 
# Metadata

TODO

# Subplugin types

There are three subplugintypes that can be used with this plugin.

## Metadata aggregator

Mainly used to load additional metadata from linked courses of the educational institution. If needed, additional metadata fields can also be attached to the file metadata.

## Classification

Extend the formular for file metadata with additional classification information. Multiple classification plugins can be used.

## Uploader

The base plugin has an endpoint that provides the metadata of all published files including links to the files as JSON. If the preferred way to load the data from Moodle is not via this pull service, but via upload, an additional plugin can be defined which enables the upload to a repository.

## Subplugins used by Graz University of Technology

[moodle-oercourseinfo_tugraz](https://github.com/llttugraz/moodle-oercourseinfo_tugraz)  
[moodle-oerclassifcation_oefos](https://github.com/llttugraz/moodle-oerclassification_oefos)  
[moodle-oeruploader_tugraz](https://github.com/llttugraz/moodle-oeruploader_tugraz)

