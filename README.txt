===============================================
The Constant database field for Moodle >= 2.3
===============================================

   The Constant database field allows the specification of an uneditable field value
   that does not change wherever it appears in the current Darabase activity.

   If the "Auto-increment" box is checked, the value is automatically assigned to new
   records and then incremented by one. Thus, each record is assigned a unique number.
   Auto-increment values are displayed according to the "Print format" setting, which
   is a format string for the PHP "sprintf" function, e.g. %04d pads the number with
   zeroes on the left so that it is four-digits wide. 1 => 0001, 2 => 0002, and so on.

   For full details see: http://php.net/manual/en/function.sprintf.php

=================================================
To INSTALL this plugin
=================================================

    ----------------
    Using GIT
    ----------------

    1. Clone this plugin to your server

       cd /PATH/TO/MOODLE
       git clone -q https://github.com/gbateson/moodle-datafield_constant.git mod/data/field/constant

    2. Add this plugin to the GIT exclude file

       cd /PATH/TO/MOODLE
       echo '/mod/data/field/constant/' >> '.git/info/exclude'

       (continue with steps 3 and 4 below)

    ----------------
    Using ZIP
    ----------------

    1. download the zip file from one of the following locations

        * https://github.com/gbateson/moodle-datafield_constant/archive/master.zip
        * https://bateson.kochi-tech.ac.jp/zip/plugins_datafield_constant.zip

    2. Unzip the zip file - if necessary renaming the resulting folder to "constant".
       Then upload, or move, the "constant" folder into the "mod/data/field" folder on
       your Moodle >= 2.3 site, to create a new folder at "mod/data/field/constant"

       (continue with steps 3 and 4 below)

    ----------------
    Using GIT or ZIP
    ----------------

    3. In Moodle <= 3.1, database plugin strings aren't fully modularised, so the
       following two strings need be added manually to the language pack for the
       Database activity module, in file "/PATH/TO/MOODLE/mod/data/lang/en/data.php"

          $string['constant'] = 'Constant';
          $string['nameconstant'] = 'Constant field';

    4. Log in to Moodle as administrator to initiate the install/update

       If the install/update does not begin automatically, you can initiate it
       manually by navigating to the following Moodle administration page:

          Settings -> Site administration -> Notifications

    ----------------
    Troubleshooting
    ----------------

    If you have a white screen when trying to view your Moodle site
    after having installed this plugin, then you should remove the
    plugin folder, enable Moodle debugging, and try the install again.

    With Moodle debugging enabled you should get a somewhat meaningful
    message about what the problem is.

    The most common issues with installing this plugin are:

    (a) the "constant" folder is put in the wrong place
        SOLUTION: make sure the folder is at "mod/data/field/constant"
                  under your main Moodle folder, and that the file
                  "mod/data/field/constant/field.class.php" exists

    (b) permissions are set incorrectly on the "mod/data/field/constant" folder
        SOLUTION: set the permissions to be the same as those of other folders
                  within the "mod/data/field" folder

    (c) there is a syntax error in the Database language file
        SOLUTION: remove your previous edits, and then copy and paste
                  the language strings from this README file

    (d) the PHP cache is old
        SOLUTION: refresh the cache, for example by restarting the web server,
                  or the PHP accelerator, or both

=================================================
To UPDATE this plugin
=================================================

    ----------------
    Using GIT
    ----------------

    1. Get the latest version of this plugin

       cd /PATH/TO/MOODLE/mod/data/field/constant
       git pull

    2. Log in to Moodle as administrator to initiate the update

    ----------------
    Using ZIP
    ----------------

    Repeat steps 1, 2 and 4 of the ZIP install procedure (see above)


===============================================
To ADD an Constant field to a database activity
===============================================

    1. Login to Moodle, and navigate to a course page in which you are a teacher (or admin)

    2. Locate, or create, the Database activity to which you wish to add a Constant field

    4. click the link to view the Database activity, and then click the "Fields" tab

    5. From the "Field type" menu at the bottom of the page, select "Constant"

    6. Enter values for "Field name" and "Field description"

    7. Enter the "Value" of the constant

    8. If the field is to be an auto-increment field, check the "Auto-increment" box and enter a "Print format"

    9. Click the "Save changes" button at the bottom of the page.
