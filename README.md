# moodle_block_mymindmap_overview

NEW: Now All new content in a course is marked with a red flag

The mymindmap_overview block otherwise presents the courses in the dashboard. 

Everyone knows the block myoverview which presents in the dashboard in a paginated form the courses where the user is registered. 

But the block mymindmap_overview has the advantage of seeing all its courses containing at least one module on a single screen zone and being able to navigate to the section, the activity or the chosen resource to access it. 

This view also shows how many modules constitute a course or section, which allows you to only go to courses offering content.

This plugin uses jsmind javascript tool to design the schemas. You can find it here:

https://github.com/hizzgdev/jsmind

There are some slowdowns due to searching the logs table for each student and each course needed to show the red flag.

To avoid this, you can replace the "block_mymindmap_overview" file with the "block_mymindmap_overview-without-flags" file by removing the "-without-flags" extension.

In this version, all calls to the "logs" table to control access to new activities are avoided in order to speed up the display of the mindmap 
