CREATE PROCEDURE dbo.UpdateOrInsertSession
        @id nvarchar(32),
        @user_id int,
        @start_time int,
        @data nvarchar(4000)
    AS
    BEGIN
        IF EXISTS (SELECT id FROM session WHERE id = @id)
            BEGIN
                UPDATE session
                SET  data = @data, user_id = @user_id, start_time = @start_time
                WHERE id = @id
            END
        ELSE
            BEGIN
                INSERT INTO session (id, user_id, start_time, data)
                VALUES ( @id, @user_id, @start_time, @data )
            END
    END