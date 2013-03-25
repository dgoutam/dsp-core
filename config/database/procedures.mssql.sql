CREATE PROCEDURE dbo.UpdateOrInsertSession
        @id nvarchar(32),
        @user_id int,
        @role_id int,
        @start_time int,
        @data nvarchar(4000)
    AS
    BEGIN
        IF EXISTS (SELECT id FROM df_sys_session WHERE id = @id)
            BEGIN
                UPDATE df_sys_session
                SET  user_id = @user_id, role_id = @role_id, start_time = @start_time, data = @data
                WHERE id = @id
            END
        ELSE
            BEGIN
                INSERT INTO df_sys_session (id, user_id, role_id, start_time, data)
                VALUES (@id, @user_id, @role_id, @start_time, @data)
            END
    END