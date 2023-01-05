# moodle-report_reflectionexporter

This plugin allows teachers to automatically export students assessments text into a PDF provided by IB Diploma Programme. Teachers can also add comments, that will be inserted in correct section of the PDF.

The plugin has the capacity to allow teachers to do the job on behalf of other teachers. In order to do so, groups with the teacher in charge and the students this teacher has allocated, have to be created in the course.



## Views ##

### Process own work ###
![](/screenshots/CompleteProcess.gif)

### Process on behalf of ###
![](/screenshots/CompleteProcessOnBehalf.gif)

### Process from started  ###
![](/screenshots/CompleteProcessFromStarted.gif)

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/report/

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2022 Veronica Bermegui

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
