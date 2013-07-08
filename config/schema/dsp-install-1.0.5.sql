/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * DSP v1.0.5 database creation script for MySQL
 */

/*!40101 SET NAMES utf8 */;
/*!40101 SET SQL_MODE = ''*/;
/*!40014 SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS */`dreamfactory` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `dreamfactory`;

/**
 * Table structure for table `df_sys_app`
 */
CREATE TABLE `df_sys_app` (
		`id`                      INT(11)     NOT NULL AUTO_INCREMENT,
		`api_name`                VARCHAR(64) NOT NULL,
		`name`                    VARCHAR(64) NOT NULL,
		`description`             TEXT,
		`is_active`               TINYINT(1)  NOT NULL DEFAULT 1,
		`url`                     TEXT,
		`is_url_external`         TINYINT(1)  NOT NULL DEFAULT 0,
		`import_url`              TEXT,
		`storage_service_id`      INT(11) DEFAULT NULL,
		`storage_container`       VARCHAR(255) DEFAULT NULL,
		`requires_fullscreen`     TINYINT(1)  NOT NULL DEFAULT 0,
		`allow_fullscreen_toggle` TINYINT(1)  NOT NULL DEFAULT 1,
		`toggle_location`         VARCHAR(64) NOT NULL DEFAULT 'top',
		`requires_plugin`         TINYINT(1)  NOT NULL DEFAULT 0,
		`created_date`            DATETIME    NOT NULL,
		`last_modified_date`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_by_id`           INT(11)     NOT NULL,
		`last_modified_by_id`     INT(11)     NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `undx_df_sys_app_api_name` (`api_name`),
		UNIQUE KEY `undx_df_sys_app_name` (`name`),
		KEY `fk_df_sys_app_storage_service_id` (`storage_service_id`),
		KEY `fk_df_sys_app_created_by_id` (`created_by_id`),
		KEY `fk_df_sys_app_last_modified_by_id` (`last_modified_by_id`),
		CONSTRAINT `fk_df_sys_app_last_modified_by_id` FOREIGN KEY (`last_modified_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_app_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_app_storage_service_id` FOREIGN KEY (`storage_service_id`) REFERENCES `df_sys_service` (`id`)
				ON DELETE SET NULL
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		AUTO_INCREMENT =1
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_app_group` */
CREATE TABLE `df_sys_app_group` (
		`id`                  INT(11)     NOT NULL AUTO_INCREMENT,
		`name`                VARCHAR(64) NOT NULL,
		`description`         VARCHAR(512),
		`created_date`        DATETIME    NOT NULL,
		`last_modified_date`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_by_id`       INT(11)     NOT NULL,
		`last_modified_by_id` INT(11)     NOT NULL,
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_app_group_created_by_id` (`created_by_id`),
		KEY `fk_df_sys_app_group_last_modified_by_id` (`last_modified_by_id`),
		CONSTRAINT `fk_df_sys_app_group_last_modified_by_id` FOREIGN KEY (`last_modified_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_app_group_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `df_sys_user` (`id`)
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_app_to_app_group` */
CREATE TABLE `df_sys_app_to_app_group` (
		`id`           INT(11) NOT NULL AUTO_INCREMENT,
		`app_id`       INT(11) NOT NULL,
		`app_group_id` INT(11) NOT NULL,
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_app_to_app_group_app_id` (`app_id`),
		KEY `fk_df_sys_app_to_app_group_app_group_id` (`app_group_id`),
		CONSTRAINT `fk_df_sys_app_to_app_group_app_group_id` FOREIGN KEY (`app_group_id`) REFERENCES `df_sys_app_group` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE,
		CONSTRAINT `fk_df_sys_app_to_app_group_app_id` FOREIGN KEY (`app_id`) REFERENCES `df_sys_app` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_app_to_role` */
CREATE TABLE `df_sys_app_to_role` (
		`id`      INT(11) NOT NULL AUTO_INCREMENT,
		`app_id`  INT(11) NOT NULL,
		`role_id` INT(11) NOT NULL,
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_app_to_role_app_id` (`app_id`),
		KEY `fk_df_sys_app_to_role_role_id` (`role_id`),
		CONSTRAINT `fk_df_sys_app_to_role_role_id` FOREIGN KEY (`role_id`) REFERENCES `df_sys_role` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE,
		CONSTRAINT `fk_df_sys_app_to_role_app_id` FOREIGN KEY (`app_id`) REFERENCES `df_sys_app` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_app_to_service` */
CREATE TABLE `df_sys_app_to_service` (
		`id`         INT(11) NOT NULL AUTO_INCREMENT,
		`app_id`     INT(11) NOT NULL,
		`service_id` INT(11) NOT NULL,
		`component`  VARCHAR(128),
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_app_to_service_app_id` (`app_id`),
		KEY `fk_df_sys_app_to_service_service_id` (`service_id`),
		CONSTRAINT `fk_df_sys_app_to_service_service_id` FOREIGN KEY (`service_id`) REFERENCES `df_sys_service` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE,
		CONSTRAINT `fk_df_sys_app_to_service_app_id` FOREIGN KEY (`app_id`) REFERENCES `df_sys_app` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_config` */
CREATE TABLE `df_sys_config` (
		`id`                      INT(11)      NOT NULL AUTO_INCREMENT,
		`db_version`              VARCHAR(32)  NOT NULL,
		`allow_open_registration` TINYINT(1)   NOT NULL DEFAULT '0',
		`open_reg_role_id`        INT(11) DEFAULT NULL,
		`allow_guest_user`        TINYINT(1)   NOT NULL DEFAULT '0',
		`guest_role_id`           INT(11) DEFAULT NULL,
		`editable_profile_fields` VARCHAR(255) NOT NULL DEFAULT 'email,display_name,first_name,last_name,phone,default_app_id,security_question,security_answer',
		`created_date`            DATETIME     NOT NULL,
		`last_modified_date`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_by_id`           INT(11) DEFAULT NULL,
		`last_modified_by_id`     INT(11) DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_config_open_reg_role_id` (`open_reg_role_id`),
		KEY `fk_df_sys_config_guest_role_id` (`guest_role_id`),
		KEY `fk_df_sys_config_created_by_id` (`created_by_id`),
		KEY `fk_df_sys_config_last_modified_by_id` (`last_modified_by_id`),
		CONSTRAINT `fk_df_sys_config_last_modified_by_id` FOREIGN KEY (`last_modified_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_config_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_config_guest_role_id` FOREIGN KEY (`guest_role_id`) REFERENCES `df_sys_role` (`id`)
				ON DELETE SET NULL,
		CONSTRAINT `fk_df_sys_config_open_reg_role_id` FOREIGN KEY (`open_reg_role_id`) REFERENCES `df_sys_role` (`id`)
				ON DELETE SET NULL
)
		ENGINE =InnoDB
		AUTO_INCREMENT =2
		DEFAULT CHARSET =utf8;

/*Data for the table `df_sys_config` */
INSERT INTO `df_sys_config`
(`id`, `db_version`, `allow_open_registration`, `open_reg_role_id`, `allow_guest_user`, `guest_role_id`, `editable_profile_fields`, `created_date`, `last_modified_date`, `created_by_id`, `last_modified_by_id`)
		VALUES
		(1, '1.0.5', 0, NULL, 0, NULL, 'email,display_name,first_name,last_name,phone,default_app_id,security_question,security_answer', NOW(), NOW(), NULL, NULL);

/*Table structure for table `df_sys_email_template` */
CREATE TABLE `df_sys_email_template` (
		`id`                  INT(11)     NOT NULL AUTO_INCREMENT,
		`name`                VARCHAR(64) NOT NULL,
		`description`         TEXT,
		`to`                  TEXT,
		`cc`                  TEXT,
		`bcc`                 TEXT,
		`subject`             VARCHAR(80) DEFAULT NULL,
		`body_text`           TEXT,
		`body_html`           TEXT,
		`from_name`           VARCHAR(80) DEFAULT NULL,
		`from_email`          VARCHAR(255) DEFAULT NULL,
		`reply_to_name`       VARCHAR(80) DEFAULT NULL,
		`reply_to_email`      VARCHAR(255) DEFAULT NULL,
		`defaults`            TEXT,
		`created_date`        DATETIME    NOT NULL,
		`last_modified_date`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_by_id`       INT(11)     NOT NULL,
		`last_modified_by_id` INT(11)     NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `undx_df_sys_email_template_name` (`name`),
		KEY `fk_df_sys_email_template_created_by_id` (`created_by_id`),
		KEY `fk_df_sys_email_template_last_modified_by_id` (`last_modified_by_id`),
		CONSTRAINT `fk_df_sys_email_template_last_modified_by_id` FOREIGN KEY (`last_modified_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_email_template_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `df_sys_user` (`id`)
)
		ENGINE =InnoDB
		AUTO_INCREMENT =2
		DEFAULT CHARSET =utf8;

/*Data for the table `df_sys_email_template` */

INSERT INTO `df_sys_email_template`
(`id`, `name`, `description`, `to`, `cc`, `bcc`, `subject`, `body_text`, `body_html`, `from_name`, `from_email`, `reply_to_name`, `reply_to_email`, `defaults`, `created_date`, `last_modified_date`, `created_by_id`, `last_modified_by_id`)
		VALUES
		(1, 'User Invite', 'Email sent to new users allowing them to set their password and log in.', NULL, NULL, NULL, 'Welcome to DreamFactory', NULL, 'Hi {first_name},<br/>\n<br/>\nYou have been invited to become a DreamFactory user. Click the confirmation link below to set your password and log in.<br/>\n<br/>\n{_invite_url_}<br/>\n<br/>\nEnjoy!<br/>\n<br/>\nDreamFactory', 'DreamFactory', 'no-reply@dreamfactory.com', 'DreamFactory', 'no-reply@dreamfactory.com', NULL, NOW(), NOW(), 1, 1);

/*Table structure for table `df_sys_role` */
CREATE TABLE `df_sys_role` (
		`id`                  INT(11)     NOT NULL AUTO_INCREMENT,
		`name`                VARCHAR(64) NOT NULL,
		`description`         TEXT,
		`is_active`           TINYINT(1)  NOT NULL DEFAULT '1',
		`default_app_id`      INT(11) DEFAULT NULL,
		`created_date`        DATETIME    NOT NULL,
		`last_modified_date`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_by_id`       INT(11)     NOT NULL,
		`last_modified_by_id` INT(11)     NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `undx_df_sys_role_name` (`name`),
		KEY `fk_df_sys_role_default_app_id` (`default_app_id`),
		KEY `fk_df_sys_role_created_by_id` (`created_by_id`),
		KEY `fk_df_sys_role_last_modified_by_id` (`last_modified_by_id`),
		CONSTRAINT `fk_df_sys_role_last_modified_by_id` FOREIGN KEY (`last_modified_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_role_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_role_default_app_id` FOREIGN KEY (`default_app_id`) REFERENCES `df_sys_app` (`id`)
				ON DELETE SET NULL
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_role_service_access` */
CREATE TABLE `df_sys_role_service_access` (
		`id`         INT(11)     NOT NULL AUTO_INCREMENT,
		`role_id`    INT(11)     NOT NULL,
		`service_id` INT(11) DEFAULT NULL,
		`component`  VARCHAR(128) DEFAULT NULL,
		`access`     VARCHAR(64) NOT NULL DEFAULT 'No Access',
		`access_id`  INT(11)     NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_role_service_access_role_id` (`role_id`),
		KEY `fk_df_sys_role_service_access_service_id` (`service_id`),
		CONSTRAINT `fk_df_sys_role_service_access_service_id` FOREIGN KEY (`service_id`) REFERENCES `df_sys_service` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE,
		CONSTRAINT `fk_df_sys_role_service_access_role_id` FOREIGN KEY (`role_id`) REFERENCES `df_sys_role` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_role_system_access` */
CREATE TABLE `df_sys_role_system_access` (
		`id`        INT(11)     NOT NULL AUTO_INCREMENT,
		`role_id`   INT(11)     NOT NULL,
		`component` VARCHAR(128) DEFAULT NULL,
		`access`    VARCHAR(64) NOT NULL DEFAULT 'No Access',
		`access_id` INT(11)     NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_role_system_access_role_id` (`role_id`),
		CONSTRAINT `fk_df_sys_role_system_access_role_id` FOREIGN KEY (`role_id`) REFERENCES `df_sys_role` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_schema_extras` */
CREATE TABLE `df_sys_schema_extras` (
		`id`                  INT(11)      NOT NULL AUTO_INCREMENT,
		`service_id`          INT(11),
		`table`               VARCHAR(128) NOT NULL,
		`field`               VARCHAR(128) NOT NULL,
		`name_field`          VARCHAR(128) NOT NULL,
		`label`               VARCHAR(128),
		`plural`              VARCHAR(128),
		`picklist`            TEXT,
		`validation`          VARCHAR(255) DEFAULT NULL,
		`is_user_id`          TINYINT(1) DEFAULT NULL,
		`user_id_on_update`   TINYINT(1) DEFAULT NULL,
		`timestamp_on_update` TINYINT(1) DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `fk_df_sys_schema_extras_service_id` (`service_id`),
		KEY `ndx_df_sys_schema_extras_table` (`table`),
		KEY `ndx_df_sys_schema_extras_field` (`field`),
		KEY `ndx_df_sys_schema_extras_name_field` (`name_field`),
		CONSTRAINT `fk_df_sys_schema_extras_service_id` FOREIGN KEY (`service_id`) REFERENCES `df_sys_service` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		AUTO_INCREMENT =1
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_service` */
DROP TABLE IF EXISTS `df_sys_service`;

CREATE TABLE `df_sys_service` (
		`id`                  INT(11)     NOT NULL AUTO_INCREMENT,
		`api_name`            VARCHAR(64) NOT NULL,
		`name`                VARCHAR(64) NOT NULL,
		`description`         VARCHAR(512) DEFAULT NULL,
		`is_active`           TINYINT(1)  NOT NULL DEFAULT 1,
		`type`                VARCHAR(64) NOT NULL,
		`type_id`             INT(11)     NOT NULL DEFAULT 1,
		`storage_name`        VARCHAR(128) DEFAULT NULL,
		`storage_type`        VARCHAR(64) DEFAULT NULL,
		`credentials`         MEDIUMTEXT,
		`native_format`       VARCHAR(64) DEFAULT NULL,
		`native_format_id`    INT(11)     NOT NULL DEFAULT 0,
		`base_url`            TEXT,
		`service_class`       VARCHAR(1024) DEFAULT NULL,
		`parameters`          TEXT,
		`headers`             TEXT,
		`created_date`        DATETIME    NOT NULL,
		`last_modified_date`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_by_id`       INT(11)     NOT NULL,
		`last_modified_by_id` INT(11)     NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `undx_df_sys_service_api_name` (`api_name`),
		UNIQUE KEY `undx_df_sys_service_name` (`name`),
		KEY `fk_df_sys_service_created_by_id` (`created_by_id`),
		KEY `fk_df_sys_service_last_modified_by_id` (`last_modified_by_id`),
		CONSTRAINT `fk_df_sys_service_last_modified_by_id` FOREIGN KEY (`last_modified_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_service_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `df_sys_user` (`id`)
)
		ENGINE =InnoDB
		AUTO_INCREMENT =1
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_service_auth` */
CREATE TABLE `df_sys_service_auth` (
		`user_id`            INT(11)    NOT NULL,
		`service_id`         INT(11)    NOT NULL,
		`auth_text`          MEDIUMTEXT NOT NULL,
		`created_date`       DATETIME   NOT NULL,
		`last_modified_date` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`user_id`, `service_id`)
)
		ENGINE =InnoDB
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_stat` */
CREATE TABLE `df_sys_stat` (
		`id`                 INT(11)   NOT NULL AUTO_INCREMENT,
		`type`               INT(11)   NOT NULL,
		`user_id`            INT(11)   NOT NULL,
		`stat_date`          DATETIME  NOT NULL,
		`stat_data`          TEXT      NOT NULL,
		`reported_ind`       INT(1)    NOT NULL DEFAULT 0,
		`created_date`       DATETIME  NOT NULL,
		`last_modified_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`)
)
		ENGINE =MyISAM
		DEFAULT CHARSET =utf8;

/*Table structure for table `df_sys_user` */
CREATE TABLE `df_sys_user` (
		`id`                  INT(11)      NOT NULL AUTO_INCREMENT,
		`email`               VARCHAR(255) NOT NULL,
		`password`            VARCHAR(64)  NOT NULL,
		`first_name`          VARCHAR(64)  NOT NULL,
		`last_name`           VARCHAR(64)  NOT NULL,
		`display_name`        VARCHAR(128) NOT NULL,
		`phone`               VARCHAR(32) DEFAULT NULL,
		`is_active`           TINYINT(1)   NOT NULL DEFAULT '1',
		`is_sys_admin`        TINYINT(1)   NOT NULL DEFAULT '0',
		`is_deleted`          TINYINT(1)   NOT NULL DEFAULT '0',
		`confirm_code`        VARCHAR(128) DEFAULT NULL,
		`default_app_id`      INT(11) DEFAULT NULL,
		`role_id`             INT(11) DEFAULT NULL,
		`security_question`   VARCHAR(128) DEFAULT NULL,
		`security_answer`     VARCHAR(64) DEFAULT NULL,
		`last_login_date`     DATETIME DEFAULT NULL,
		`created_date`        DATETIME     NOT NULL,
		`last_modified_date`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_by_id`       INT(11) DEFAULT NULL,
		`last_modified_by_id` INT(11) DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `undx_df_sys_user_email` (`email`),
		KEY `fk_df_sys_user_default_app_id` (`default_app_id`),
		KEY `fk_df_sys_user_role_id` (`role_id`),
		KEY `fk_df_sys_user_created_by_id` (`created_by_id`),
		KEY `fk_df_sys_user_last_modified_by_id` (`last_modified_by_id`),
		KEY `ndx_df_sys_user_first_name` (`first_name`),
		KEY `ndx_df_sys_user_last_name` (`last_name`),
		KEY `ndx_df_sys_user_display_name` (`display_name`),
		CONSTRAINT `fk_df_sys_user_last_modified_by_id` FOREIGN KEY (`last_modified_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_user_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `df_sys_user` (`id`),
		CONSTRAINT `fk_df_sys_user_default_app_id` FOREIGN KEY (`default_app_id`) REFERENCES `df_sys_app` (`id`)
				ON DELETE SET NULL
				ON UPDATE CASCADE,
		CONSTRAINT `fk_df_sys_user_role_id` FOREIGN KEY (`role_id`) REFERENCES `df_sys_role` (`id`)
				ON DELETE SET NULL
				ON UPDATE CASCADE
)
		ENGINE =InnoDB
		AUTO_INCREMENT =1
		DEFAULT CHARSET =utf8;

/*!40101 SET SQL_MODE = @OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES = @OLD_SQL_NOTES */;
