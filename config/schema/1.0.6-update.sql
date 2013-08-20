--
-- This file is part of the DreamFactory Services Platform(tm) (DSP)
--
-- DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
-- Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
--
-- Licensed under the Apache License, Version 2.0 (the "License");
-- you may not use this file except in compliance with the License.
-- You may obtain a copy of the License at
--
-- http://www.apache.org/licenses/LICENSE-2.0
--
-- Unless required by applicable law or agreed to in writing, software
-- distributed under the License is distributed on an "AS IS" BASIS,
-- WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
-- See the License for the specific language governing permissions and
-- limitations under the License.
--

--
-- DSP v1.0.6.x database update script for MySQL
--

DROP TABLE IF EXISTS `df_sys_account_provider`;
DROP TABLE IF EXISTS `df_sys_service_account`;
DROP TABLE IF EXISTS `df_sys_portal_account`;

--	Unique index on portal accounts
DROP INDEX undx_provider_user_provider_user_id ON df_sys_provider_user;
CREATE UNIQUE INDEX undx_provider_user_provider_user_id ON df_sys_provider_user (user_id, provider_id, provider_user_id);
