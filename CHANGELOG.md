# DSP v1.1.0 Change Log

## Major Bug Fixes
* Add description to AppGroup and Role models
* Current user can no longer change their own role(s)
* Permission issues when saving new data
* All remaining open issues from version 1.0.6 fixed

## Major New Features
* Removed most DSP-specific code from Yii routing engine
* Most, if not all, resource access moved to a single class "ResourceStore"
* "/rest/system/constant" call to return system constants for list building
* Swagger annotations removed from code an placed into their own files
* Take "Accept" header into account for determination of content
* Config call now returns any available remote authentication providers
* Added new service to retrieve and update data from Salesforce
* New authentication provider service using the Oasys library
* Remote login services added to core and app-launchpad
* Added support for "global" authentication providers for enterprise customers

## Major Foundational Changes
* Most system types (i.e. service, storage, etc.) are now numeric instead of hard-coded strings
* Services now configured from /config/services.config.php instead of being hard-coded
* /src tree removed, replaced by new Composer libraries (lib-php-common-platform)
* Prep work for new "portal" service (future release)
* Prep work for moving to Bootstrap v3.x (future release)
* Prep work for new administration dashboard (future release)
