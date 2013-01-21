CREATE PROCEDURE dbo.UpdateOrInsertSession
       @id nvarchar(32),
       @start_time int,
       @data nvarchar(4000)
    AS
    BEGIN
        IF EXISTS (SELECT id FROM session WHERE id = @id)
            BEGIN
                UPDATE session
                SET  data = @data, start_time = @start_time
                WHERE id = @id
            END
        ELSE
            BEGIN
                INSERT INTO session (id, start_time, data)
                VALUES ( @id, @start_time, @data )
            END
    END