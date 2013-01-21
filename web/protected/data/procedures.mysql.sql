CREATE PROCEDURE `UpdateOrInsertSession`(IN the_id nvarchar(32),
                                         IN the_start int,
                                         IN the_data nvarchar(4000))
    BEGIN
        IF EXISTS (SELECT `id` FROM `session` WHERE `id` = the_id) THEN
            UPDATE session
            SET  `data` = the_data, `start_time` = the_start
            WHERE `id` = the_id;
        ELSE
            INSERT INTO session (`id`, `start_time`, `data`)
            VALUES ( the_id, the_start, the_data );
        END IF;
    END
