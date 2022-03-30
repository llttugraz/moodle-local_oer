# moodle-local_oer

The Open Educational Resources (OER) plugin provides a graphical user interface (GUI) to release files from courses for public use.
The first version of the plugin was developed during a project about [open education](https://www.openeducation.at/). It was developed very close to the Graz University of Technology customizations of Moodle. The use for other educational institutions was therefore very limited. 

In this repository a refactored version of the plugin can be found. This version has been developed for a vanilla moodle. Adaptations, which are necessary for the Moodle instance at Graz University of Technology, were moved to subplugins. The base plugin is fully functional without these subplugins.

# Content

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

