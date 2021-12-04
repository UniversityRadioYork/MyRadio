BEGIN;

-- Remake the 'analytics' table to add more advanced functionality
DROP TABLE IF EXISTS myradio.analytics;
DROP FUNCTION IF EXISTS myradio.create_analytics_record(TEXT, TEXT, INTEGER, VARCHAR);

CREATE TABLE myradio.analytics (
    analytics_record_id BIGSERIAL PRIMARY KEY,
    page TEXT,
    ref TEXT DEFAULT NULL,
    member_officerships integer[],
    member_shows_bucketed integer,
    session_id VARCHAR(32),
    time timestamptz
);

-- For a given page, take a given memberid and find its officerships and shows
--  Useful for finding analytics of a page, based on attributes of its viewers
--  Inserts this into the "myradio.analytics" table
CREATE FUNCTION myradio.create_analytics_record(
    page TEXT,
    ref TEXT,
    memberid INTEGER,
    session_id VARCHAR(32)
)
RETURNS VOID
AS $$
    DECLARE
        officerships INTEGER[];
        num_shows INTEGER;
    BEGIN
        -- Find the officerships this member currently has
	--  Checks both analytics history and officers with this memberid
        SELECT array_agg(officerid) INTO officerships
        FROM public.member_officer
        WHERE member_officer.memberid = create_analytics_record.memberid
        AND from_date <= NOW()
        AND (till_date IS NULL OR till_date >= (NOW() + '28 days'::INTERVAL));

        -- Find the number of shows this member has
	--  Checks both analytics history and shows with this memberid
        SELECT COUNT(*) INTO num_shows
        FROM schedule.show
        WHERE show.memberid=create_analytics_record.memberid
        OR show_id IN (
               SELECT show_id FROM schedule.show_credit
                  WHERE creditid=create_analytics_record.memberid AND
                      (effective_to >= NOW() OR effective_to IS NULL)
        );

        -- Right, we've got what we need so dump output into analytics table
	--  Preserve reference data (like session_id, current datetime) for comparison
	--  Note that no identifiable details are preserved (hence why num_shows is rounded)
        INSERT INTO myradio.analytics
        (page, ref, member_officerships, member_shows_bucketed, session_id, time)
        VALUES (
                create_analytics_record.page,
                create_analytics_record.ref,
                officerships,
                CEIL(num_shows::decimal / 5),
                create_analytics_record.session_id,
                NOW()
               );
    END;
    $$
LANGUAGE plpgsql;

COMMIT;
