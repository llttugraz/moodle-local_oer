<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Open Educational Resources Plugin
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Open Educational Resources';
$string['oer_link'] = 'OER';
$string['cb_allowedlist'] = 'Allowedlist';
$string['cb_allowedlist_desc'] = 'Used to maintain a userlist with access to oer metadata editor';
$string['oer_intro'] = '<blockquote>' .
        '<p>"Open Educational Resources (OER) sind freie Bildungsmaterialien,' .
        ' d.h. Lehr- und Lernmaterialien, die frei zugänglich sind und dank entsprechender ' .
        'Lizenzierung (oder weil sie gemeinfrei sind) ohne zusätzliche Erlaubnis bearbeitet, ' .
        'weiterentwickelt und weitergegeben werden dürfen."</p>' .
        '<p><cite>Bündnis Freie Bildung, 2015</cite></p>' .
        '</blockquote>';
$string['manageview'] = 'OER Settings and allowedlist';
$string['manage_oer'] = 'OER Clearance';
$string['log_oer'] = 'OER Logs and errors';
$string['potusers'] = 'Choose user(s)';
$string['oerusers'] = 'Authorised users';
$string['usersmatching'] = 'Authorised users';
$string['potusersmatching'] = 'Authorised users';
$string['filetype'] = 'Type';
$string['language'] = 'Language';
$string['language_help'] = 'What language is used';
$string['resourcetype'] = 'Resource Type';
$string['role'] = 'Role';
$string['author'] = 'Author';
$string['publisher'] = 'Publisher';
$string['license'] = 'Licence';
$string['tags'] = 'Tags';
$string['noselection'] = 'no selection';
$string['figure'] = 'Figure';
$string['diagram'] = 'Diagram';
$string['narrative'] = 'Narrative';
$string['experiment'] = 'Experiment';
$string['questionnaire'] = 'Questionnaire';
$string['graphic'] = 'Graphic';
$string['contents'] = 'Table of contents';
$string['presentationslide'] = 'Presentation slide';
$string['problem'] = 'Problem';
$string['exam'] = 'Exam';
$string['selfassesment'] = 'Self-assesment';
$string['simulation'] = 'Simulation';
$string['chart'] = 'Chart';
$string['exercise'] = 'Exercise';
$string['lecture'] = 'Lecture';
$string['coursename'] = 'Title';
$string['coursename_help'] = 'Title of the course';
$string['lecturer'] = 'Lecturer';
$string['lecturer_help'] = 'The lecturers of the course are not necessarily ' .
        'the authors of the file.';
$string['structure'] = 'Structure';
$string['structure_help'] = 'What is the mode of the course? ' .
        'e.g. lecture, exercise, laboratory ...';
$string['organisation'] = 'Organisation';
$string['organisation_help'] = 'Name of the organization offering the course';
$string['description'] = 'Content';
$string['description_help'] = 'A description of the course. What is this course about.';
$string['objectives'] = 'Objective';
$string['objectives_help'] = 'What are the main goals of this course.';
$string['preferences'] = 'Preferences';
$string['nopreference'] = 'no preference';
$string['error_upload_license'] = 'A Creative Commons or Public Domain license is ' .
        'required to release files.' .
        'You must either uncheck "release" or set the appropriate license.';
$string['error_upload_author'] = 'A person with the role {$a->roles} is required for releasing files';
$string['error_license'] = 'Wrong license for release selected';
$string['no_files_heading'] = 'No files found';
$string['no_files_filter'] = 'No files are found with this filter setting.';
$string['no_files_body'] = 'No files were found in the course.';
$string['no_files_description'] = 'Only files from the activity \'file\' or ' .
        'the activity \'folder\' are shown.';
$string['error_body'] = 'An error has occured, please reload the page. ' .
        'If this error happens again please contact your administrator.';
$string['error_message'] = 'Errormessage';
$string['oer:manage'] = 'Manage OER settings';
$string['oer:edititems'] = 'Edit file metadata in oer plugin';
$string['oer:viewitems'] = 'View files and metadata in oer plugin';
$string['logheading'] = 'Log';
$string['message'] = 'Message';
$string['type'] = 'Type';
$string['privacy:metadata:local_oer_userlist'] = 'Userid is stored to maintain allowance/disallowance list';
$string['privacy:metadata:local_oer_userlist:userid'] = 'Userid of user';
$string['privacy:metadata:local_oer_userlist:type'] = 'Type of clearance';
$string['privacy:metadata:local_oer_userlist:timecreated'] = 'Time when clearance was set';
$string['subpluginsheading'] = 'List of installed subplugins';
$string['no_value'] = 'Default courseinfo aggregator';
$string['metadataaggregator'] = 'Courseinfo metadata';
$string['metadataaggregator_description'] = 'Select where the metadata fields for the courseinformation ' .
        'are gathered from.';
$string['updatecourseinfo'] = 'Task to synchronise course metadata';
$string['courseinfobutton'] = 'Course Metadata';
$string['preferencebutton'] = 'Preferences';
$string['ignorecourse'] = 'Ignore';
$string['ignoredcourse'] = 'Exclude the metadata of this course';
$string['ignorecourse_help'] = 'If selected, this course metadata will not be ' .
        'added to file metadata.';
$string['deleted'] = 'Deleted';
$string['deleted_help'] = 'The automatic course metadata synchronization has marked this ' .
        'course as deleted because the external source is no longer ' .
        'associated with this Moodle course.' .
        ' Since the metadata was edited manually, ' .
        'the entry was marked and not deleted.' .
        ' If you do not need the entries anymore, ' .
        'you can uncheck the checkboxes of the' .
        ' edited fields and the metadata will be ' .
        'deleted during the next synchronization.';
$string['minimumchars'] = 'Minimum of {$a} characters';
$string['errorempty'] = 'This field cannot be empty.';
$string['all'] = 'All files';
$string['upload'] = 'Marked for release';
$string['norelease'] = 'Not marked for release';
$string['ignore'] = 'Ignored';
$string['ignore_help'] = 'The file will be shown as ignored and ' .
        'sorted to the end of the file list.';
$string['noignore'] = 'Not ignored';
$string['deleted'] = 'Deleted files';
$string['preferencefilter'] = 'Files using preferences';
$string['nopreferencefilter'] = 'Files not using preferences';
$string['list'] = 'List';
$string['card'] = 'Card';
$string['title'] = 'Title';
$string['filedescription'] = 'Abstract';
$string['highereducation'] = 'Higher Education';
$string['person'] = 'Person(s)';
$string['prefperson'] = 'Person(s) (Preference)';
$string['preftags'] = 'Tags (Preference)';
$string['prefclassification'] = 'Additional tags (Preference)';
$string['person_help'] = 'The button opens a form in which you can enter the first and last name ' .
        'of a person and select their role for this element.'
        . '<p>Multiple persons are possible.</p>' .
        '<p>Once you have entered a name, you will see ' .
        'it above the button.</p>' .
        '<p>You can remove a name by clicking on the ' .
        'tag above the button.</p>';
$string['role_help'] = 'Select the role for the person. ' .
        'Roles depends on the type of the element, so not every role is available for every element.';
$string['role_description'] = 'The table shows which roles are available for which element type. ' .
        'Roles marked with a * (asterisk) are mandatory for that element type. ' .
        'When a person is added per preference form, the person will only be added to elements where the role is available.';
$string['confirmperson'] = 'Press <strong>Enter</strong> to confirm the person';
$string['preferenceenabled'] = 'Preferences are <strong>enabled</strong> for this file. ' .
        'Values that are set in preferences are locked in this view';
$string['prefdisable'] = 'Disable preferences';
$string['prefenable'] = 'Enable preferences';
$string['preferencedisabled'] = 'Preferences are <strong>disabled</strong> for this file';
$string['preferenceset'] = '(Preference)';
$string['noprefsetyet'] = 'Preferences have not been set for this course yet. If you want to ' .
        'use predefined values for some of the metadata fields, ' .
        'the preferences menu can be found in the navigation bar ' .
        'above the files.';
$string['preferencetags_help'] = 'Additional tags have been defined in preference setting . ' .
        'These tags will be added to the tags ' .
        'defined in the default field of this formular. ' .
        'If you do not want to use these tags in this file ' .
        'you can either disable preference for this file, ' .
        'or edit the preference to remove some or all tags. ' .
        'Duplicated tags in preference and file setting will be cleaned ' .
        'up when releasing the files.';
$string['preferenceset_help'] = 'This field is controlled by the preference setting. ' .
        'If you want to change this field you can either disable ' .
        'preference for this file, or edit the preference ' .
        'to not handle this field.';
$string['state'] = 'Status of file';
$string['markedforupload'] = 'Marked for release';
$string['notmarkedforupload'] = 'Not marked for release';
$string['isignored'] = 'Ignored';
$string['preferenceactive'] = 'Preferences are enabled';
$string['preferencenotactive'] = 'Preferences are disabled';
$string['selectcc'] = 'CC license type has to be set';
$string['correctlicense'] = 'License: {$a->license}';
$string['readyforupload'] = 'Requirements for release:';
$string['personmissing'] = 'Author / Publisher missing';
$string['persondefined'] = 'Author / Publisher has been set';
$string['contextset'] = 'Context ist set';
$string['contextnotset'] = 'Context has to be set';
$string['error_upload_context'] = 'To release a context must be set';
$string['default'] = 'Default';
$string['title_asc'] = 'Title ascending';
$string['title_desc'] = 'Title descending';
$string['released'] = 'Released';
$string['searchtitle'] = 'Search title';
$string['pullservice'] = 'public data webservice';
$string['pullservice_desc'] = 'A webservice to pull the public oer metadata ' .
        'from the moodle system. When this service is used ' .
        'for releasing the files, the external system needs a token to use ' .
        ' the set_release_data webservice to mark files as released . ';
$string['extendedpullservice'] = 'Extended webservice';
$string['extendedpullservice_desc'] = 'Per default only the released files will be shown in an ' .
        'activated pull service . An upload push service is necessary ' .
        'to upload the files to an external repository and mark the files ' .
        'as released. The extended webservice shows all files that meets ' .
        'all requirements to be released and are marked for release.';
$string['onecourseinfoneeded'] = 'At least one course information has to remain for the ' .
        'resulting file metadata';
$string['preferencedefault'] = 'Preference default';
$string['preferencedefault_desc'] = 'The course preferences settings are independently stored ' .
        'from file metadata informations. When a new filelist in a ' .
        'course is created there is no stored file metadata. ' .
        'The flag if a file uses course preferences, is stored in ' .
        'the file metadata table. This setting is necessary to set ' .
        'a default before the file metadata exist. When enabled two ' .
        'things are happening:' .
        '<ul>' .
        '<li>Files will be shown as preferences activated in frontend </li> ' .
        '<li>When the preferences meet the release requirements, files can ' .
        'be released without editing their metadata </li > ' .
        '</ul>';
$string['zipperfilesize'] = 'ZIP package size';
$string['zipperfilesize_description'] = 'Select the filesize for ZIP files . Important:' .
        'This restriction is not strict. ' .
        'The zipper counts files until the restriction is reached ' .
        'and creates independent ZIP volumes of each package.';
$string['zipnorestriction'] = 'No restriction';
$string['uselicensereplacement'] = 'License shortname replacement';
$string['uselicensereplacement_description'] = 'When enabled, a textarea is available where replacement ' .
        'strings for moodle license shortnames can be defined.';
$string['licensereplacement'] = 'License shortname replacement';
$string['licensereplacement_description'] = 'For external systems the license shortname system of ' .
        'Moodle can be a bit confusing. ' .
        'In this field a mapping for Moodle used shortname, ' .
        'and for resulting shortname in the files metadata can be defined. ' .
        'One entry per line in the format: <em>shortname=>replacement</em>';
$string['releaseplugin'] = 'Use for releases';
$string['releaseplugin_description'] = 'Choose subplugin to handle the release. ' .
        'When no subplugin is installed for uploading files to a ' .
        'repository, the pull service of the base plugin is available.';
$string['pullrelease'] = 'Release information';
$string['pullrelease_desc'] = 'The release of files is done via an external service. ' .
        'This service can access this Moodle at any time and load ' .
        'the files marked for release.';
$string['allowed'] = '<p>By uploading your teaching and learning materials, ' .
        'you make them openly and freely accessible through the ' .
        'library service so that other teachers and learners worldwide ' .
        'can use them.<br>' .
        'Please note that these materials are licensed under an open ' .
        'license, which means they comply with applicable ' .
        'copyright laws .</p> ';
$string['organisationheading'] = 'Organisation';
$string['organisation_desc'] = 'Information about the organisation using the OER Plugin, ' .
        'this information will at least be shown to users, ' .
        'that are not allowed yet to use the OER functionality. ';
$string['organisationname'] = 'Name';
$string['organisationname_desc'] = 'The name of the organisation';
$string['organisationphone'] = 'Phone';
$string['organisationphone_desc'] = 'A telephone number where support is reachable ...';
$string['organisationemail'] = 'Email';
$string['organisationemail_desc'] = 'An email address where support is reachable ...';
$string['oermetadataheading'] = 'Metadata settings';
$string['oermetadataheading_desc'] = 'Some settings for metadata related things like the ' .
        'selection of additional subplugin.';
$string['oerreleaseheading'] = 'Release settings';
$string['oerreleaseheading_desc'] = 'Settings that affect the release functionality';
$string['emailsubject'] = 'Email subject';
$string['emailsubject_desc'] = 'Email subject the email should be prefilled for support . ';
$string['notactive'] = 'This course is not active, please activate it using the ' .
        '"Activate course" button.';
$string['lastchange'] = 'Last modified:';
$string['uploaded'] = 'Released on:';
$string['context'] = 'Context';
$string['overwrite'] = 'Overwrite';
$string['courseinfoformhelp'] = 'Information about the course in which the file is used is also ' .
        'attached to the metadata of each file. Here you can edit the ' .
        'metadata of the course.';
$string['courseinfoformexternhelp'] = 'If you see more than one course here, it means that this course ' .
        'is connected to one or more external courses. ' .
        'In this case you can ignore individual courses from it. ' .
        'Their metadata will then not be attached to the files. ' .
        'At least the metadata of one course must be attached.';
$string['courseinfoformadditionalhelp'] = 'The course metadata is synchronized regularly. To overwrite ' .
        'synchronized information, activate the checkboxes next to the respective text fields.';
$string['preferenceinfoformhelp'] = '<p class="alert alert-info">The filled fields of this preferences ' .
        'form are used as the base values for files that are ' .
        'edited for the first time.</p>';
$string['context_help'] = 'The educational context this file is designed/written for.';
$string['license_help'] = 'To release a file, it is necessary to use a Creative Commons license.';
$string['tags_help'] = '<p>Additional tags to classify the file.</p>' .
        '<p>Enter the tag and confirm with <strong>Enter</strong>.</p>' .
        '<p>Multiple tags are possible.</p>' .
        '<p>Once you have entered a tag, you will see ' .
        'it above the input field.</p>' .
        '<p>You can remove a tag by clicking on the ' .
        'tag above the input field.</p>';
$string['resourcetype_help'] = 'What is the nature of the file';
$string['upload_help'] = 'To mark a file for release some requirements are necessary:' .
        '<ul>' .
        '<li>A context has been set.</li>' .
        '<li>At least one person (author or publisher) is present.</li>' .
        '<li>The license is set to Creative Commons or Public Domain.</li>' .
        '</ul>';
$string['title_help'] = 'File title. Initially, the file name used when uploading the ' .
        'file to Moodle is displayed. However, these file names are ' .
        'often not very meaningful and should be replaced by a title.';
$string['filedescription_help'] = 'A brief overview of the contents of the file';
$string['pressenter'] = 'Press the Enter key to confirm the field.';
$string['notallowedtext'] = 'Text for users without access';
$string['notallowedtext_desc'] = 'Access to the file metadata editor is controlled by an ' .
        'access list (either with strict permission, or affected ' .
        'users are blocked). This text field is displayed to these ' .
        'users. This is available as a setting to display organization ' .
        'related data such as support email or phone number.';
$string['licensenotfound'] = 'License not found';
$string['snapshottask'] = 'Task to create release snapshots';
$string['configtime'] = 'Set release time';
$string['releasetime'] = 'Releaserythm';
$string['releasetime_help'] = 'Choose release/snapshot rythm for file metadata';
$string['custom'] = 'Custom time';
$string['releasehour'] = 'Time of day';
$string['releasehour_help'] = 'Daytime the files will be released';
$string['customdates'] = 'Release dates';
$string['customdates_help'] = 'Single entry: DD.MM<br>' .
        'Multiple: DD.MM;DD.MM;DD.MM<br>' .
        '(DD Day, MM Month)';
$string['customdates_error'] = 'Wrong Format! <br>';
$string['uploadtimebutton'] = 'Set snapshot/release time';
$string['next_release'] = 'Next release of files';
$string['releasehistory'] = 'OER Release history';
$string['timediff'] = '{$a->days} days, {$a->hours} hours and {$a->minutes} minutes';
$string['prefresettext'] = 'Reset fields set in preferences to preference values. ' .
        'Other fields are not changed. Changes needs to be saved after reset.';
$string['prefresetbtn'] = 'Reset';
$string['addpersonbtn'] = 'Add person';
$string['amount'] = 'Elements per page';
$string['filecount'] = 'elements are available';
$string['filecount_info'] = 'The number of available elements depends on the filter settings';
$string['uploadignoreerror'] = 'Mark for release and ignore cannot be set at the same time!';
$string['requiredfields'] = 'Required fields';
$string['requiredfields_desc'] = 'Select the formular fields that are required for metadata/release.' .
        'Title, Person(s) and License are always required and ' .
        'are not shown here.';
$string['error_upload_resourcetype'] = 'A resource has to be selected for release.';
$string['error_upload_classification'] = 'At least one value is necessary for release.';
$string['error_upload_language'] = 'A language has to be selected for release.';
$string['error_upload_description'] = 'An abstract is necessary for release.';
$string['error_upload_tags'] = 'At least one tag is necessary for release.';
$string['requirementsmet'] = 'All requirements are met.';
$string['requirementsnotmet'] = 'Not all requirements are met.';
$string['oer_settings'] = 'Plugin settings';
$string['messageprovider:requirementschanged'] = 'OER Metadata requirements have changed';
$string['requirementschanged_subject'] = 'Open Educational Resources metadata requirements have changed';
$string['requirementschanged_body'] = 'Due to changes in the guidelines for handling Open Educational ' .
        'Resources, the requirements for publishing files have been changed. ' .
        '<br><br>' .
        'The metadata of the following files in course ' .
        '<a href="{$a->url}">{$a->course}</a> must ' .
        'be modified for republishing: <br><br>';
$string['requirementschanged_small'] = 'Open Educational Resources metadata requirements have changed';
$string['coursecustomfields'] = 'Add course customfields';
$string['coursecustomfields_description'] = 'When enabled, course custom fields are read from the system and ' .
        'are added to the course metadata of the moodle course.';
$string['customfieldcategory'] = 'Custom field category';
$string['customfieldcategory_help'] = '<p>This is the name of a custom field category, below the fields of ' .
        'the category can be seen.</p>' .
        '<p>Customfields cannot be overwritten. ' .
        'Editing is only possible in the course settings.</p>';
$string['coursecustomfieldsvisibility'] = 'Set customfield visibility level';
$string['coursecustomfieldsvisibility_description'] = '<p>The visibility level indicates the fields that are ' .
        'added to the metadata. When a customfield is set up in Moodle, it ' .
        'has to be defined which users can see the field. There are three ' .
        'options for that. In OER context these three options can be used as ' .
        'follows </p>' .
        '<ul>' .
        '<li>"Everyone": Only fields marked with this state are added</li>' .
        '<li>"Teachers": "Teachers" and "Everyone" fields are added</li>' .
        '<li>"Nobody": All fields are added ' .
        '(including the options above)</li>' .
        '</ul>';
$string['coursecustomfieldsignored'] = 'Ignore customfields';
$string['coursecustomfieldsignored_description'] = '<p>Ignore customfields by selecting them in ' .
        'this multiselect field. ' .
        'Per default all fields are added to the course metadata when ' .
        'customfields are enabled for OER plugin.</p>' .
        '<p> Keep in mind that the visibility setting is also applied and ' .
        'fields are eventually not be shown, even if they are shown ' .
        'in this setting.</p>' .
        '<p>Field format: {fullname} ({category} {visibility})</p>';
$string['customfield_help'] = 'Course custom fields can be added in a similar way as ' .
        'the default course fields . They can also be overwritten so that ' .
        'an other value than the one set in course can be used . ' .
        'Also they can be ignored, to not add them to the course metadata ' .
        'of the released OER objects . ';
$string['nofieldsincat'] = 'This customfield category does not have any fields to show.';
$string['multiplecourses'] = 'File is used in multiple courses';
$string['multiplecoursestofile'] = 'File is used in multiple courses. You can also add course metadata ' .
        'that belongs to an other course than where the file is edited.';
$string['metadatanotwritable'] = 'The metadata of this file cannot be edited.';
$string['reason'] = 'Reason';
$string['metadatanotwritable0'] = 'An error has been found. It seems that this file has been edited ' .
        'in multiple places.';
$string['metadatanotwritable2'] = 'The metadata of this file has already been edited ' .
        'in another course.';
$string['metadatanotwritable3'] = 'This file has already been released and cannot be edited anymore.';
$string['contactsupport'] = 'For further information, please contact ' .
        '<a href="mailto:{$a->support}">{$a->support}</a>.';
$string['showmetadata'] = 'Show stored metadata';
$string['coursetofile'] = 'Overwrite coursemetada for each file';
$string['coursetofile_info'] = 'In this form course metadata is listed which is available ' .
        'for this file. Here you can override the course settings, ' .
        'which course metadata will be attached to this file.';
$string['coursetofile_description'] = 'In courses, the course metadata can be edited. ' .
        'You can decide whether the metadata of the Moodle ' .
        'course and external courses that are linked to a Moodle ' .
        'course (subplugin) are attached to files. If this setting ' .
        'is enabled, this can also be overridden on a per file basis. ' .
        'Furthermore, the course metadata from other courses that ' .
        'use the same file can be attached.';
$string['tocourse'] = 'link';
$string['nocourseinfo'] = 'The course metadata of this course has not been synchronised for ' .
        'OER purposes yet. If you want to use course meteadata of this ' .
        'course, please open the OER view inside the course and edit the ' .
        'metadata.';
$string['editor'] = 'Editor';
$string['oneeditorselectederror'] = 'At least one of the editor courses metadata ' .
        'options has to be selected.';
$string['writablefields'] = 'The metadata for the fields: "{$a->fields}" will be stored back to the original source.';
$string['moreinformation'] = 'More information';
$string['noinfo'] = 'No additional information';
$string['origin'] = 'Origin';
$string['applicationprofile'] = 'Application profile';
$string['applicationprofile_description'] = 'Select the application profile which is used to generate the release metadata. <br>' .
        '<ul>' .
        '<li>v1.0.0: Profile used up to local_oer Plugin Version v2.2.1 (2023062800). ' .
        'Only supports files hosted in Moodle directly.' .
        'Use it for backwards compatibility</li>' .
        '<li>v2.0.0: Support for different element sources. Uses new identifier. Not compatible to older versions.</li>' .
        '</ul>' .
        'More information regarding the metadata structure can be found in the ' .
        '<a href="https://github.com/llttugraz/moodle-local_oer/blob/main/README.md" target="_blank">ReadMe</a>';
