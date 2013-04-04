CREATE PROCEDURE `UpdateOrInsertSession`(IN the_id nvarchar(32),
                                         IN user_id int,
                                         IN role_id int,
                                         IN the_start int,
                                         IN the_data nvarchar(4000))
    BEGIN
        IF EXISTS (SELECT `id` FROM `df_sys_session` WHERE `id` = the_id) THEN
            UPDATE `df_sys_session`
            SET `user_id` = user_id, `role_id` = role_id, `start_time` = the_start, `data` = the_data
              WHERE `id` = the_id;
        ELSE
            INSERT INTO  `df_sys_session` (`id`, `user_id`, `role_id`, `start_time`, `data`)
            VALUES ( the_id, user_id, role_id, the_start, the_data );
        END IF;
    END
