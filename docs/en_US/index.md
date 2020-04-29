Waze in Time 
============

Description 
-----------

This plugin allows you to have the trip info (traffic taken into account) via
Waze. This plugin may no longer work if Waze no longer accepts that
queries his site

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

Setup 
-------------

### Plugin configuration: 

at. Installation / Creation

In order to use the plugin, you need to download, install and
activate it like any Jeedom plugin.

After that you will have to create your trip (s) :

Go to the plugins / organization menu, you will find the
Waze Duration plugin :

![configuration1](../images/configuration1.jpg)

Then you will arrive on the page which will list your equipment (you
can have multiple Routes) and which will allow you to create

![wazeintime screenshot2](../images/wazeintime_screenshot2.jpg)

Click on the Add Trip button or on the + button :

![config2](../images/config2.jpg)

You will then arrive on the configuration page of your Trip:

![wazeintime screenshot3](../images/wazeintime_screenshot3.jpg)

On this page you will find three sections :

i. General

In this section you will find all jeedom configurations. AT
know the name of your equipment, the object you want
associate it, category, if you want the equipment to be active or
no, and finally if you want it to be visible on the dashboard.

i. Setup

This section is one of the most important, it allows you to adjust the
starting and ending point :

-   These infos must be the latitudes and longitudes of the positions

-   They can be found using the site provided in
    clicking on the link of the page (you just have to enter a
    address and click on get GPS coordinates)

    i. Control Panel

![config3](../images/config3.jpg)

-   Duration 1 : duration of journey 1

-   Duration 2 : journey time with the alternative route

-   Path 1 : Path 1

-   Path 2 : Alternative route

-   Return Period 1 : return time with trip 1

-   Return Period 2 : return time with the alternative route

-   Return trip 1 : Return trip 1

-   Return trip 2 : Alternative return journey

-   Refresh : Refresh info

All these commands are available via scenarios and via the dashboard

### The widget : 

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

-   The button at the top right refreshes the info.

-   All info is visible (for journeys, if the journey is
    long, it can be truncated but the full version is visible in
    leaving the mouse over)

### How are the news refreshed : 

The information is refreshed once every 30 minutes. You can
refresh them on demand via scenario with the refresh command, or
via the dash with the double arrows

Changelog 
=========

Changelog detailed :
<https://github.com/jeedom/plugin-wazeintime/commits/stable>
